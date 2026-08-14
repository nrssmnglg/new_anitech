<?php

namespace App\Services\Farmers;

use App\Enums\ApplicationStatus;
use App\Enums\AssessmentStatus;
use App\Enums\MembershipStatus;
use App\Models\Farmer;
use App\Models\MemberType;
use App\Models\MembershipApplication;
use App\Models\MembershipLedger;
use App\Services\Membership\RenewalRequestService;
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
        private readonly RenewalRequestService $renewalRequestService,
    ) {
    }

    public function syncForOldRecord(Farmer $farmer, ?int $renewalYear = null): Farmer
    {
        return DB::transaction(function () use ($farmer, $renewalYear): Farmer {
            $farmer->loadMissing(['profile', 'memberType']);

            $registeredAt = $farmer->registered_at
                ? CarbonImmutable::parse($farmer->registered_at)
                : CarbonImmutable::now();
            $registryState = [
                'membership_status' => $farmer->membership_status,
                'activated_at' => $farmer->activated_at,
                'inactive_at' => $farmer->inactive_at,
                'inactive_reason' => $farmer->inactive_reason,
            ];
            $memberTypeCode = $farmer->memberType?->code ?: $this->resolveHistoricalMemberTypeCode($farmer, $registeredAt);
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

            $renewalCoversRegistrationYear = $renewalYear !== null
                && $renewalYear === $registeredAt->year;

            if (! $renewalCoversRegistrationYear) {
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
                        'reference_no' => 'LEGACY-APP-' . $farmer->farmer_code . '-' . $registeredAt->format('Y'),
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
            }

            if ($renewalYear !== null) {
                if ($renewalYear < $registeredAt->year) {
                    throw new DomainException('Renewal year must be the registered year or later.');
                }

                $renewalRecordedAt = $this->renewalRecordedAt($registeredAt, $renewalYear);
                $renewalAssessmentContext = [
                    'member_type_code' => $memberTypeCode,
                    'member_type_id' => $memberType->id,
                    'year' => $renewalYear,
                    'include_membership_fee' => $renewalCoversRegistrationYear,
                ];
                $renewal = $this->renewalRequestService->createForLegacyRecord([
                    'farmer_id' => $farmer->id,
                    'year' => $renewalYear,
                    'source' => 'walk_in',
                    'submitted_at' => $renewalRecordedAt->toDateTimeString(),
                    'reviewed_at' => $renewalRecordedAt->toDateTimeString(),
                ], $renewalAssessmentContext);

                $assessment = $renewal->paymentAssessments->sortByDesc('id')->first()
                    ?? $this->paymentAssessmentService->createForRenewal($renewal, $renewalAssessmentContext);

                $this->renewalRequestService->recordPayment($renewal, [
                    'payment_method' => 'cash',
                    'amount_paid' => (float) $assessment->total_amount_due,
                    'paid_at' => $renewalRecordedAt->toDateTimeString(),
                    'reference_no' => 'LEGACY-REN-' . $farmer->farmer_code . '-' . $renewalYear,
                ], null, $renewalAssessmentContext);

                $farmer->refresh()->forceFill($registryState)->save();
            }

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

    private function renewalRecordedAt(CarbonImmutable $registeredAt, int $renewalYear): CarbonImmutable
    {
        $lastDayOfMonth = CarbonImmutable::create($renewalYear, $registeredAt->month, 1)->daysInMonth;

        return $registeredAt->setDate(
            $renewalYear,
            $registeredAt->month,
            min($registeredAt->day, $lastDayOfMonth),
        );
    }
}
