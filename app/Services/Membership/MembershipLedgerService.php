<?php

namespace App\Services\Membership;

use App\Enums\MemberTypeCode;
use App\Enums\PaymentStatus;
use Carbon\CarbonImmutable;

class MembershipLedgerService
{
    public function buildFromSource(string $sourceType, array|object $source, array|object $assessment, array|object $paymentSummary = []): array
    {
        $sourceData = $this->normalize($source);
        $assessmentData = $this->normalize($assessment);
        $paymentData = $this->normalize($paymentSummary);
        $memberType = $assessmentData['member_type'] ?? $sourceData['member_type'] ?? null;
        $sourceId = $sourceData['id'] ?? null;
        $paymentStatus = $this->ledgerPaymentStatus($paymentData['payment_status'] ?? $paymentData['assessment_status'] ?? PaymentStatus::PENDING);
        $ledgerStatus = $paymentStatus === 'Paid' ? 'Active' : 'Inactive';

        return [
            'membership_transaction_id' => $sourceId,
            'fee_schedule_id' => $assessmentData['fee_schedule_id'] ?? null,
            'year' => (int) ($sourceData['year'] ?? CarbonImmutable::now()->year),
            'amount_paid' => $this->money($paymentData['paid_amount'] ?? 0),
            'paid_at' => $this->dateTime($paymentData['paid_at'] ?? null),
            'payment_status' => $paymentStatus,
            'mortuary_eligible' => $assessmentData['mortuary_eligible'] ?? $this->mortuaryEligible($memberType),
            'status' => $sourceData['status'] ?? $ledgerStatus,
        ];
    }

    public function applyPayment(array|object $ledger, array|object $paymentSummary): array
    {
        $ledgerData = $this->normalize($ledger);
        $paymentData = $this->normalize($paymentSummary);

        return array_merge($ledgerData, [
            'amount_paid' => $this->money($paymentData['paid_amount'] ?? $ledgerData['amount_paid'] ?? 0),
            'paid_at' => $this->dateTime($paymentData['paid_at'] ?? $ledgerData['paid_at'] ?? null),
            'payment_status' => $this->ledgerPaymentStatus($paymentData['payment_status'] ?? $paymentData['assessment_status'] ?? $ledgerData['payment_status'] ?? PaymentStatus::PENDING),
        ]);
    }

    public function isSettled(array|object $ledger): bool
    {
        $data = $this->normalize($ledger);

        return $this->paymentStatus($data['payment_status'] ?? PaymentStatus::PENDING)->isSettled();
    }

    private function mortuaryEligible(mixed $memberType): bool
    {
        if ($memberType instanceof MemberTypeCode) {
            return ! $memberType->isSenior();
        }

        if (is_string($memberType) && $memberType !== '' && MemberTypeCode::tryFrom($memberType) !== null) {
            return ! MemberTypeCode::from($memberType)->isSenior();
        }

        return false;
    }

    private function paymentStatus(mixed $status): PaymentStatus
    {
        if ($status instanceof PaymentStatus) {
            return $status;
        }

        $value = $status instanceof \BackedEnum ? $status->value : (string) $status;

        return PaymentStatus::from($value);
    }

    private function ledgerPaymentStatus(mixed $status): string
    {
        return match ($this->paymentStatus($status)) {
            PaymentStatus::PAID, PaymentStatus::OVERPAID, PaymentStatus::WAIVED => 'Paid',
            PaymentStatus::PARTIALLY_PAID => 'Partial',
            default => 'Unpaid',
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

    private function money(mixed $value): float
    {
        return round((float) $value, 2);
    }

    private function dateTime(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return CarbonImmutable::instance($value)->toDateTimeString();
        }

        if (blank($value)) {
            return null;
        }

        return CarbonImmutable::parse((string) $value)->toDateTimeString();
    }
}
