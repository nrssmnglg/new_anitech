<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class UpdateAdvisoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'audience_type' => ['required', Rule::in(['all', 'barangay', 'group'])],
            'barangay_id' => ['nullable', 'integer', 'exists:barangays,id', 'required_if:audience_type,barangay'],
            'member_type_id' => ['nullable', 'integer', 'exists:member_types,id', 'required_if:audience_type,group'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'attachments.*.uploaded' => 'One or more attachments could not be uploaded. Please select the file again and retry.',
            'attachments.*.file' => 'The selected attachment is invalid or could not be read.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $files = $this->file('attachments', []);
            $files = $files instanceof UploadedFile ? [$files] : array_values(array_filter($files));

            foreach ($files as $index => $file) {
                if (! $file instanceof UploadedFile || $file->isValid()) {
                    continue;
                }

                Log::warning('Advisory attachment upload failed during update request.', [
                    'user_id' => $this->user()?->id,
                    'ip' => $this->ip(),
                    'advisory_id' => optional($this->route('advisory'))->id,
                    'index' => $index,
                    'original_name' => $file->getClientOriginalName(),
                    'client_mime_type' => $file->getClientMimeType(),
                    'client_size' => $file->getSize(),
                    'upload_error' => $file->getError(),
                    'upload_error_message' => $file->getErrorMessage(),
                ]);
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $audienceType = (string) $this->input('audience_type', 'all');

        $this->merge([
            'barangay_id' => $audienceType === 'barangay' ? $this->input('barangay_id') : null,
            'member_type_id' => $audienceType === 'group' ? $this->input('member_type_id') : null,
        ]);
    }
}
