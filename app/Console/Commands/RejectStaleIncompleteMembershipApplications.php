<?php

namespace App\Console\Commands;

use App\Services\Membership\StaleIncompleteApplicationService;
use Illuminate\Console\Command;

class RejectStaleIncompleteMembershipApplications extends Command
{
    protected $signature = 'app:reject-stale-incomplete-membership-applications';

    protected $description = 'Reject incomplete membership applications after seven days without activity';

    public function handle(StaleIncompleteApplicationService $service): int
    {
        $count = $service->rejectInactive();
        $this->info("Rejected {$count} stale incomplete membership application(s).");

        return self::SUCCESS;
    }
}
