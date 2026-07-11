<?php

namespace App\Services\Membership;

use App\Enums\RenewalStatus;
use App\Services\Payments\PaymentAssessmentService;
use Carbon\CarbonImmutable;

class RenewalService
{
    public function __construct(
        private readonly RenewalWorkflowService $workflow,
        private readonly PaymentAssessmentService $paymentAssessmentService,
        private readonly MembershipLedgerService $membershipLedgerService,
    ) {
    }

    public function submit(array|object $payload, array|object|null $feeSchedule = null): array
    {
        $data = $this->normalize($payload);
        $status = $this->workflow->submit($data['status'] ?? RenewalStatus::DRAFT);

        return array_merge($data, [
            'status' => $status->value,
            'submitted_at' => $this->timestamp($data['submitted_at'] ?? null),
            'is_late' => $this->isLate($data, $feeSchedule),
        ]);
    }

    public function startReview(array|object $request, ?int $reviewedBy = null): array
    {
        $data = $this->normalize($request);
        $status = $this->workflow->startReview($data['status'] ?? RenewalStatus::SUBMITTED);

        return array_merge($data, [
            'status' => $status->value,
            'reviewed_by' => $reviewedBy ?? ($data['reviewed_by'] ?? null),
            'reviewed_at' => $this->timestamp($data['reviewed_at'] ?? null),
        ]);
    }

    public function approve(array|object $request, iterable $feeDefinitions = []): array
    {
        $data = $this->normalize($request);
        $status = $this->workflow->approve($data['status'] ?? RenewalStatus::UNDER_REVIEW);
        $assessment = $this->paymentAssessmentService->assess('renewal', $data, $feeDefinitions);

        return array_merge($data, [
            'status' => $status->value,
            'payment_assessment' => $assessment,
        ]);
    }

    public function reject(array|object $request, ?string $remarks = null): array
    {
        $data = $this->normalize($request);
        $status = $this->workflow->reject($data['status'] ?? RenewalStatus::UNDER_REVIEW);

        return array_merge($data, [
            'status' => $status->value,
            'remarks' => $remarks ?? ($data['remarks'] ?? null),
        ]);
    }

    public function complete(array|object $request, array|object $assessment, array|object $paymentSummary = []): array
    {
        $data = $this->normalize($request);
        $status = $this->workflow->complete($data['status'] ?? RenewalStatus::APPROVED);
        $ledger = $this->membershipLedgerService->buildFromSource('renewal', $data, $assessment, $paymentSummary);

        return array_merge($data, [
            'status' => $status->value,
            'membership_ledger' => $ledger,
        ]);
    }

    public function isLate(array|object $request, array|object|null $feeSchedule = null): bool
    {
        $data = $this->normalize($request);
        $submittedAt = isset($data['submitted_at']) && $data['submitted_at'] !== null
            ? CarbonImmutable::parse((string) $data['submitted_at'])
            : CarbonImmutable::now();

        if ($feeSchedule !== null) {
            $schedule = $this->normalize($feeSchedule);
            if (isset($schedule['renewal_deadline']) && $schedule['renewal_deadline'] !== null) {
                return $submittedAt->toDateString() > CarbonImmutable::parse((string) $schedule['renewal_deadline'])->toDateString();
            }
        }

        return $submittedAt->toDateString() > CarbonImmutable::create($submittedAt->year, 2, 14)->toDateString();
    }

    private function timestamp(mixed $value): string
    {
        return $value !== null && $value !== ''
            ? CarbonImmutable::parse((string) $value)->toDateTimeString()
            : CarbonImmutable::now()->toDateTimeString();
    }

    private function normalize(array|object $payload): array
    {
        if (is_array($payload)) {
            return $payload;
        }

        if (method_exists($payload, 'toArray')) {
            return $payload->toArray();
        }

        return get_object_vars($payload);
    }
}
