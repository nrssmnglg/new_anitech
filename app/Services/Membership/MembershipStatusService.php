<?php

namespace App\Services\Membership;

use App\Enums\FarmerStatus;
use App\Enums\MembershipStatus;
use App\Models\Farmer;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Schema;

class MembershipStatusService
{
    public function markPendingApplication(Farmer $farmer): Farmer
    {
        $farmer->forceFill([
            'membership_status' => MembershipStatus::PENDING_APPLICATION,
        ])->save();

        return $farmer->refresh();
    }

    public function markPendingDocuments(Farmer $farmer): Farmer
    {
        $farmer->forceFill([
            'membership_status' => MembershipStatus::PENDING_DOCUMENTS,
        ])->save();

        return $farmer->refresh();
    }

    public function markPendingVerification(Farmer $farmer): Farmer
    {
        $farmer->forceFill([
            'membership_status' => MembershipStatus::PENDING_VERIFICATION,
        ])->save();

        return $farmer->refresh();
    }

    public function markPendingPayment(Farmer $farmer): Farmer
    {
        $farmer->forceFill([
            'membership_status' => MembershipStatus::PENDING_PAYMENT,
        ])->save();

        return $farmer->refresh();
    }

    public function activateMembership(Farmer $farmer, ?int $renewalYear = null): Farmer
    {
        $now = CarbonImmutable::now();

        $attributes = [
            'membership_status' => MembershipStatus::ACTIVE,
            'registered_at' => $farmer->registered_at ?? $now,
            'activated_at' => $farmer->activated_at ?? $now,
            'inactive_at' => null,
            'inactive_reason' => null,
        ];

        if (Schema::hasColumn('farmers', 'is_registry_record')) {
            $attributes['is_registry_record'] = true;
        }

        $farmer->forceFill($attributes)->save();

        return $farmer->refresh();
    }

    public function activate(array|object $farmer, ?int $renewalYear = null): array
    {
        $data = $this->normalize($farmer);
        $now = CarbonImmutable::now();

        return array_merge($data, [
            'status' => FarmerStatus::ACTIVE->value,
            'membership_status' => MembershipStatus::ACTIVE->value,
            'registered_at' => $data['registered_at'] ?? $now->toDateTimeString(),
            'activated_at' => $data['activated_at'] ?? $now->toDateTimeString(),
            'inactive_at' => null,
            'inactive_reason' => null,
        ]);
    }

    public function markInactive(array|object $farmer, string $reason = 'No renewal for 5 years'): array
    {
        $data = $this->normalize($farmer);

        return array_merge($data, [
            'status' => FarmerStatus::INACTIVE->value,
            'inactive_at' => CarbonImmutable::now()->toDateTimeString(),
            'inactive_reason' => $reason,
        ]);
    }

    public function reactivate(array|object $farmer, ?int $renewalYear = null): array
    {
        return $this->activate($farmer, $renewalYear);
    }

    public function yearsWithoutRenewal(array|object $farmer, ?int $currentYear = null): int
    {
        $data = $this->normalize($farmer);
        $year = $currentYear ?? CarbonImmutable::now()->year;

        foreach (['activated_at', 'registered_at', 'created_at'] as $field) {
            if (! empty($data[$field])) {
                return max(0, $year - CarbonImmutable::parse((string) $data[$field])->year);
            }
        }

        return 0;
    }

    public function shouldTagInactive(array|object $farmer, int $graceYears = 5, ?int $currentYear = null): bool
    {
        $data = $this->normalize($farmer);
        $status = FarmerStatus::tryFrom((string) ($data['status'] ?? FarmerStatus::PENDING->value));

        if ($status === FarmerStatus::INACTIVE || $status === FarmerStatus::DECEASED) {
            return false;
        }

        return $this->yearsWithoutRenewal($data, $currentYear) >= $graceYears;
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
