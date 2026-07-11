<?php

namespace App\Services\Membership;

use App\Enums\ApplicationStatus;
use DomainException;

class ApplicationWorkflowService
{
    private const TRANSITIONS = [
        ApplicationStatus::DRAFT->value => [
            ApplicationStatus::SUBMITTED,
            ApplicationStatus::CANCELLED,
        ],
        ApplicationStatus::SUBMITTED->value => [
            ApplicationStatus::UNDER_REVIEW,
            ApplicationStatus::CANCELLED,
        ],
        ApplicationStatus::UNDER_REVIEW->value => [
            ApplicationStatus::APPROVED,
            ApplicationStatus::REJECTED,
        ],
        ApplicationStatus::REJECTED->value => [
            ApplicationStatus::SUBMITTED,
            ApplicationStatus::CANCELLED,
        ],
        ApplicationStatus::APPROVED->value => [],
        ApplicationStatus::CANCELLED->value => [],
    ];

    public function initialStatus(): ApplicationStatus
    {
        return ApplicationStatus::DRAFT;
    }

    public function canTransition(ApplicationStatus|string $from, ApplicationStatus|string $to): bool
    {
        $from = $this->normalizeStatus($from);
        $to = $this->normalizeStatus($to);

        return in_array($to, self::TRANSITIONS[$from->value], true);
    }

    public function transition(ApplicationStatus|string $from, ApplicationStatus|string $to): ApplicationStatus
    {
        $from = $this->normalizeStatus($from);
        $to = $this->normalizeStatus($to);

        if (! $this->canTransition($from, $to)) {
            throw new DomainException("Application status cannot move from [{$from->value}] to [{$to->value}].");
        }

        return $to;
    }

    public function submit(ApplicationStatus|string $from = ApplicationStatus::DRAFT): ApplicationStatus
    {
        return $this->transition($from, ApplicationStatus::SUBMITTED);
    }

    public function startReview(ApplicationStatus|string $from = ApplicationStatus::SUBMITTED): ApplicationStatus
    {
        return $this->transition($from, ApplicationStatus::UNDER_REVIEW);
    }

    public function approve(ApplicationStatus|string $from = ApplicationStatus::UNDER_REVIEW): ApplicationStatus
    {
        return $this->transition($from, ApplicationStatus::APPROVED);
    }

    public function reject(ApplicationStatus|string $from = ApplicationStatus::UNDER_REVIEW): ApplicationStatus
    {
        return $this->transition($from, ApplicationStatus::REJECTED);
    }

    public function cancel(ApplicationStatus|string $from): ApplicationStatus
    {
        return $this->transition($from, ApplicationStatus::CANCELLED);
    }

    private function normalizeStatus(ApplicationStatus|string $status): ApplicationStatus
    {
        return $status instanceof ApplicationStatus
            ? $status
            : ApplicationStatus::from($status);
    }
}
