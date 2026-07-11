<?php

namespace App\Http\Requests\Api;

use App\Enums\DocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadRenewalDocumentRequest extends JsonFormRequest
{
    public function authorize(): bool
    {
        return auth('farmer_pwa')->check();
    }

    public function rules(): array
    {
        return [
            'document_type' => ['required', Rule::in(DocumentType::values())],
            'document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ];
    }
}
