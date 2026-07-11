<?php

namespace App\Http\Requests\Admin;

use App\Models\Barangay;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBarangayRequest extends FormRequest
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
        /** @var Barangay|null $barangay */
        $barangay = $this->route('barangay');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('barangays', 'name')->ignore($barangay)],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('barangays', 'code')->ignore($barangay)],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
