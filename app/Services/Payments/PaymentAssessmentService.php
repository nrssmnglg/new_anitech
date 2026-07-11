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

    public function createForApplication(MembershipApplication $application): PaymentAssessment
    {
        $application->loadMissing('farmer.memberType');
        $memberTypeCode = $application->farmer->memberType?->code;
        $memberTypeId = $application->farmer->member_type_id;

        if (! $memberTypeCode) {
            throw new DomainException('Member type is required before creating a payment assessment.');
        }

        $feeSchedule = $this->resolveFeeSchedule($memberTypeId);

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

    public function createForRenewal(RenewalRequest $renewalRequest): PaymentAssessment
    {
        $renewalRequest->loadMissing('farmer.memberType');
        $memberTypeCode = $renewalRequest->farmer->memberType?->code;
        $memberTypeId = $renewalRequest->farmer->member_type_id;

        if (! $memberTypeCode) {
            throw new DomainException('Member type is required before creating a renewal payment assessment.');
        }

        $feeSchedule = $this->resolveFeeSchedule($memberTypeId);

        $calculation = $this->feeCalculator->calculateRenewal([
            'member_type' => $memberTypeCode,
            'fee_schedule' => $feeSchedule,
        ]);

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

    public function resolveFeeSchedule(?int $memberTypeId = null): FeeSchedule
    {
        return FeeSchedule::query()
            ->when($memberTypeId, fn ($query) => $query->where('member_type_id', $memberTypeId))
            ->where('is_active', true)
            ->orderByDesc('year')
            ->first()
            ?? throw new DomainException('No active fee schedule is available for the selected member type.');
    }
}
