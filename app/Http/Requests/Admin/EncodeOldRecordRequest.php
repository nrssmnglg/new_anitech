<?php

namespace App\Http\Requests\Admin;

use App\Models\Farmer;
use Illuminate\Validation\Rule;

class EncodeOldRecordRequest extends StoreFarmerRequest
{
    public function rules(): array
    {
        $existing = $this->input('record_mode') === 'existing';
        $rules = $existing ? [] : parent::rules();
        return array_merge($rules, [
            'record_mode' => ['required', Rule::in(['new', 'existing'])],
            'farmer_id' => [Rule::requiredIf($existing && ! $this->filled('existing_farmer_code')), 'nullable', 'integer', 'exists:farmers,id'],
            'existing_farmer_code' => ['nullable', 'string', 'max:100', 'exists:farmers,farmer_code'],
            'historical_year' => ['required', 'integer', 'min:1900', 'max:' . now()->year],
            'member_type_id' => ['required', Rule::exists('member_types', 'id')->whereIn('code', $existing ? ['OM', 'OSC'] : ['OM', 'OSC', 'NM', 'NSC'])],
            // Historical registration is derived from historical_year as February 14.
            'registered_at' => ['exclude'],
        ]);
    }

    public function after(): array
    {
        return [
            ...($this->input('record_mode') === 'new' ? parent::after() : []),
            function ($validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }
                if ($this->input('record_mode') === 'new' && ($this->filled('farmer_id') || $this->filled('existing_farmer_code'))) {
                    $validator->errors()->add('farmer_id', 'Use Existing farmer to record another year for this farmer.');
                }
                if ($this->filled('farmer_id') && $this->filled('existing_farmer_code') && ! Farmer::query()
                    ->whereKey($this->input('farmer_id'))->where('farmer_code', $this->input('existing_farmer_code'))->exists()) {
                    $validator->errors()->add('farmer_id', 'The farmer code does not match the selected farmer.');
                }
            },
        ];
    }

    protected function validatesLegacyRenewalSetup(): bool
    {
        return false;
    }
}
