<?php

namespace App\Services\Membership;

use App\Models\Farmer;
use App\Models\MembershipLedger;
use Illuminate\Database\Eloquent\Builder;

class ApplicationAnnualCoverageService
{
    public function query(?Farmer $farmer = null): Builder
    {
        return $this->constrain(MembershipLedger::query())
            ->with('membershipTransaction')
            ->when($farmer, fn (Builder $query) => $query->whereHas('membershipTransaction', fn (Builder $transaction) => $transaction->where('farmer_id', $farmer->id)));
    }

    public function constrain(Builder $query): Builder
    {
        return $query->whereIn($query->qualifyColumn('payment_status'), ['Paid', 'Overpaid', 'Waived'])
            ->whereHas('membershipTransaction', function (Builder $query): void {
                $query->where('transaction_type', 'Application')
                    ->where('status', 'Approved')
                    ->whereHas('paymentAssessments', fn (Builder $assessment) => $assessment->where('annual_due', '>', 0));
            });
    }
}
