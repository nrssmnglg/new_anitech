<?php

namespace App\Http\Requests\Admin;

use App\Enums\MembershipApplicationRejectionReason;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewMembershipApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        if ($this->input('action') === 'reject') {
            return $user->hasRole(User::ROLE_ADMIN);
        }

        return $user->hasRole(User::ROLE_ADMIN)
            || $user->hasRole(User::ROLE_STAFF);
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['approve', 'reject'])],
            'remarks' => ['nullable', 'string'],
            'rejection_reason' => [
                Rule::requiredIf(fn () => $this->input('action') === 'reject'),
                'nullable',
                Rule::in(array_map(fn (MembershipApplicationRejectionReason $reason) => $reason->value, MembershipApplicationRejectionReason::cases())),
            ],
            'rejection_details' => [
                Rule::requiredIf(fn () => $this->input('action') === 'reject' && $this->input('rejection_reason') === MembershipApplicationRejectionReason::OTHER->value),
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }
}
