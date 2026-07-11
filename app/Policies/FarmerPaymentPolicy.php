<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class FarmerPaymentPolicy
{
    public function view(User $user, Payment $payment): bool
    {
        return $user->farmer_id !== null
            && (int) $user->farmer_id === (int) optional($payment->paymentAssessment?->membershipTransaction)->farmer_id;
    }
}
