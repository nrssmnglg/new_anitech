<?php

namespace App\Services\Farmers;

use App\Enums\ApplicationStatus;
use App\Enums\AssessmentStatus;
use App\Enums\MembershipStatus;
use App\Models\Farmer;
use App\Models\MemberType;
use App\Models\MembershipApplication;
use App\Models\MembershipLedger;
use App\Services\Membership\MemberTypeResolverService;
use App\Services\Membership\MembershipLedgerService;
use App\Services\Payments\PaymentAssessmentService;
use App\Services\Payments\PaymentPostingService;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LegacyMembershipRecorderService
{
    public function __construct(
        private readonly MemberTypeResolverService $memberTypeResolver,
        private readonly PaymentAssessmentService $paymentAssessmentService,
        private readonly PaymentPostingService $paymentPostingService,
        private readonly MembershipLedgerService $membershipLedgerService,
    ) {
    }

    public function syncForOldRecord(Farmer $farmer): Farmer
    {
        return DB::transaction(function () use ($farmer): Farmer {
            $farmer->loadMissing(['profile', 'memberType']);

            $registeredAt = $farmer->registered_at
                ? CarbonImmutable::parse($farmer->registered_at)
                : CarbonImmutable::now();
            $memberTypeCode = $this->resolveHistoricalMemberTypeCode($farmer, $registeredAt);
            $memberType = MemberType::query()
                ->where('code', $memberTypeCode)
                ->first();

            if (! $memberType) {
                throw new DomainException('The required member type for legacy record encoding is missing.');
            }

            if ((int) $farmer->member_type_id !== (int) $memberType->id) {
                $farmer->forceFill([
                    'member_type_id' => $memberType->id,
                ])->save();
            }

            $application = MembershipApplication::query()->firstOrCreate(
                [
                    'farmer_id' => $farmer->id,
                    'transaction_type' => 'Application',
                    'year' => $registeredAt->year,
                    'source' => 'walk_in',
                ],
                [
                    'application_no' => $this->generateLegacyApplicationNumber($registeredAt),
                    'status' => ApplicationStatus::APPROVED,
                    'submitted_at' => $registeredAt->toDateTimeString(),
                    'reviewed_at' => $registeredAt->toDateTimeString(),
                    'is_late' => false,
                ],
            );

            $assessment = $this->paymentAssessmentService->createForApplication($application, [
                'member_type_code' => $memberTypeCode,
                'member_type_id' => $memberType->id,
                'year' => $registeredAt->year,
            ]);

            $hasPayment = $assessment->payments()
                ->whereDate('paid_at', $registeredAt->toDateString())
                ->exists();

            if (! $hasPayment) {
                $paymentResult = $this->paymentPostingService->record($assessment, [
                    'payment_method' => 'cash',
                    'amount_paid' => (float) $assessment->total_amount_due,
                    'paid_at' => $registeredAt->toDateTimeString(),
                    'reference_no' => 'LEGACY-' . $farmer->farmer_code . '-' . $registeredAt->format('Y'),
                ]);

                $summary = $paymentResult['summary'];
            } else {
                $summary = $this->paymentPostingService->postPayments($assessment, $assessment->payments()->get());
                $assessment->forceFill([
                    'status' => $summary['assessment_status'],
                ])->save();
            }

            MembershipLedger::query()->updateOrCreate(
                [
                    'membership_transaction_id' => $application->id,
                    'year' => $registeredAt->year,
                ],
                $this->membershipLedgerService->buildFromSource(
                    'application',
                    [
                        'id' => $application->id,
                        'status' => in_array($summary['assessment_status'], [AssessmentStatus::PAID, AssessmentStatus::OVERPAID, AssessmentStatus::WAIVED], true)
                            ? 'Active'
                            : 'Inactive',
                        'member_type' => $memberTypeCode,
                        'year' => $registeredAt->year,
                    ],
                    [
                        'fee_schedule_id' => $assessment->fee_schedule_id,
                        'member_type' => $memberTypeCode,
                        'mortuary_eligible' => ! in_array($memberTypeCode, ['NSC', 'OSC'], true),
                    ],
                    $summary,
                ),
            );

            if ($farmer->inactive_at === null) {
                $farmer->forceFill([
                    'membership_status' => MembershipStatus::ACTIVE,
                    'activated_at' => $farmer->activated_at ?? $registeredAt->toDateTimeString(),
                ])->save();
            }

            return $farmer->fresh(['profile', 'memberType']);
        });
    }

    private function resolveHistoricalMemberTypeCode(Farmer $farmer, CarbonImmutable $registeredAt): string
    {
        return $this->memberTypeResolver->resolve([
            'birth_date' => optional($farmer->profile)->birth_date?->toDateString(),
            'has_existing_membership' => false,
        ], $registeredAt);
    }

    private function generateLegacyApplicationNumber(CarbonImmutable $registeredAt): string
    {
        return 'APP-' . $registeredAt->format('Y') . '-' . Str::upper(Str::random(6));
    }
}
