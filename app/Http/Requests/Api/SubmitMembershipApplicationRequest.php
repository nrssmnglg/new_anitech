<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitMembershipApplicationRequest extends JsonFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'suffix' => ['nullable', 'string', 'max:30'],
            'birth_date' => ['required', 'date', 'before_or_equal:today'],
            'sex' => ['nullable', Rule::in(['male', 'female'])],
            'civil_status' => ['nullable', Rule::in(['single', 'married', 'widowed', 'separated'])],
            'mobile_number' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'barangay_id' => ['required', 'integer', 'exists:barangays,id'],
            'association_id' => ['nullable', 'integer', 'exists:associations,id'],
            'remarks' => ['nullable', 'string'],
            'reapply_from_application_id' => ['nullable', 'integer', 'exists:membership_applications,id'],
        ];
    }
}

