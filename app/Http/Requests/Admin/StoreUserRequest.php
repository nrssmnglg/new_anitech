<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use App\Services\Routing\PublicRouteKeyService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $farmerId = $this->input('farmer_id');

        $this->merge([
            'contact_number' => $this->filled('contact_number') ? $this->input('contact_number') : null,
            'farmer_id' => $this->decodeRouteKey($farmerId),
        ]);
    }

    private function decodeRouteKey(mixed $value): mixed
    {
        if (! filled($value) || is_numeric($value)) {
            return $value;
        }

        return app(PublicRouteKeyService::class)->decode((string) $value);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_STAFF, User::ROLE_FARMER])],
            'status' => ['required', Rule::in([User::STATUS_ACTIVE, User::STATUS_INACTIVE])],
            'farmer_id' => ['nullable', 'required_if:role,' . User::ROLE_FARMER, 'integer', 'exists:farmers,id', 'unique:users,farmer_id'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:30'],
        ];
    }
}
