<?php

namespace App\Console\Commands;

use App\Models\Farmer;
use App\Services\Audit\AuditTrailService;
use App\Services\Notifications\NotificationDispatchService;
use App\Services\Notifications\RenewalReminderService;
use Illuminate\Console\Command;

class SendRenewalReminders extends Command
{
    protected $signature = 'app:send-renewal-reminders {--year=} {--deadline=} {--days-before=5} {--dry-run} {--force}';

    protected $description = 'Queue renewal reminder notifications for active farmers who still need to renew.';

    public function __construct(
        private readonly RenewalReminderService $renewalReminderService,
        private readonly NotificationDispatchService $notificationDispatchService,
        private readonly AuditTrailService $auditTrailService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $targetYear = (int) ($this->option('year') ?: now()->year);
        $deadline = $this->option('deadline');
        $daysBefore = max(0, (int) $this->option('days-before'));
        $force = (bool) $this->option('force');

        $this->renewalReminderService->syncInactiveLapsedFarmers($targetYear);
        $recipients = $this->renewalReminderService
            ->eligibleFarmerQuery($targetYear)
            ->with([
                'profile:farmer_id,mobile_number',
                'users:id,farmer_id,email',
                'membershipLedgers:id,membership_transaction_id,year,amount_paid',
            ])
            ->get()
            ->map(fn (Farmer $farmer): array => $this->renewalReminderService->reminderRecipient($farmer))
            ->all();

        $notification = $this->renewalReminderService->queueReminders(
            $recipients,
            $targetYear,
            $deadline ?: null,
            $daysBefore,
            $force,
        );
        $recipientCount = count($notification['recipients']);

        if ($recipientCount === 0) {
            $this->info("No farmers are due for renewal reminders for {$targetYear}.");

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info("Dry run: {$recipientCount} renewal reminder(s) would be queued for {$targetYear}.");

            return self::SUCCESS;
        }

        $persisted = $this->notificationDispatchService->persistQueued($notification);

        $this->auditTrailService->record(
            'notifications',
            'renewal_reminders_queued',
            'Queued renewal reminder notifications for farmers due to renew.',
            null,
            null,
            $this->auditTrailService->activityMetadata(
                AuditTrailService::ACTION_PROCESSED,
                'Renewal reminder dispatch',
                [
                    'notification_id' => $persisted['id'] ?? null,
                    'target_year' => $targetYear,
                    'recipient_count' => $recipientCount,
                    'days_before' => $daysBefore,
                    'deadline' => $notification['payload']['deadline'] ?? null,
                    'reminder_date' => $notification['payload']['reminder_date'] ?? null,
                    'force' => $force ? 'Yes' : 'No',
                    'farmer_ids' => collect($notification['recipients'] ?? [])
                        ->pluck('farmer_id')
                        ->filter()
                        ->values()
                        ->all(),
                ],
            ),
        );

        $this->info("Queued {$recipientCount} renewal reminder(s) for {$targetYear}.");

        return self::SUCCESS;
    }
}
