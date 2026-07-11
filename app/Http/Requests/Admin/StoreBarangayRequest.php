<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBarangayRequest extends FormRequest
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
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:barangays,name'],
            'code' => ['nullable', 'string', 'max:50', 'unique:barangays,code'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
