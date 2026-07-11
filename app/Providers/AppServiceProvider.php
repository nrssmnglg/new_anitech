<?php

namespace App\Providers;

use App\Models\Payment;
use App\Models\Query;
use App\Models\RenewalRequest;
use App\Policies\FarmerPaymentPolicy;
use App\Policies\FarmerQueryPolicy;
use App\Policies\FarmerRenewalPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Gate::policy(Query::class, FarmerQueryPolicy::class);
        Gate::policy(RenewalRequest::class, FarmerRenewalPolicy::class);
        Gate::policy(Payment::class, FarmerPaymentPolicy::class);
    }
}
