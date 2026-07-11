<?php

namespace App\Services\Notifications;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NotificationDeliveryService
{
    public function processQueued(int $limit = 50): array
    {
        $notifications = DB::table('notifications')
            ->where('status', 'queued')
            ->orderBy('queued_at')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $processed = 0;
        $delivered = 0;
        $failed = 0;

        foreach ($notifications as $notification) {
            $result = $this->processNotification((int) $notification->id);
            $processed++;
            $delivered += $result['delivered'];
            $failed += $result['failed'];
        }

        return [
            'processed' => $processed,
            'delivered' => $delivered,
            'failed' => $failed,
        ];
    }

    public function processNotification(int $notificationId): array
    {
        return DB::transaction(function () use ($notificationId): array {
            $notification = DB::table('notifications')
                ->where('id', $notificationId)
                ->lockForUpdate()
                ->first();

            if ($notification === null || $notification->status !== 'queued') {
                return ['delivered' => 0, 'failed' => 0];
            }

            $recipients = DB::table('notification_recipients')
                ->where('notification_id', $notificationId)
                ->lockForUpdate()
                ->get();

            $delivered = 0;
            $failed = 0;
            $now = now();

            foreach ($recipients as $recipient) {
                if ($recipient->status !== 'pending') {
                    continue;
                }

                $address = trim((string) ($recipient->recipient_address ?? ''));

                if ($address === '') {
                    DB::table('notification_recipients')
                        ->where('id', $recipient->id)
                        ->update([
                            'status' => 'failed',
                            'failed_at' => $now,
                            'failure_reason' => 'Recipient address is missing.',
                            'updated_at' => $now,
                        ]);
                    $failed++;
                    continue;
                }

                DB::table('notification_recipients')
                    ->where('id', $recipient->id)
                    ->update([
                        'status' => 'delivered',
                        'delivered_at' => $now,
                        'failed_at' => null,
                        'failure_reason' => null,
                        'updated_at' => $now,
                    ]);
                $delivered++;
            }

            $recipientStatuses = DB::table('notification_recipients')
                ->where('notification_id', $notificationId)
                ->pluck('status');

            DB::table('notifications')
                ->where('id', $notificationId)
                ->update([
                    'status' => $this->resolveNotificationStatus($recipientStatuses),
                    'sent_at' => $now,
                    'updated_at' => $now,
                ]);

            return [
                'delivered' => $delivered,
                'failed' => $failed,
            ];
        });
    }

    private function resolveNotificationStatus(Collection $statuses): string
    {
        $unique = $statuses->filter()->unique()->values();

        if ($unique->contains('pending')) {
            return 'queued';
        }

        if ($unique->isNotEmpty() && $unique->every(fn (string $status): bool => $status === 'failed')) {
            return 'failed';
        }

        if ($unique->contains('failed')) {
            return 'sent';
        }

        return 'sent';
    }
}
