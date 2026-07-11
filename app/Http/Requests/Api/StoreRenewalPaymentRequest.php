<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class StoreRenewalPaymentRequest extends JsonFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', Rule::in(['qrph'])],
            'reference_no' => ['nullable', 'string', 'max:100'],
        ];
    }
}
