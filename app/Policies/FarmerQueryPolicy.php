<?php

namespace App\Policies;

use App\Models\Query;
use App\Models\User;

class FarmerQueryPolicy
{
    public function view(User $user, Query $query): bool
    {
        return $user->farmer_id !== null
            && (int) $user->farmer_id === (int) $query->farmer_id;
    }

    public function reply(User $user, Query $query): bool
    {
        return $this->view($user, $query);
    }
}
