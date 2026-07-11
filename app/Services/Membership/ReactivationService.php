<?php

namespace App\Services\Membership;

use App\Enums\ReactivationStatus;
use App\Services\Payments\PaymentAssessmentService;
use Carbon\CarbonImmutable;

class ReactivationService
{
    public function __construct(
        private readonly ReactivationWorkflowService $workflow,
        private readonly PaymentAssessmentService $paymentAssessmentService,
        private readonly MembershipLedgerService $membershipLedgerService,
    ) {
    }

    public function submit(array|object $payload): array
    {
        $data = $this->normalize($payload);
        $status = $this->workflow->submit($data['status'] ?? ReactivationStatus::DRAFT);

        return array_merge($data, [
            'status' => $status->value,
            'submitted_at' => $this->timestamp($data['submitted_at'] ?? null),
        ]);
    }

    public function startReview(array|object $request, ?int $reviewedBy = null): array
    {
        $data = $this->normalize($request);
        $status = $this->workflow->startReview($data['status'] ?? ReactivationStatus::SUBMITTED);

        return array_merge($data, [
            'status' => $status->value,
            'reviewed_by' => $reviewedBy ?? ($data['reviewed_by'] ?? null),
            'reviewed_at' => $this->timestamp($data['reviewed_at'] ?? null),
        ]);
    }

    public function approve(array|object $request, iterable $feeDefinitions = []): array
    {
        $data = $this->normalize($request);
        $status = $this->workflow->approve($data['status'] ?? ReactivationStatus::UNDER_REVIEW);
        $assessment = $this->paymentAssessmentService->assess('reactivation', $data, $feeDefinitions);

        return array_merge($data, [
            'status' => $status->value,
            'payment_assessment' => $assessment,
        ]);
    }

    public function reject(array|object $request, ?string $remarks = null): array
    {
        $data = $this->normalize($request);
        $status = $this->workflow->reject($data['status'] ?? ReactivationStatus::UNDER_REVIEW);

        return array_merge($data, [
            'status' => $status->value,
            'remarks' => $remarks ?? ($data['remarks'] ?? null),
        ]);
    }

    public function complete(array|object $request, array|object $assessment, array|object $paymentSummary = []): array
    {
        $data = $this->normalize($request);
        $status = $this->workflow->complete($data['status'] ?? ReactivationStatus::APPROVED);
        $ledger = $this->membershipLedgerService->buildFromSource('reactivation', $data, $assessment, $paymentSummary);

        return array_merge($data, [
            'status' => $status->value,
            'membership_ledger' => $ledger,
        ]);
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
