<?php

namespace App\Services\Farmers;

use App\Enums\ApplicationStatus;
use App\Enums\AssessmentStatus;
use App\Enums\DocumentVerificationStatus;
use App\Enums\FarmerStatus;
use App\Enums\PaymentStatus;
use App\Models\Farmer;
use App\Models\MembershipApplication;
use App\Models\MembershipLedger;
use App\Services\Documents\DocumentVerificationService;
use App\Services\Documents\FarmerDocumentService;
use App\Services\Membership\MembershipLedgerService;
use App\Services\Membership\MembershipStatusService;
use App\Services\Payments\PaymentAssessmentService;
use App\Services\Payments\PaymentPostingService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class FarmerStatusWorkflowService
{
    public function __construct(
        private readonly FarmerDocumentService $farmerDocumentService,
        private readonly DocumentVerificationService $documentVerificationService,
        private readonly PaymentAssessmentService $paymentAssessmentService,
        private readonly PaymentPostingService $paymentPostingService,
        private readonly MembershipLedgerService $membershipLedgerService,
        private readonly MembershipStatusService $membershipStatusService,
    ) {
    }

    public function syncManualUpdate(Farmer $farmer, ?int $userId = null): Farmer
    {
        return match ($farmer->status) {
            FarmerStatus::ACTIVE => $this->activateFromAdminOverride($farmer, $userId),
            FarmerStatus::PENDING => $this->syncPendingWorkflow($farmer),
            default => $farmer->refresh(),
        };
    }

    private function activateFromAdminOverride(Farmer $farmer, ?int $userId = null): Farmer
    {
        return DB::transaction(function () use ($farmer, $userId): Farmer {
            $application = $this->resolveApplication($farmer);

            if (! $application) {
                return $this->membershipStatusService->activateMembership($farmer);
            }

            $now = CarbonImmutable::now();
            $application->loadMissing(['farmer.memberType', 'documents', 'paymentAssessments.payments']);

            foreach ($application->documents as $document) {
                $document->forceFill([
                    'is_received' => true,
                    'received_by' => $userId,
                    'received_at' => $document->received_at ?? $now,
                    'verification_status' => DocumentVerificationStatus::VERIFIED,
                    'verified_by' => $userId,
                    'verified_at' => $document->verified_at ?? $now,
                ])->save();
            }

            $application->forceFill([
                'status' => ApplicationStatus::APPROVED,
                'reviewed_by' => $userId,
                'reviewed_at' => $application->reviewed_at ?? $now,
                'rejection_reason' => null,
                'rejected_at' => null,
            ])->save();

            $assessment = $this->paymentAssessmentService->createForApplication($application->refresh()->load('farmer.memberType'));
            $assessment->loadMissing('payments');
            $paidAmount = round((float) $assessment->payments->sum('amount_paid'), 2);
            $amountDue = round((float) $assessment->total_amount_due, 2);
            $remainingBalance = round($amountDue - $paidAmount, 2);

            if ($amountDue <= 0.0) {
                $assessment->forceFill([
                    'status' => AssessmentStatus::WAIVED,
                ])->save();

                $summary = [
                    'amount_due' => $amountDue,
                    'paid_amount' => $paidAmount,
                    'balance' => 0.0,
                    'paid_at' => null,
                    'assessment_status' => AssessmentStatus::WAIVED,
                    'payment_status' => PaymentStatus::WAIVED,
                ];
            } elseif ($remainingBalance > 0.0) {
                $paymentResult = $this->paymentPostingService->record($assessment, [
                    'payment_method' => 'cash',
                    'reference_no' => 'ADMIN-ACTIVATE-' . $application->id,
                    'amount_paid' => $remainingBalance,
                    'paid_at' => $now->toDateTimeString(),
                ], $userId);

                $assessment = $assessment->refresh()->load('payments');
                $summary = $paymentResult['summary'];
            } else {
                $summary = $this->paymentPostingService->postPayments($assessment, $assessment->payments);
            }

            $ledger = MembershipLedger::query()->firstOrNew([
                'membership_transaction_id' => $application->id,
                'year' => $now->year,
            ]);

            $ledger->fill($this->membershipLedgerService->buildFromSource(
                'application',
                [
                    'id' => $application->id,
                    'status' => 'Active',
                    'member_type' => $application->farmer->memberType?->code,
                    'year' => $now->year,
                ],
                [
                    'fee_schedule_id' => $assessment->fee_schedule_id,
                    'member_type' => $application->farmer->memberType?->code,
                ],
                $summary,
            ));
            $ledger->save();

            $this->membershipStatusService->activateMembership($application->farmer, $now->year);
            $application->farmer->forceFill([
                'record_origin' => 'application',
            ])->save();

            return $application->farmer->refresh();
        });
    }

    private function syncPendingWorkflow(Farmer $farmer): Farmer
    {
        return DB::transaction(function () use ($farmer): Farmer {
            $application = $this->resolveApplication($farmer);

            $farmer->forceFill([
                'activated_at' => null,
            ])->save();

            if (! $application) {
                return $this->membershipStatusService->markPendingApplication($farmer);
            }

            $application->loadMissing(['documents', 'paymentAssessments.payments']);

            if ($application->status === ApplicationStatus::APPROVED) {
                return $this->membershipStatusService->markPendingPayment($farmer);
            }

            if ($this->documentVerificationService->allRequiredVerified($application)) {
                return $this->membershipStatusService->markPendingVerification($farmer);
            }

            $allSubmitted = $application->documents
                ->where('is_required', true)
                ->every(fn ($document) => $this->farmerDocumentService->readyForVerification($document));

            if ($allSubmitted) {
                return $this->membershipStatusService->markPendingVerification($farmer);
            }

            return $this->membershipStatusService->markPendingDocuments($farmer);
        });
    }

    private function resolveApplication(Farmer $farmer): ?MembershipApplication
    {
        return MembershipApplication::query()
            ->where('farmer_id', $farmer->id)
            ->latest('id')
            ->first();
    }
}
