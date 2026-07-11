<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreRenewalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'farmer_id' => ['required', 'exists:farmers,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:' . (now()->year + 1)],
            'remarks' => ['nullable', 'string'],
            'documents' => ['array'],
            'documents.*.is_received' => ['nullable', 'boolean'],
        ];
    }
}
