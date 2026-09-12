<?php

namespace App\Services\Farmer;

use App\Models\Association;
use App\Models\Barangay;
use App\Models\Farmer;
use App\Models\FarmerProfile;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class FarmerProfileService
{
    public function farmerForUser(?User $user): ?Farmer
    {
        $farmer = $user?->farmer?->load([
            'profile',
            'barangay',
            'association',
            'memberType',
        ]);

        return $this->hydrateFarmer($farmer);
    }

    private function hydrateFarmer(?Farmer $farmer): ?Farmer
    {
        if (! $farmer) {
            return null;
        }

        $farmer->setAttribute('profile_completion', $this->profileCompletion($farmer));
        $farmer->setAttribute('data_quality', $this->dataQuality($farmer));
        $farmer->setAttribute('member_type_explanation', $this->memberTypeExplanation($farmer));

        return $farmer;
    }

    public function updateProfile(Farmer $farmer, array $payload): Farmer
    {
        $validated = Validator::make($payload, [
            'birth_date' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'civil_status' => ['sometimes', 'nullable', 'string', 'max:50'],
            'mobile_number' => ['sometimes', 'nullable', 'string', 'max:20'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
        ])->after(function ($validator) use ($payload): void {
            $mobile = preg_replace('/[\s-]/', '', (string) ($payload['mobile_number'] ?? ''));

            if ($mobile !== '' && preg_match('/^(09|\+639)\d{9}$/', $mobile) !== 1) {
                $validator->errors()->add('mobile_number', 'Use a valid mobile number such as 09171234567 or +639171234567.');
            }

            if (filled($payload['address'] ?? null) && str_word_count(trim((string) $payload['address'])) < 2) {
                $validator->errors()->add('address', 'Enter a more complete address so staff can review it properly.');
            }
        })->validate();

        $profile = $farmer->profile ?? new FarmerProfile(['farmer_id' => $farmer->id]);
        $profile->fill($validated);
        $profile->save();

        return $this->hydrateFarmer($farmer->fresh()->load([
            'profile',
            'barangay',
            'association',
            'memberType',
        ]));
    }

    public function options(): array
    {
        return [
            'civil_statuses' => ['Single', 'Married', 'Widowed', 'Separated'],
            'barangays' => Barangay::query()->orderBy('name')->get(['id', 'name'])->all(),
            'associations' => Association::query()->orderBy('name')->get(['id', 'barangay_id', 'name'])->all(),
        ];
    }

    private function profileCompletion(Farmer $farmer): array
    {
        $profile = $farmer->profile;
        $checks = [
            'birth_date' => filled($profile?->birth_date),
            'mobile_number' => filled($profile?->mobile_number),
            'address' => filled($profile?->address) && str_word_count(trim((string) $profile?->address)) >= 2,
            'civil_status' => filled($profile?->civil_status),
            'barangay' => $farmer->barangay_id !== null,
            'association' => $farmer->association_id !== null,
        ];

        $completed = collect($checks)->filter()->count();
        $total = count($checks);

        return [
            'score' => $total > 0 ? (int) round(($completed / $total) * 100) : 0,
            'completed' => $completed,
            'total' => $total,
            'missing_fields' => collect($checks)
                ->filter(fn (bool $ok) => ! $ok)
                ->keys()
                ->map(fn (string $field) => str($field)->replace('_', ' ')->title()->toString())
                ->values()
                ->all(),
        ];
    }

    private function dataQuality(Farmer $farmer): array
    {
        $profile = $farmer->profile;
        $warnings = [];

        if (! filled($profile?->birth_date)) {
            $warnings[] = 'Birth date is missing.';
        }

        $mobile = preg_replace('/[\s-]/', '', (string) ($profile?->mobile_number ?? ''));
        if ($mobile !== '' && preg_match('/^(09|\+639)\d{9}$/', $mobile) !== 1) {
            $warnings[] = 'Mobile number format is invalid.';
        }

        if (! filled($profile?->address) || str_word_count(trim((string) $profile?->address)) < 2) {
            $warnings[] = 'Address is incomplete.';
        }

        if ($farmer->association && (int) $farmer->association->barangay_id !== (int) $farmer->barangay_id) {
            $warnings[] = 'Association does not match the assigned barangay.';
        }

        return [
            'has_warnings' => $warnings !== [],
            'warnings' => $warnings,
        ];
    }

    private function memberTypeExplanation(Farmer $farmer): array
    {
        $code = (string) ($farmer->memberType?->code ?? '');

        $explanation = match (strtoupper($code)) {
            'NM' => 'NM means New Member. Your record follows the standard membership fee and review flow for newly registered farmers.',
            'NSC' => 'NSC means New Senior Citizen. Your record follows the senior-citizen membership fee schedule and review rules.',
            default => 'Your member type controls the fee schedule and review rules used for your membership and renewals.',
        };

        return [
            'code' => $code,
            'name' => $farmer->memberType?->name,
            'description' => $explanation,
        ];
    }
}
