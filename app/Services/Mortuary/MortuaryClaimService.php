<?php

namespace App\Services\Mortuary;

use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Str;

class MortuaryClaimService
{
    public function __construct(
        private readonly MortuaryEligibilityService $eligibilityService,
    ) {
    }

    public function file(array|object $payload, array|object|null $membershipLedger = null): array
    {
        $data = $this->normalize($payload);
        $eligibility = $this->eligibilityService->evaluate($data, $membershipLedger, $data['claim_date'] ?? null);

        if (! $eligibility['eligible']) {
            throw new DomainException('Mortuary claim is not eligible: ' . implode(' ', $eligibility['reasons']));
        }

        return array_merge($data, [
            'claim_reference' => $data['claim_reference'] ?? 'MC-' . CarbonImmutable::now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
            'status' => 'pending',
            'claim_date' => $data['claim_date'] ?? CarbonImmutable::now()->toDateString(),
        ]);
    }

    public function approve(array|object $claim, ?int $approvedBy = null): array
    {
        $data = $this->normalize($claim);

        return array_merge($data, [
            'status' => 'released',
            'approved_by' => $approvedBy ?? ($data['approved_by'] ?? null),
            'released_by' => $approvedBy ?? ($data['released_by'] ?? null),
        ]);
    }

    public function reject(array|object $claim, ?string $remarks = null): array
    {
        $data = $this->normalize($claim);

        return array_merge($data, [
            'status' => 'rejected',
            'remarks' => $remarks ?? ($data['remarks'] ?? null),
        ]);
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
