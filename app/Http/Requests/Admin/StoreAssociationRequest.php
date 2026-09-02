<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssociationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'president_name' => filled($this->input('president_name')) ? trim((string) $this->input('president_name')) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'barangay_id' => ['required', 'exists:barangays,id', 'unique:associations,barangay_id'],
            'name' => ['required', 'string', 'max:255', 'unique:associations,name'],
            'president_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    public function messages(): array
    {
        return [
            'barangay_id.unique' => 'This barangay already has an association. Only one association is allowed per barangay.',
        ];
    }
}
