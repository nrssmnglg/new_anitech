<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends JsonFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'birth_date' => ['required', 'date'],
            'payment_method' => ['required', Rule::in(['qrph'])],
            'reference_no' => ['nullable', 'string', 'max:100'],
        ];
    }
}
