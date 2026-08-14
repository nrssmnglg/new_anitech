<?php

namespace App\Services\Payments;

use App\Enums\AssessmentStatus;
use App\Models\FeeSchedule;
use App\Models\MembershipApplication;
use App\Models\PaymentAssessment;
use App\Models\RenewalRequest;
use App\Services\Membership\FeeCalculatorService;
use DomainException;

class PaymentAssessmentService
{
    public function __construct(
        private readonly FeeCalculatorService $feeCalculator,
    ) {
    }

    public function createForApplication(MembershipApplication $application, array $context = []): PaymentAssessment
    {
        $application->loadMissing('farmer.memberType');
        $memberTypeCode = $context['member_type_code'] ?? $application->farmer->memberType?->code;
        $memberTypeId = $context['member_type_id'] ?? $application->farmer->member_type_id;
        $year = isset($context['year']) ? (int) $context['year'] : null;

        if (! $memberTypeCode) {
            throw new DomainException('Member type is required before creating a payment assessment.');
        }

        $feeSchedule = $this->resolveFeeSchedule($memberTypeId, $year);

        $calculation = $this->feeCalculator->calculateApplication([
            'member_type' => $memberTypeCode,
            'fee_schedule' => $feeSchedule,
        ]);

        return PaymentAssessment::query()->updateOrCreate(
            [
                'membership_transaction_id' => $application->id,
            ],
            [
                'fee_schedule_id' => $feeSchedule->id,
                'total_amount_due' => $calculation['total'],
                'membership_fee' => $calculation['membership_fee'] ?? 0,
                'annual_due' => $calculation['annual_due'] ?? 0,
                'mortuary_fee' => $calculation['mortuary_fee'] ?? 0,
                'status' => AssessmentStatus::PENDING,
            ],
        );
    }

    public function createForRenewal(RenewalRequest $renewalRequest, array $context = []): PaymentAssessment
    {
        $renewalRequest->loadMissing('farmer.memberType');
        $memberTypeCode = $context['member_type_code'] ?? $renewalRequest->farmer->memberType?->code;
        $memberTypeId = $context['member_type_id'] ?? $renewalRequest->farmer->member_type_id;
        $year = isset($context['year']) ? (int) $context['year'] : null;

        if (! $memberTypeCode) {
            throw new DomainException('Member type is required before creating a renewal payment assessment.');
        }

        $feeSchedule = $this->resolveFeeSchedule($memberTypeId, $year);

        $calculation = $this->calculateRenewalFees(
            $memberTypeCode,
            $feeSchedule,
            (bool) ($context['include_membership_fee'] ?? false),
        );

        return PaymentAssessment::query()->updateOrCreate(
            [
                'membership_transaction_id' => $renewalRequest->id,
            ],
            [
                'fee_schedule_id' => $feeSchedule->id,
                'total_amount_due' => $calculation['total'],
                'membership_fee' => $calculation['membership_fee'] ?? 0,
                'annual_due' => $calculation['annual_due'] ?? 0,
                'mortuary_fee' => $calculation['mortuary_fee'] ?? 0,
                'status' => AssessmentStatus::PENDING,
            ],
        );
    }

    public function resolveFeeSchedule(?int $memberTypeId = null, ?int $year = null): FeeSchedule
    {
        $baseQuery = FeeSchedule::query()
            ->when($memberTypeId, fn ($query) => $query->where('member_type_id', $memberTypeId));

        if ($year !== null) {
            $exactYear = (clone $baseQuery)
                ->where('year', $year)
                ->orderByDesc('is_active')
                ->orderByDesc('id')
                ->first();

            if ($exactYear !== null) {
                return $exactYear;
            }

            $closestPastYear = (clone $baseQuery)
                ->where('year', '<=', $year)
                ->orderByDesc('year')
                ->orderByDesc('is_active')
                ->first();

            if ($closestPastYear !== null) {
                return $closestPastYear;
            }
        }

        return (clone $baseQuery)
            ->where('is_active', true)
            ->orderByDesc('year')
            ->orderByDesc('id')
            ->first()
            ?? $baseQuery
                ->orderByDesc('year')
                ->orderByDesc('is_active')
                ->orderByDesc('id')
                ->first()
            ?? throw new DomainException('No fee schedule is available for the selected member type.');
    }

    private function calculateRenewalFees(
        string $memberTypeCode,
        FeeSchedule $feeSchedule,
        bool $includeMembershipFee,
    ): array
    {
        $context = [
            'member_type' => $memberTypeCode,
            'fee_schedule' => $feeSchedule,
        ];

        return $includeMembershipFee
            ? $this->feeCalculator->calculateApplication($context)
            : $this->feeCalculator->calculateRenewal($context);
    }
}
