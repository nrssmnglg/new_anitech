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
            'payment_method' => ['required', 'string', 'max:50'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'amount_paid' => ['required', 'numeric', 'min:0.01'],
            'paid_at' => ['required', 'date'],
            'documents' => ['array'],
            'documents.*.is_received' => ['nullable', 'boolean'],
        ];
    }
}
