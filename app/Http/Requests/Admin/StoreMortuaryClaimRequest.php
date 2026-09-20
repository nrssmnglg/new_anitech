<?php

namespace App\Http\Requests\Admin;

use App\Models\DocumentRequirement;
use App\Services\Routing\PublicRouteKeyService;
use Illuminate\Foundation\Http\FormRequest;

class StoreMortuaryClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->decodeRouteKey('membership_ledger_id');
    }

    public function rules(): array
    {
        $rules = [
            'membership_ledger_id' => ['required', 'exists:membership_ledgers,id'],
            'claim_amount' => ['required', 'numeric', 'min:0.01'],
            'claim_date' => ['required', 'date'],
            'claimer_name' => ['required', 'string', 'max:255'],
            'claimer_relationship' => ['required', 'string', 'max:255'],
            'claimer_contact_number' => ['required', 'string', 'max:255'],
            'claimer_address' => ['required', 'string'],
            'requirements' => ['array'],
            'remarks' => ['nullable', 'string'],
        ];

        $requirements = DocumentRequirement::query()
            ->with('documentType:id,code')
            ->where('transaction_type', 'Mortuary')
            ->where('is_active', true)
            ->where('is_required', true)
            ->get();

        foreach ($requirements as $requirement) {
            $code = strtolower((string) $requirement->documentType?->code);

            if ($code === '') {
                continue;
            }

            $rules["requirements.$code.is_received"] = ['accepted'];
        }

        return $rules;
    }

    private function decodeRouteKey(string $field): void
    {
        $value = $this->input($field);

        if ($value === null || $value === '') {
            return;
        }

        if (is_numeric($value)) {
            $this->merge([$field => (int) $value]);

            return;
        }

        $this->merge([
            $field => app(PublicRouteKeyService::class)->decode((string) $value),
        ]);
    }
}
