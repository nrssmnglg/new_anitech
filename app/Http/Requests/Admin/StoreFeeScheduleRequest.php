<?php

namespace App\Http\Requests\Admin;

use App\Services\Routing\PublicRouteKeyService;
use Illuminate\Foundation\Http\FormRequest;

class StoreFeeScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'member_type_id' => $this->decodeRouteKey($this->input('member_type_id')),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        return [
            'member_type_id' => ['required', 'exists:member_types,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'membership_fee' => ['required', 'numeric', 'min:0'],
            'annual_due' => ['required', 'numeric', 'min:0'],
            'mortuary_fee' => ['required', 'numeric', 'min:0'],
            'renewal_deadline' => ['required', 'date'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (! $this->filled('member_type_id') || ! $this->filled('year')) {
                return;
            }

            $exists = \App\Models\FeeSchedule::query()
                ->where('member_type_id', $this->integer('member_type_id'))
                ->where('year', $this->integer('year'))
                ->exists();

            if ($exists) {
                $validator->errors()->add('year', 'A fee schedule already exists for this member type and year.');
            }
        });
    }

    private function decodeRouteKey(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return app(PublicRouteKeyService::class)->decode((string) $value);
    }
}
