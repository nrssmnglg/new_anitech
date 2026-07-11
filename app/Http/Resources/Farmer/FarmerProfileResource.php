<?php

namespace App\Http\Resources\Farmer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FarmerProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profile = $this->profile;

        return [
            'id' => $this->id,
            'farmer_code' => $this->farmer_code,
            'full_name' => $this->full_name,
            'membership_status' => $this->membership_status?->value ?? (string) $this->membership_status,
            'status' => $this->status->value,
            'record_origin' => $this->record_origin,
            'registered_at' => optional($this->registered_at)->toIso8601String(),
            'activated_at' => optional($this->activated_at)->toIso8601String(),
            'inactive_at' => optional($this->inactive_at)->toIso8601String(),
            'inactive_reason' => $this->inactive_reason,
            'barangay' => $this->barangay ? [
                'id' => $this->barangay->id,
                'name' => $this->barangay->name,
            ] : null,
            'association' => $this->association ? [
                'id' => $this->association->id,
                'name' => $this->association->name,
            ] : null,
            'member_type' => $this->memberType ? [
                'id' => $this->memberType->id,
                'code' => $this->memberType->code,
                'name' => $this->memberType->name,
            ] : null,
            'member_type_explanation' => $this->member_type_explanation ?? null,
            'profile_completion' => $this->profile_completion ?? null,
            'data_quality' => $this->data_quality ?? null,
            'profile' => $profile ? [
                'first_name' => $profile->first_name,
                'middle_name' => $profile->middle_name,
                'last_name' => $profile->last_name,
                'suffix' => $profile->suffix,
                'sex' => $profile->sex,
                'civil_status' => $profile->civil_status,
                'birth_date' => optional($profile->birth_date)->toDateString(),
                'address' => $profile->address,
                'mobile_number' => $profile->mobile_number,
            ] : null,
            'editable_fields' => [
                'civil_status' => true,
                'mobile_number' => true,
                'address' => true,
            ],
        ];
    }
}
