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
            'code' => filled($this->input('code')) ? trim((string) $this->input('code')) : null,
            'president_name' => filled($this->input('president_name')) ? trim((string) $this->input('president_name')) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'barangay_id' => ['required', 'exists:barangays,id', 'unique:associations,barangay_id'],
            'name' => ['required', 'string', 'max:255', 'unique:associations,name'],
            'code' => ['nullable', 'string', 'max:50', 'unique:associations,code'],
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
