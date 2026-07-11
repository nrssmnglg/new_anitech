<?php

namespace App\Services\Payments;

use App\Enums\AssessmentStatus;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\PaymentAssessment;
use App\Models\PaymentMethod;
use Carbon\CarbonImmutable;

class PaymentPostingService
{
    public function record(PaymentAssessment $assessment, array $attributes, ?int $verifiedBy = null): array
    {
        $paymentMethodCode = strtolower(trim((string) ($attributes['payment_method'] ?? 'cash')));
        $paymentMethod = PaymentMethod::query()->firstOrCreate(
            ['code' => $paymentMethodCode],
            [
                'name' => str($paymentMethodCode)->replace('_', ' ')->title()->value(),
                'is_online' => $paymentMethodCode !== 'cash',
                'status' => 'Active',
            ],
        );

        $assessment->loadMissing('payments');
        $amountPaid = round((float) $attributes['amount_paid'], 2);
        $allocation = $this->allocateBreakdown($assessment, $amountPaid);

        $payment = $assessment->payments()->create([
            'payment_method_id' => $paymentMethod->id,
            'reference_no' => $attributes['reference_no'] ?? null,
            'amount_paid' => $amountPaid,
            'membership_fee' => $allocation['membership_fee'],
            'annual_due' => $allocation['annual_due'],
            'mortuary_fee' => $allocation['mortuary_fee'],
            'paid_at' => $attributes['paid_at'],
            'verified_by' => $verifiedBy,
            'verified_at' => CarbonImmutable::now(),
            'status' => PaymentStatus::VERIFIED,
        ]);

        $summary = $this->postPayments($assessment->refresh(), $assessment->payments()->get());

        $assessment->forceFill([
            'status' => $summary['assessment_status'],
        ])->save();

        return [
            'payment' => $payment,
            'summary' => $summary,
        ];
    }

    public function post(array|object $assessment, array|object $payment): array
    {
        return $this->postPayments($assessment, [$payment]);
    }

    public function postPayments(array|object $assessment, iterable $payments): array
    {
        $normalizedAssessment = $this->normalize($assessment);
        $amountDue = $this->money($normalizedAssessment['amount_due'] ?? $normalizedAssessment['total_amount_due'] ?? $normalizedAssessment['total'] ?? 0);
        $alreadyPaid = $this->money($normalizedAssessment['paid_amount'] ?? 0);
        $newPayments = 0.0;
        $paidAt = null;

        foreach ($payments as $payment) {
            $normalizedPayment = $this->normalize($payment);
            $newPayments += $this->money(
                $normalizedPayment['amount']
                ?? $normalizedPayment['payment_amount']
                ?? $normalizedPayment['amount_paid']
                ?? $normalizedPayment['paid_amount']
                ?? 0
            );
            $paidAt = $this->normalizePaidAt($payment, $normalizedPayment['paid_at'] ?? $paidAt);
        }

        $paidAmount = round($alreadyPaid + $newPayments, 2);
        $balance = round($amountDue - $paidAmount, 2);
        $status = $this->resolveStatus($amountDue, $paidAmount);

        return [
            'amount_due' => $amountDue,
            'paid_amount' => $paidAmount,
            'balance' => $balance,
            'paid_at' => $paidAt,
            'assessment_status' => $status,
            'payment_status' => $this->toPaymentStatus($status),
        ];
    }

    public function resolveStatus(float $amountDue, float $paidAmount): AssessmentStatus
    {
        $amountDue = $this->money($amountDue);
        $paidAmount = $this->money($paidAmount);

        if ($paidAmount <= 0.0) {
            return AssessmentStatus::PENDING;
        }

        if ($paidAmount < $amountDue) {
            return AssessmentStatus::PARTIALLY_PAID;
        }

        if ($paidAmount > $amountDue) {
            return AssessmentStatus::OVERPAID;
        }

        return AssessmentStatus::PAID;
    }

    public function toPaymentStatus(AssessmentStatus $status): PaymentStatus
    {
        return match ($status) {
            AssessmentStatus::PENDING => PaymentStatus::PENDING,
            AssessmentStatus::PARTIALLY_PAID => PaymentStatus::PARTIALLY_PAID,
            AssessmentStatus::PAID => PaymentStatus::PAID,
            AssessmentStatus::OVERPAID => PaymentStatus::OVERPAID,
            AssessmentStatus::WAIVED => PaymentStatus::WAIVED,
            AssessmentStatus::CANCELLED => PaymentStatus::CANCELLED,
        };
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

    private function normalizePaidAt(array|object $payment, mixed $fallback = null): ?string
    {
        $value = $payment instanceof Payment ? $payment->paid_at : $fallback;

        if ($value instanceof \DateTimeInterface) {
            return CarbonImmutable::instance($value)->toDateTimeString();
        }

        if (blank($value)) {
            return null;
        }

        return CarbonImmutable::parse((string) $value)->toDateTimeString();
    }

    private function allocateBreakdown(PaymentAssessment $assessment, float $amountPaid): array
    {
        $remaining = max(0, round($amountPaid, 2));
        $paidByComponent = [
            'membership_fee' => round((float) $assessment->payments->sum('membership_fee'), 2),
            'annual_due' => round((float) $assessment->payments->sum('annual_due'), 2),
            'mortuary_fee' => round((float) $assessment->payments->sum('mortuary_fee'), 2),
        ];
        $targets = [
            'membership_fee' => max(0, round((float) ($assessment->membership_fee ?? 0) - $paidByComponent['membership_fee'], 2)),
            'annual_due' => max(0, round((float) ($assessment->annual_due ?? 0) - $paidByComponent['annual_due'], 2)),
            'mortuary_fee' => max(0, round((float) ($assessment->mortuary_fee ?? 0) - $paidByComponent['mortuary_fee'], 2)),
        ];
        $allocation = [
            'membership_fee' => 0.0,
            'annual_due' => 0.0,
            'mortuary_fee' => 0.0,
        ];

        foreach (['membership_fee', 'annual_due', 'mortuary_fee'] as $component) {
            if ($remaining <= 0) {
                break;
            }

            $portion = min($remaining, $targets[$component]);
            $allocation[$component] = round($portion, 2);
            $remaining = round($remaining - $portion, 2);
        }

        return $allocation;
    }

    private function money(mixed $value): float
    {
        return round((float) $value, 2);
    }
}
