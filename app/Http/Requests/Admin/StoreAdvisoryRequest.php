<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdvisoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'audience_type' => ['required', Rule::in(['all', 'barangay', 'group'])],
            'barangay_id' => ['nullable', 'integer', 'exists:barangays,id', 'required_if:audience_type,barangay'],
            'member_type_id' => ['nullable', 'integer', 'exists:member_types,id', 'required_if:audience_type,group'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:10240'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $audienceType = (string) $this->input('audience_type', 'all');

        $this->merge([
            'barangay_id' => $audienceType === 'barangay' ? $this->input('barangay_id') : null,
            'member_type_id' => $audienceType === 'group' ? $this->input('member_type_id') : null,
        ]);
    }
}
