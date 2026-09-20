<?php

namespace App\Http\Requests\Admin;

use App\Enums\FarmerStatus;
use App\Models\Association;
use App\Services\Routing\PublicRouteKeyService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFarmerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['barangay_id', 'association_id', 'member_type_id'] as $key) {
            if (! $this->filled($key)) {
                continue;
            }

            $this->merge([$key => $this->decodeRouteKey($this->input($key))]);
        }
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
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:50'],
            'sex' => ['required', Rule::in(['male', 'female'])],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'civil_status' => ['nullable', Rule::in(['single', 'married', 'widowed', 'separated'])],
            'mobile_number' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'barangay_id' => ['required', 'exists:barangays,id'],
            'association_id' => ['nullable', 'exists:associations,id'],
            'member_type_id' => ['required', 'exists:member_types,id'],
            'status' => ['required', Rule::in(FarmerStatus::values())],
            'registered_at' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string'],
            'confirm_duplicate_override' => ['nullable', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator): void {
                $associationId = $this->input('association_id');
                $barangayId = $this->input('barangay_id');

                if (! $associationId || ! $barangayId) {
                    return;
                }

                $associationMatches = Association::query()
                    ->whereKey($associationId)
                    ->where('barangay_id', $barangayId)
                    ->exists();

                if (! $associationMatches) {
                    $validator->errors()->add('association_id', 'The selected association does not belong to the selected barangay.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'barangay_id' => 'barangay',
            'association_id' => 'association',
            'member_type_id' => 'member type',
            'birth_date' => 'birth date',
            'status' => 'membership status',
        ];
    }
}
