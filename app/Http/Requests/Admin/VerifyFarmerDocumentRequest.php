<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyFarmerDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['receive', 'unreceive', 'verify', 'reject'])],
            'remarks' => [Rule::requiredIf(fn () => $this->input('action') === 'reject'), 'nullable', 'string'],
        ];
    }
}