<?php

namespace App\Console\Commands;

use App\Services\Notifications\NotificationDeliveryService;
use Illuminate\Console\Command;

class ProcessQueuedNotifications extends Command
{
    protected $signature = 'app:process-queued-notifications {--limit=50}';

    protected $description = 'Process queued notifications and mark recipients as delivered or failed.';

    public function __construct(
        private readonly NotificationDeliveryService $notificationDeliveryService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $result = $this->notificationDeliveryService->processQueued($limit);

        $this->info(sprintf(
            'Processed %d notification(s): %d delivered recipient(s), %d failed recipient(s).',
            $result['processed'],
            $result['delivered'],
            $result['failed'],
        ));

        return self::SUCCESS;
    }
}
