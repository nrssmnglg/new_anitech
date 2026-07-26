<?php

namespace App\Services\Farmers;

use App\Enums\FarmerStatus;
use App\Enums\MembershipStatus;
use App\Models\Association;
use App\Models\Farmer;
use App\Models\FarmerProfile;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FarmerRegistryService
{
    public function create(array $data): Farmer
    {
        return DB::transaction(function () use ($data): Farmer {
            $farmer = Farmer::create($this->preparePayload($data));
            $this->syncProfile($farmer, $data);

            return $farmer->fresh(['barangay', 'association', 'memberType', 'profile']);
        });
    }

    public function update(Farmer $farmer, array $data): Farmer
    {
        return DB::transaction(function () use ($farmer, $data): Farmer {
            $farmer->update($this->preparePayload($data, $farmer));
            $this->syncProfile($farmer, $data);

            return $farmer->fresh(['barangay', 'association', 'memberType', 'profile']);
        });
    }


    public function delete(Farmer $farmer): void
    {
        DB::transaction(function () use ($farmer): void {
            $farmer->loadCount([
                'documents',
                'membershipApplications',
                'paymentAssessments',
                'membershipLedgers',
                'renewalRequests',
                'reactivationRequests',
                'users',
            ]);

            $hasLinkedRecords = $farmer->documents_count > 0
                || $farmer->membership_applications_count > 0
                || $farmer->payment_assessments_count > 0
                || $farmer->membership_ledgers_count > 0
                || $farmer->renewal_requests_count > 0
                || $farmer->reactivation_requests_count > 0
                || $farmer->users_count > 0;

            if ($hasLinkedRecords) {
                throw new DomainException('This farmer record cannot be deleted because it already has membership or account activity attached to it.');
            }

            $farmer->delete();
        });
    }
    public function previewFarmerCode(): string
    {
        return $this->generateFarmerCode();
    }

    public function findPotentialDuplicates(array $data, ?Farmer $ignore = null, int $limit = 5): Collection
    {
        $normalized = $this->normalizeDuplicatePayload($data);

        if (! $this->canCheckDuplicates($normalized)) {
            return collect();
        }

        $candidates = Farmer::query()
            ->with(['barangay:id,name', 'memberType:id,code,name', 'profile:farmer_id,first_name,last_name,birth_date,mobile_number'])
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))
            ->where(function ($query) use ($normalized): void {
                if ($normalized['mobile_number']) {
                    $query->orWhereHas('profile', function ($profileQuery) use ($normalized): void {
                        $profileQuery->where('mobile_number', $normalized['mobile_number']);
                    });
                }

                if ($normalized['first_name'] && $normalized['last_name'] && $normalized['birth_date']) {
                    $query->orWhereHas('profile', function ($profileQuery) use ($normalized): void {
                        $profileQuery->whereRaw('LOWER(first_name) = ?', [$normalized['first_name']])
                            ->whereRaw('LOWER(last_name) = ?', [$normalized['last_name']])
                            ->whereDate('birth_date', $normalized['birth_date']);
                    });
                }

                if ($normalized['first_name'] && $normalized['last_name'] && $normalized['barangay_id']) {
                    $query->orWhere(function ($nested) use ($normalized): void {
                        $nested->where('barangay_id', $normalized['barangay_id'])
                            ->whereHas('profile', function ($profileQuery) use ($normalized): void {
                                $profileQuery->whereRaw('LOWER(first_name) = ?', [$normalized['first_name']])
                                    ->whereRaw('LOWER(last_name) = ?', [$normalized['last_name']]);
                            });
                    });
                }
            })
            ->limit(15)
            ->get();

        return $candidates
            ->map(function (Farmer $candidate) use ($normalized): ?Farmer {
                $reasons = $this->duplicateReasons($candidate, $normalized);

                if ($reasons === []) {
                    return null;
                }

                $candidate->setAttribute('duplicate_reasons', $reasons);

                return $candidate;
            })
            ->filter()
            ->take($limit)
            ->values();
    }

    public function detectQualityIssues(Farmer $farmer): array
    {
        $profile = $farmer->profile;
        $issues = [];

        if ($this->hasIncompleteProfile($farmer)) {
            $issues[] = 'Incomplete profile';
        }

        if ($this->hasInvalidMobileNumber($profile?->mobile_number)) {
            $issues[] = 'Invalid mobile number';
        }

        if ($this->hasBarangayAssociationMismatch($farmer)) {
            $issues[] = 'Barangay and association mismatch';
        }

        if ($this->needsInactiveReview($farmer)) {
            $issues[] = 'Inactive record needs review';
        }

        if ($this->findPotentialDuplicates([
            'first_name' => $profile?->first_name,
            'last_name' => $profile?->last_name,
            'birth_date' => optional($profile?->birth_date)?->toDateString(),
            'mobile_number' => $profile?->mobile_number,
            'barangay_id' => $farmer->barangay_id,
        ], $farmer, 1)->isNotEmpty()) {
            $issues[] = 'Possible duplicate';
        }

        return $issues;
    }

    public function hasIncompleteProfile(Farmer $farmer): bool
    {
        $profile = $farmer->profile;

        return blank($profile?->first_name)
            || blank($profile?->last_name)
            || blank($profile?->birth_date)
            || blank($profile?->address)
            || blank($profile?->mobile_number)
            || blank($farmer->barangay_id)
            || blank($farmer->member_type_id);
    }

    public function hasInvalidMobileNumber(?string $mobileNumber): bool
    {
        if (blank($mobileNumber)) {
            return false;
        }

        return ! preg_match('/^(09|\+639)\d{9}$/', preg_replace('/\s+/', '', (string) $mobileNumber));
    }

    public function hasBarangayAssociationMismatch(Farmer $farmer): bool
    {
        if (blank($farmer->association_id) || blank($farmer->barangay_id)) {
            return false;
        }

        return ! Association::query()
            ->whereKey($farmer->association_id)
            ->where('barangay_id', $farmer->barangay_id)
            ->exists();
    }

    public function needsInactiveReview(Farmer $farmer): bool
    {
        return $farmer->inactive_at !== null && blank($farmer->inactive_reason);
    }

    private function preparePayload(array $data, ?Farmer $farmer = null): array
    {
        $payload = Arr::except($data, ['has_existing_membership', 'confirm_duplicate_override']);
        $status = FarmerStatus::tryFrom((string) ($payload['status'] ?? FarmerStatus::PENDING->value))
            ?? FarmerStatus::PENDING;
        $registeredAt = $this->normalizeRegisteredAt($payload['registered_at'] ?? null, $farmer);

        $payload['farmer_code'] = $farmer?->farmer_code ?? $payload['farmer_code'] ?? $this->generateFarmerCode();
        $payload['membership_status'] = $this->resolveMembershipStatus($status, $farmer);
        $payload['registered_at'] = $registeredAt;
        $payload['record_origin'] = $payload['record_origin'] ?? $farmer?->record_origin ?? 'Admin';

        if ($status === FarmerStatus::ACTIVE && empty($payload['activated_at'])) {
            $payload['activated_at'] = $farmer?->activated_at?->toDateTimeString() ?? now()->toDateTimeString();
        }

        if ($status !== FarmerStatus::INACTIVE) {
            $payload['inactive_at'] = null;
            $payload['inactive_reason'] = null;
        } elseif (empty($payload['inactive_at'])) {
            $payload['inactive_at'] = $farmer?->inactive_at?->toDateTimeString() ?? now()->toDateTimeString();
        }

        if ($this->shouldRequireReactivation($payload['record_origin'], $registeredAt)) {
            $payload['membership_status'] = MembershipStatus::PENDING_APPLICATION->value;
            $payload['activated_at'] = null;
            $payload['inactive_at'] = $farmer?->inactive_at?->toDateTimeString() ?? now()->toDateTimeString();
            $payload['inactive_reason'] = trim((string) ($payload['inactive_reason'] ?? '')) !== ''
                ? $payload['inactive_reason']
                : 'Old registry record requires reactivation.';
        }

        return Arr::only($payload, [
            'farmer_code',
            'barangay_id',
            'association_id',
            'member_type_id',
            'membership_status',
            'record_origin',
            'registered_at',
            'activated_at',
            'inactive_at',
            'inactive_reason',
        ]);
    }

    private function normalizeRegisteredAt(mixed $value, ?Farmer $farmer = null): string
    {
        if (filled($value)) {
            return Carbon::parse((string) $value)->startOfDay()->toDateTimeString();
        }

        return $farmer?->registered_at?->toDateTimeString() ?? now()->toDateTimeString();
    }

    private function shouldRequireReactivation(string $recordOrigin, string $registeredAt): bool
    {
        if (strcasecmp(trim($recordOrigin), 'Old Record') !== 0) {
            return false;
        }

        return Carbon::parse($registeredAt)->lt(now()->subYears(5)->startOfDay());
    }

    private function resolveMembershipStatus(FarmerStatus $status, ?Farmer $farmer = null): string
    {
        return match ($status) {
            FarmerStatus::ACTIVE => MembershipStatus::ACTIVE->value,
            FarmerStatus::INACTIVE => MembershipStatus::PENDING_APPLICATION->value,
            default => $farmer?->membership_status?->value ?? MembershipStatus::PENDING_APPLICATION->value,
        };
    }

    private function duplicateReasons(Farmer $candidate, array $normalized): array
    {
        $reasons = [];
        $profile = $candidate->profile;

        if (
            $normalized['mobile_number']
            && $profile?->mobile_number
            && trim((string) $profile->mobile_number) === $normalized['mobile_number']
        ) {
            $reasons[] = 'Same mobile number';
        }

        if (
            $normalized['first_name']
            && $normalized['last_name']
            && mb_strtolower((string) $profile?->first_name) === $normalized['first_name']
            && mb_strtolower((string) $profile?->last_name) === $normalized['last_name']
        ) {
            if ($normalized['birth_date'] && optional($profile?->birth_date)?->toDateString() === $normalized['birth_date']) {
                $reasons[] = 'Same first name, last name, and birth date';
            }

            if ($normalized['barangay_id'] && (int) $candidate->barangay_id === (int) $normalized['barangay_id']) {
                $reasons[] = 'Same first name, last name, and barangay';
            }
        }

        return array_values(array_unique($reasons));
    }

    private function normalizeDuplicatePayload(array $data): array
    {
        return [
            'first_name' => $this->normalizeText($data['first_name'] ?? null),
            'last_name' => $this->normalizeText($data['last_name'] ?? null),
            'birth_date' => filled($data['birth_date'] ?? null) ? (string) $data['birth_date'] : null,
            'mobile_number' => filled($data['mobile_number'] ?? null) ? trim((string) $data['mobile_number']) : null,
            'barangay_id' => filled($data['barangay_id'] ?? null) ? (int) $data['barangay_id'] : null,
        ];
    }

    private function canCheckDuplicates(array $normalized): bool
    {
        return (bool) $normalized['mobile_number']
            || ($normalized['first_name'] && $normalized['last_name'] && $normalized['birth_date'])
            || ($normalized['first_name'] && $normalized['last_name'] && $normalized['barangay_id']);
    }

    private function normalizeText(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_strtolower($value);
    }

    private function generateFarmerCode(): string
    {
        $prefix = 'FRM-' . now()->format('Y') . '-';
        $lastCode = Farmer::query()
            ->where('farmer_code', 'like', $prefix . '%')
            ->orderByDesc('farmer_code')
            ->value('farmer_code');

        $nextNumber = 1;

        if ($lastCode !== null) {
            $segment = (int) str($lastCode)->afterLast('-')->value();
            $nextNumber = $segment + 1;
        }

        do {
            $code = $prefix . str_pad((string) $nextNumber, 5, '0', STR_PAD_LEFT);
            $nextNumber++;
        } while (Farmer::query()->where('farmer_code', $code)->exists());

        return $code;
    }

    private function syncProfile(Farmer $farmer, array $data): void
    {
        $sex = filled($data['sex'] ?? null)
            ? ucfirst(strtolower((string) $data['sex']))
            : null;
        $civilStatus = filled($data['civil_status'] ?? null)
            ? ucfirst(strtolower((string) $data['civil_status']))
            : null;

        FarmerProfile::query()->updateOrCreate(
            ['farmer_id' => $farmer->id],
            [
                'first_name' => $data['first_name'] ?? null,
                'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'suffix' => $data['suffix'] ?? null,
                'sex' => $sex,
                'civil_status' => $civilStatus,
                'birth_date' => $data['birth_date'] ?? null,
                'address' => $data['address'] ?? null,
                'mobile_number' => $data['mobile_number'] ?? null,
            ],
        );
    }
}
