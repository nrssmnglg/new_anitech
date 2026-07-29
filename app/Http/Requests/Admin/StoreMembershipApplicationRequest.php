<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class StoreMembershipApplicationRequest extends StoreFarmerRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        $rules['birth_date'] = ['nullable', 'date', 'before_or_equal:today'];
        $rules['source'] = ['required', Rule::in(['walk_in'])];
        $rules['application_remarks'] = ['nullable', 'string'];
        $rules['reapply_from_application_id'] = ['nullable', 'integer', 'exists:membership_applications,id'];
        $rules['documents'] = ['nullable', 'array'];
        $rules['documents.*.is_received'] = ['nullable', 'boolean'];

        return $rules;
    }

    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'birth_date' => 'birth date',
            'application_remarks' => 'application remarks',
        ]);
    }
}

