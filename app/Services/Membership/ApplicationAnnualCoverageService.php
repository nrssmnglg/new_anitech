<?php

namespace App\Services\Membership;

use App\Models\Farmer;
use App\Models\MembershipLedger;
use Illuminate\Database\Eloquent\Builder;

class ApplicationAnnualCoverageService
{
    public function query(Farmer $farmer): Builder
    {
        return MembershipLedger::query()
            ->with('membershipTransaction')
            ->whereIn('payment_status', ['Paid', 'Overpaid', 'Waived'])
            ->whereHas('membershipTransaction', function (Builder $query) use ($farmer): void {
                $query->where('farmer_id', $farmer->id)
                    ->where('transaction_type', 'Application')
                    ->where('status', 'Approved')
                    ->whereHas('paymentAssessments', fn (Builder $assessment) => $assessment->where('annual_due', '>', 0));
            });
    }
}
