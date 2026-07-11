<?php

namespace App\Services\Membership;

use App\Enums\FarmerStatus;
use App\Enums\ReactivationStatus;
use DomainException;

class ReactivationWorkflowService
{
    private const TRANSITIONS = [
        ReactivationStatus::DRAFT->value => [
            ReactivationStatus::SUBMITTED,
            ReactivationStatus::CANCELLED,
        ],
        ReactivationStatus::SUBMITTED->value => [
            ReactivationStatus::UNDER_REVIEW,
            ReactivationStatus::CANCELLED,
        ],
        ReactivationStatus::UNDER_REVIEW->value => [
            ReactivationStatus::APPROVED,
            ReactivationStatus::REJECTED,
        ],
        ReactivationStatus::APPROVED->value => [
            ReactivationStatus::COMPLETED,
        ],
        ReactivationStatus::REJECTED->value => [
            ReactivationStatus::SUBMITTED,
            ReactivationStatus::CANCELLED,
        ],
        ReactivationStatus::COMPLETED->value => [],
        ReactivationStatus::CANCELLED->value => [],
    ];

    public function initialStatus(): ReactivationStatus
    {
        return ReactivationStatus::DRAFT;
    }

    public function canTransition(ReactivationStatus|string $from, ReactivationStatus|string $to): bool
    {
        $from = $this->normalizeStatus($from);
        $to = $this->normalizeStatus($to);

        return in_array($to, self::TRANSITIONS[$from->value], true);
    }

    public function transition(ReactivationStatus|string $from, ReactivationStatus|string $to): ReactivationStatus
    {
        $from = $this->normalizeStatus($from);
        $to = $this->normalizeStatus($to);

        if (! $this->canTransition($from, $to)) {
            throw new DomainException("Reactivation status cannot move from [{$from->value}] to [{$to->value}].");
        }

        return $to;
    }

    public function submit(ReactivationStatus|string $from = ReactivationStatus::DRAFT): ReactivationStatus
    {
        return $this->transition($from, ReactivationStatus::SUBMITTED);
    }

    public function startReview(ReactivationStatus|string $from = ReactivationStatus::SUBMITTED): ReactivationStatus
    {
        return $this->transition($from, ReactivationStatus::UNDER_REVIEW);
    }

    public function approve(ReactivationStatus|string $from = ReactivationStatus::UNDER_REVIEW): ReactivationStatus
    {
        return $this->transition($from, ReactivationStatus::APPROVED);
    }

    public function reject(ReactivationStatus|string $from = ReactivationStatus::UNDER_REVIEW): ReactivationStatus
    {
        return $this->transition($from, ReactivationStatus::REJECTED);
    }

    public function complete(ReactivationStatus|string $from = ReactivationStatus::APPROVED): ReactivationStatus
    {
        return $this->transition($from, ReactivationStatus::COMPLETED);
    }

    public function cancel(ReactivationStatus|string $from): ReactivationStatus
    {
        return $this->transition($from, ReactivationStatus::CANCELLED);
    }

    public function farmerStatusAfterCompletion(): FarmerStatus
    {
        return FarmerStatus::ACTIVE;
    }

    public function farmerStatusBeforeSubmission(): FarmerStatus
    {
        return FarmerStatus::INACTIVE;
    }

    private function normalizeStatus(ReactivationStatus|string $status): ReactivationStatus
    {
        return $status instanceof ReactivationStatus
            ? $status
            : ReactivationStatus::from($status);
    }
}
