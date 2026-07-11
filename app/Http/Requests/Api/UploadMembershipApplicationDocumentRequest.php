<?php

namespace App\Http\Requests\Api;

use App\Enums\DocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadMembershipApplicationDocumentRequest extends JsonFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'birth_date' => ['required', 'date'],
            'document_type' => ['required', Rule::in(DocumentType::values())],
            'document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ];
    }
}
