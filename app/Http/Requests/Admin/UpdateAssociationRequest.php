<?php

namespace App\Http\Requests\Admin;

use App\Models\Association;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssociationRequest extends FormRequest
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
        /** @var Association|null $association */
        $association = $this->route('association');

        return [
            'barangay_id' => ['required', 'exists:barangays,id', Rule::unique('associations', 'barangay_id')->ignore($association)],
            'name' => ['required', 'string', 'max:255', Rule::unique('associations', 'name')->ignore($association)],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('associations', 'code')->ignore($association)],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    public function messages(): array
    {
        return [
            'barangay_id.unique' => 'This barangay already has an association. Only one association is allowed per barangay.',
        ];
    }
}
