<?php

namespace App\Services\Farmer;

use App\Models\Farmer;

class FarmerMembershipService
{
    public function membership(Farmer $farmer): Farmer
    {
        return $farmer->load([
            'memberType',
            'renewalRequests' => fn ($query) => $query->latest('id')->limit(3),
        ]);
    }
}
