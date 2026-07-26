<?php

namespace App\Services\Membership;

use App\Enums\AssessmentStatus;
use App\Enums\DocumentVerificationStatus;
use App\Enums\ReactivationStatus;
use App\Models\Farmer;
use App\Models\FarmerDocument;
use App\Models\MembershipLedger;
use App\Models\PaymentAssessment;
use App\Models\ReactivationRequest;
use App\Services\Documents\DocumentVerificationService;
use App\Services\Documents\FarmerDocumentService;
use App\Services\Payments\PaymentAssessmentService;
use App\Services\Payments\PaymentPostingService;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ReactivationRequestService
{
    private ?array $farmerDocumentColumns = null;

    public function __construct(
        private readonly FarmerDocumentService $farmerDocumentService,
        private readonly DocumentVerificationService $documentVerificationService,
        private readonly PaymentAssessmentService $paymentAssessmentService,
        private readonly PaymentPostingService $paymentPostingService,
        private readonly MembershipLedgerService $ledgerService,
        private readonly MembershipStatusService $membershipStatusService,
    ) {
    }

    public function createForFarmer(Farmer $farmer, array $attributes = []): ReactivationRequest
    {
        return DB::transaction(function () use ($farmer, $attributes): ReactivationRequest {
            $farmer->loadMissing('memberType');

            if ($farmer->status->value !== 'inactive') {
                throw new DomainException('Only inactive farmer records can start reactivation.');
            }

            $year = (int) ($attributes['year'] ?? now()->year);
            $source = (string) ($attributes['source'] ?? 'walk_in');

            $openRequest = ReactivationRequest::query()
                ->where('farmer_id', $farmer->id)
                ->where('year', $year)
                ->whereIn('status', ['Pending', 'Approved'])
                ->latest('id')
                ->first();

            if ($openRequest !== null) {
                $this->farmerDocumentService->ensureReactivationChecklist($openRequest);
                $this->createOrRefreshAssessment($openRequest);

                return $openRequest->refresh()->load(['farmer.memberType', 'documents.documentType', 'paymentAssessments.payments']);
            }

            $request = ReactivationRequest::query()->create([
                'farmer_id' => $farmer->id,
                'application_no' => $this->generateRequestNumber(),
                'year' => $year,
                'source' => $source,
                'status' => ReactivationStatus::SUBMITTED,
                'submitted_at' => CarbonImmutable::now(),
                'is_late' => false,
            ]);

            $this->farmerDocumentService->ensureReactivationChecklist($request);
            $this->createOrRefreshAssessment($request);

            return $request->refresh()->load(['farmer.memberType', 'documents.documentType', 'paymentAssessments.payments']);
        });
    }

    public function markDocumentReceived(ReactivationRequest $request, int $documentId, bool $received, ?int $userId = null): FarmerDocument
    {
        $request->loadMissing('documents');
        $document = $request->documents->firstWhere('id', $documentId)
            ?? throw new DomainException('Document not found for this reactivation request.');

        $document = $this->farmerDocumentService->markReceived($document, $received, $userId);

        if ($request->source === 'walk_in' && $received) {
            $document = $this->documentVerificationService->verifyDocument($document, $userId, 'Verified during reactivation intake.');
        }

        return $document;
    }

    public function verifyDocument(ReactivationRequest $request, int $documentId, ?int $userId = null, ?string $remarks = null): FarmerDocument
    {
        $request->loadMissing('documents');
        $document = $request->documents->firstWhere('id', $documentId)
            ?? throw new DomainException('Document not found for this reactivation request.');

        return $this->documentVerificationService->verifyDocument($document, $userId, $remarks);
    }

    public function rejectDocument(ReactivationRequest $request, int $documentId, ?int $userId = null, ?string $remarks = null): FarmerDocument
    {
        $request->loadMissing('documents');
        $document = $request->documents->firstWhere('id', $documentId)
            ?? throw new DomainException('Document not found for this reactivation request.');

        return $this->documentVerificationService->rejectDocument($document, $userId, $remarks);
    }

    public function attachOfficeDocumentScan(ReactivationRequest $request, int $documentId, UploadedFile $file): FarmerDocument
    {
        return DB::transaction(function () use ($request, $documentId, $file): FarmerDocument {
            $request->loadMissing(['documents', 'farmer']);

            if ($request->source !== 'walk_in') {
                throw new DomainException('Office document scans can only be attached to walk-in reactivation requests.');
            }

            $document = $request->documents->firstWhere('id', $documentId)
                ?? throw new DomainException('Document not found for this reactivation request.');

            $documentType = $document->document_type?->value ?? 'document';
            $filename = now()->format('YmdHis') . '-' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs(
                'reactivations/' . $request->application_no . '/office/' . $documentType,
                $filename,
                'public',
            );

            $payload = [
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'uploaded_at' => now(),
            ];

            if ($this->farmerDocumentHasColumn('mime_type')) {
                $payload['mime_type'] = $file->getClientMimeType();
            }

            if ($this->farmerDocumentHasColumn('file_size')) {
                $payload['file_size'] = $file->getSize();
            }

            $document->forceFill($payload)->save();

            return $document->refresh()->load('documentType');
        });
    }

    public function recordPayment(ReactivationRequest $request, array $attributes, ?int $userId = null): array
    {
        return DB::transaction(function () use ($request, $attributes, $userId): array {
            $request->loadMissing(['farmer.memberType', 'documents', 'paymentAssessments.payments']);

            if (! $this->documentVerificationService->allRequiredVerifiedForReactivation($request)) {
                throw new DomainException('All required documents must be verified before recording reactivation payment.');
            }

            if ($request->status === ReactivationStatus::REJECTED) {
                throw new DomainException('Rejected reactivation requests cannot accept payment.');
            }

            $assessment = $this->createOrRefreshAssessment($request);
            $paymentResult = $this->paymentPostingService->record($assessment, $attributes, $userId);
            $summary = $paymentResult['summary'];

            if (in_array($summary['assessment_status'], [AssessmentStatus::PAID, AssessmentStatus::OVERPAID, AssessmentStatus::WAIVED], true)) {
                $request->forceFill([
                    'status' => ReactivationStatus::COMPLETED,
                    'reviewed_by' => $userId,
                    'reviewed_at' => CarbonImmutable::now(),
                ])->save();

                $ledger = MembershipLedger::query()->firstOrNew([
                    'membership_transaction_id' => $request->id,
                    'year' => $request->year,
                ]);

                $ledger->fill($this->ledgerService->buildFromSource(
                    'reactivation',
                    [
                        'id' => $request->id,
                        'status' => 'Active',
                        'member_type' => $request->farmer->memberType?->code,
                        'year' => $request->year,
                    ],
                    [
                        'fee_schedule_id' => $assessment->fee_schedule_id,
                        'member_type' => $request->farmer->memberType?->code,
                        'mortuary_eligible' => ! in_array($request->farmer->memberType?->code, ['NSC', 'OSC'], true),
                    ],
                    $summary,
                ));
                $ledger->save();

                $request->farmer->forceFill([
                    'membership_status' => \App\Enums\MembershipStatus::ACTIVE->value,
                    'activated_at' => now(),
                    'inactive_at' => null,
                    'inactive_reason' => null,
                ])->save();

                $this->membershipStatusService->activateMembership($request->farmer, $request->year);
            }

            return [
                'reactivation' => $request->refresh()->load(['farmer', 'paymentAssessments.payments']),
                'assessment' => $assessment->refresh(),
                'payment' => $paymentResult['payment'],
                'summary' => $summary,
            ];
        });
    }

    public function createOrRefreshAssessment(ReactivationRequest $request): PaymentAssessment
    {
        $request->loadMissing('farmer.memberType');
        $breakdown = $this->assessmentBreakdown($request);
        $feeSchedule = $this->paymentAssessmentService->resolveFeeSchedule($request->farmer->member_type_id, $request->year);

        return PaymentAssessment::query()->updateOrCreate(
            ['membership_transaction_id' => $request->id],
            [
                'fee_schedule_id' => $feeSchedule->id,
                'total_amount_due' => $breakdown['total_amount_due'],
                'membership_fee' => 0,
                'annual_due' => $breakdown['annual_due'],
                'mortuary_fee' => $breakdown['mortuary_fee'],
                'status' => AssessmentStatus::PENDING,
            ],
        );
    }

    public function assessmentBreakdown(ReactivationRequest $request): array
    {
        $request->loadMissing('farmer.memberType');
        $years = $this->arrearsYears($request->farmer, (int) $request->year);
        $annualDue = 0.0;
        $mortuaryFee = 0.0;

        foreach ($years as $year) {
            $schedule = $this->paymentAssessmentService->resolveFeeSchedule($request->farmer->member_type_id, $year);
            $annualDue += round((float) $schedule->annual_due, 2);

            if (! in_array($request->farmer->memberType?->code, ['NSC', 'OSC'], true)) {
                $mortuaryFee += round((float) $schedule->mortuary_fee, 2);
            }
        }

        return [
            'arrears_years' => $years,
            'annual_due' => round($annualDue, 2),
            'mortuary_fee' => round($mortuaryFee, 2),
            'total_amount_due' => round($annualDue + $mortuaryFee, 2),
        ];
    }

    public function arrearsYears(Farmer $farmer, ?int $reactivationYear = null): array
    {
        $farmer->loadMissing('membershipLedgers');
        $year = $reactivationYear ?? now()->year;

        $lastSettledYear = MembershipLedger::query()
            ->whereHas('membershipTransaction', fn ($query) => $query->where('farmer_id', $farmer->id))
            ->where('amount_paid', '>', 0)
            ->max('year');

        $baseYear = $lastSettledYear !== null
            ? ((int) $lastSettledYear + 1)
            : (int) optional($farmer->inactive_at ?? $farmer->registered_at ?? now())->format('Y');

        if ($baseYear > $year) {
            $baseYear = $year;
        }

        return range($baseYear, $year);
    }

    private function farmerDocumentHasColumn(string $column): bool
    {
        if ($this->farmerDocumentColumns === null) {
            $this->farmerDocumentColumns = Schema::getColumnListing('farmer_documents');
        }

        return in_array($column, $this->farmerDocumentColumns, true);
    }

    private function generateRequestNumber(): string
    {
        return 'REA-' . now()->format('Y') . '-' . Str::upper(Str::random(6));
    }
}
