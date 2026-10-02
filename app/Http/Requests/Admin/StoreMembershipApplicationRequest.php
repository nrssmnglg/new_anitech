<?php

namespace App\Http\Requests\Admin;

use App\Enums\MemberTypeCode;
use App\Models\MemberType;
use Illuminate\Validation\Rule;

class StoreMembershipApplicationRequest extends StoreFarmerRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        $rules['birth_date'] = ['nullable', 'date', 'before_or_equal:today'];
        $rules['source'] = ['required', Rule::in(['walk_in'])];
        $rules['application_remarks'] = ['nullable', 'string'];
        $rules['reapply_from_application_id'] = ['nullable', 'integer', 'exists:membership_transactions,id'];
        $rules['documents'] = ['nullable', 'array'];
        $rules['documents.*.is_received'] = ['nullable', 'boolean'];

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if (! $this->filled('member_type_id') && $this->filled('reapply_from_application_id')) {
            $farmer = \App\Models\MembershipApplication::query()
                ->whereKey($this->input('reapply_from_application_id'))
                ->with('farmer')
                ->first()?->farmer;

            if ($farmer?->member_type_id) {
                $this->merge(['member_type_id' => $farmer->member_type_id]);
            }
        }

        if (! $this->filled('member_type_id') || ! MemberType::query()->whereKey($this->input('member_type_id'))->exists()) {
            $type = MemberTypeCode::NM;
            $default = MemberType::query()->firstOrCreate(
                ['code' => $type->value],
                [
                    'name' => $type->label(),
                    'requires_membership_fee' => true,
                    'mortuary_eligible' => true,
                    'status' => 'Active',
                ],
            );

            $this->merge(['member_type_id' => $default->id]);
        }
    }

    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'birth_date' => 'birth date',
            'application_remarks' => 'application remarks',
        ]);
    }

    protected function validatesLegacyRenewalSetup(): bool
    {
        return false;
    }
}

