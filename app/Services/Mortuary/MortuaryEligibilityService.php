<?php

namespace App\Services\Mortuary;

use App\Enums\MemberTypeCode;
use App\Enums\PaymentStatus;
use Carbon\CarbonImmutable;

class MortuaryEligibilityService
{
    public function evaluate(array|object $farmer, array|object|null $membershipLedger = null, ?string $claimDate = null): array
    {
        $farmerData = $this->normalize($farmer);
        $ledgerData = $membershipLedger !== null ? $this->normalize($membershipLedger) : null;
        $claimYear = $claimDate !== null ? CarbonImmutable::parse($claimDate)->year : CarbonImmutable::now()->year;
        $reasons = [];

        if ($this->isSenior($farmerData, $ledgerData)) {
            $reasons[] = 'Seniors are not eligible for mortuary.';
        }

        if ($ledgerData === null) {
            $reasons[] = 'No membership ledger found.';
        } else {
            if (! (bool) ($ledgerData['mortuary_eligible'] ?? false)) {
                $reasons[] = 'Ledger is not marked mortuary eligible.';
            }

            $paymentStatus = PaymentStatus::tryFrom((string) ($ledgerData['payment_status'] ?? PaymentStatus::PENDING->value));
            if ($paymentStatus === null || ! $paymentStatus->isSettled()) {
                $reasons[] = 'Membership payment is not settled.';
            }

            if (isset($ledgerData['year']) && (int) $ledgerData['year'] > $claimYear) {
                $reasons[] = 'Ledger year cannot be later than the claim year.';
            }
        }

        if (($farmerData['status'] ?? null) === 'deceased') {
            $reasons[] = 'Farmer record is already marked deceased.';
        }

        return [
            'eligible' => $reasons === [],
            'reasons' => $reasons,
            'claim_year' => $claimYear,
        ];
    }

    public function isEligible(array|object $farmer, array|object|null $membershipLedger = null, ?string $claimDate = null): bool
    {
        return $this->evaluate($farmer, $membershipLedger, $claimDate)['eligible'];
    }

    private function isSenior(array $farmerData, ?array $ledgerData): bool
    {
        foreach ([$farmerData['member_type'] ?? null, $ledgerData['member_type_snapshot'] ?? null] as $candidate) {
            if (is_string($candidate) && MemberTypeCode::tryFrom($candidate)?->isSenior()) {
                return true;
            }
        }

        if (isset($farmerData['age']) && is_numeric($farmerData['age'])) {
            return (int) $farmerData['age'] >= 60;
        }

        if (! empty($farmerData['birth_date'])) {
            return CarbonImmutable::parse((string) $farmerData['birth_date'])->diffInYears(CarbonImmutable::now()) >= 60;
        }

        return false;
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
