<?php

namespace App\Services\Farmer;

use App\Models\Advisory;
use App\Models\Farmer;
use Illuminate\Database\Eloquent\Builder;

class FarmerAdvisoryService
{
    public function queryForFarmer(Farmer $farmer): Builder
    {
        return Advisory::query()
            ->where('status', 'Published')
            ->where(function (Builder $query) use ($farmer): void {
                $query->where('audience_type', 'all')
                    ->orWhere(function (Builder $barangayQuery) use ($farmer): void {
                        $barangayQuery->where('audience_type', 'barangay')
                            ->where('barangay_id', $farmer->barangay_id);
                    });

                if ($farmer->member_type_id !== null) {
                    $query->orWhere(function (Builder $groupQuery) use ($farmer): void {
                        $groupQuery->where('audience_type', 'group')
                            ->where('member_type_id', $farmer->member_type_id);
                    });
                }
            });
    }
}
