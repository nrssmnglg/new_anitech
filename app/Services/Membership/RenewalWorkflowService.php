<?php

namespace App\Services\Membership;

use App\Enums\FarmerStatus;
use App\Enums\RenewalStatus;
use DomainException;

class RenewalWorkflowService
{
    private const TRANSITIONS = [
        RenewalStatus::DRAFT->value => [
            RenewalStatus::SUBMITTED,
            RenewalStatus::CANCELLED,
        ],
        RenewalStatus::SUBMITTED->value => [
            RenewalStatus::UNDER_REVIEW,
            RenewalStatus::CANCELLED,
        ],
        RenewalStatus::UNDER_REVIEW->value => [
            RenewalStatus::APPROVED,
            RenewalStatus::REJECTED,
        ],
        RenewalStatus::APPROVED->value => [
            RenewalStatus::COMPLETED,
        ],
        RenewalStatus::REJECTED->value => [
            RenewalStatus::SUBMITTED,
            RenewalStatus::CANCELLED,
        ],
        RenewalStatus::COMPLETED->value => [],
        RenewalStatus::CANCELLED->value => [],
    ];

    public function initialStatus(): RenewalStatus
    {
        return RenewalStatus::DRAFT;
    }

    public function canTransition(RenewalStatus|string $from, RenewalStatus|string $to): bool
    {
        $from = $this->normalizeStatus($from);
        $to = $this->normalizeStatus($to);

        return in_array($to, self::TRANSITIONS[$from->value], true);
    }

    public function transition(RenewalStatus|string $from, RenewalStatus|string $to): RenewalStatus
    {
        $from = $this->normalizeStatus($from);
        $to = $this->normalizeStatus($to);

        if (! $this->canTransition($from, $to)) {
            throw new DomainException("Renewal status cannot move from [{$from->value}] to [{$to->value}].");
        }

        return $to;
    }

    public function submit(RenewalStatus|string $from = RenewalStatus::DRAFT): RenewalStatus
    {
        return $this->transition($from, RenewalStatus::SUBMITTED);
    }

    public function startReview(RenewalStatus|string $from = RenewalStatus::SUBMITTED): RenewalStatus
    {
        return $this->transition($from, RenewalStatus::UNDER_REVIEW);
    }

    public function approve(RenewalStatus|string $from = RenewalStatus::UNDER_REVIEW): RenewalStatus
    {
        return $this->transition($from, RenewalStatus::APPROVED);
    }

    public function reject(RenewalStatus|string $from = RenewalStatus::UNDER_REVIEW): RenewalStatus
    {
        return $this->transition($from, RenewalStatus::REJECTED);
    }

    public function complete(RenewalStatus|string $from = RenewalStatus::APPROVED): RenewalStatus
    {
        return $this->transition($from, RenewalStatus::COMPLETED);
    }

    public function cancel(RenewalStatus|string $from): RenewalStatus
    {
        return $this->transition($from, RenewalStatus::CANCELLED);
    }

    public function farmerStatusAfterCompletion(): FarmerStatus
    {
        return FarmerStatus::ACTIVE;
    }

    private function normalizeStatus(RenewalStatus|string $status): RenewalStatus
    {
        return $status instanceof RenewalStatus
            ? $status
            : RenewalStatus::from($status);
    }
}
