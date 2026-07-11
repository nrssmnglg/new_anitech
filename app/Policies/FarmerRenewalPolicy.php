<?php

namespace App\Policies;

use App\Models\RenewalRequest;
use App\Models\User;

class FarmerRenewalPolicy
{
    public function view(User $user, RenewalRequest $renewal): bool
    {
        return $user->farmer_id !== null
            && (int) $user->farmer_id === (int) $renewal->farmer_id;
    }
}
