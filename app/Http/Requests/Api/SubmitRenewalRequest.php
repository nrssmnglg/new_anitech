<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SubmitRenewalRequest extends JsonFormRequest
{
    public function authorize(): bool
    {
        return auth('farmer_pwa')->check();
    }

    public function rules(): array
    {
        return [
            'year' => ['nullable', 'integer', 'min:2000', 'max:' . (now()->year + 1)],
            'remarks' => ['nullable', 'string'],
        ];
    }
}
