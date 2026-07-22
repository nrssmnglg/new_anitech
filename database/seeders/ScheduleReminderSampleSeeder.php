<?php

namespace Database\Seeders;

use App\Enums\NotificationType;
use App\Models\Farmer;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ScheduleReminderSampleSeeder extends Seeder
{
    private const SAMPLE_TAG = 'schedule-reminder-sample-v1';

    public function run(): void
    {
        $now = CarbonImmutable::now()->startOfSecond();
        $targetYear = (int) $now->year;

        $admin = $this->resolveAdminUser($now);
        $recipients = $this->resolveRecipients();

        if ($recipients->count() < 3) {
            throw new RuntimeException('ScheduleReminderSampleSeeder requires at least 3 farmers with linked user accounts. Run DatabaseSeeder or SampleDataSeeder first.');
        }

        DB::transaction(function () use ($admin, $recipients, $now, $targetYear): void {
            $this->purgeExistingSamples();

            $queuedReminderId = DB::table('notifications')->insertGetId([
                'type' => NotificationType::RENEWAL_REMINDER->value,
                'channel' => 'database',
                'subject' => 'Sample Renewal Reminder Queue',
                'message' => "This is a queued renewal reminder for {$targetYear} used to verify the scheduled reminders panel.",
                'payload' => json_encode([
                    'sample_tag' => self::SAMPLE_TAG,
                    'target_year' => $targetYear,
                    'deadline' => CarbonImmutable::create($targetYear, 2, 14)->toDateString(),
                    'days_before' => 5,
                    'reminder_date' => CarbonImmutable::create($targetYear, 2, 9)->toDateString(),
                    'source' => 'sample-seeder',
                ], JSON_THROW_ON_ERROR),
                'status' => 'queued',
                'queued_at' => $now->subMinutes(15),
                'sent_at' => null,
                'created_by' => $admin->id,
                'created_at' => $now->subMinutes(15),
                'updated_at' => $now->subMinutes(15),
            ]);

            foreach ($recipients->take(3)->values() as $index => $recipient) {
                DB::table('notification_recipients')->insert([
                    'notification_id' => $queuedReminderId,
                    'user_id' => $recipient->user_id,
                    'farmer_id' => $recipient->farmer_id,
                    'recipient_address' => $recipient->recipient_address,
                    'status' => $index === 0 ? 'pending' : 'delivered',
                    'delivered_at' => $index === 0 ? null : $now->subMinutes(10 - $index),
                    'read_at' => $index === 2 ? $now->subMinutes(5) : null,
                    'failed_at' => null,
                    'failure_reason' => null,
                    'created_at' => $now->subMinutes(15),
                    'updated_at' => $now->subMinutes(15),
                ]);
            }

            $historyReminderId = DB::table('notifications')->insertGetId([
                'type' => NotificationType::RENEWAL_REMINDER->value,
                'channel' => 'database',
                'subject' => 'Sample Renewal Reminder History',
                'message' => "This seeded reminder shows delivered, read, and failed states for {$targetYear}.",
                'payload' => json_encode([
                    'sample_tag' => self::SAMPLE_TAG,
                    'target_year' => $targetYear,
                    'deadline' => CarbonImmutable::create($targetYear, 2, 14)->toDateString(),
                    'days_before' => 5,
                    'reminder_date' => CarbonImmutable::create($targetYear, 2, 9)->toDateString(),
                    'source' => 'sample-seeder-history',
                ], JSON_THROW_ON_ERROR),
                'status' => 'sent',
                'queued_at' => $now->subDay(),
                'sent_at' => $now->subDay()->addMinutes(8),
                'created_by' => $admin->id,
                'created_at' => $now->subDay(),
                'updated_at' => $now->subDay(),
            ]);

            foreach ($recipients->take(3)->values() as $index => $recipient) {
                $status = ['delivered', 'failed', 'delivered'][$index];

                DB::table('notification_recipients')->insert([
                    'notification_id' => $historyReminderId,
                    'user_id' => $recipient->user_id,
                    'farmer_id' => $recipient->farmer_id,
                    'recipient_address' => $recipient->recipient_address,
                    'status' => $status,
                    'delivered_at' => $status === 'delivered' ? $now->subDay()->addMinutes(12 + $index) : null,
                    'read_at' => $index === 0 ? $now->subDay()->addHours(2) : null,
                    'failed_at' => $status === 'failed' ? $now->subDay()->addMinutes(14) : null,
                    'failure_reason' => $status === 'failed' ? 'Sample SMTP timeout for failed notification testing.' : null,
                    'created_at' => $now->subDay(),
                    'updated_at' => $now->subDay(),
                ]);
            }

            $this->seedSupportingNotifications($admin->id, $recipients, $now);
        });
    }

    private function resolveAdminUser(CarbonImmutable $now): User
    {
        return User::query()->firstOrCreate(
            ['email' => 'admin@anitech.test'],
            [
                'name' => 'AniTech Admin',
                'password' => 'password123',
                'role' => User::ROLE_ADMIN,
                'status' => User::STATUS_ACTIVE,
                'email_verified_at' => $now,
            ],
        );
    }

    private function resolveRecipients(): Collection
    {
        return Farmer::query()
            ->select('farmers.id')
            ->with([
                'users:id,farmer_id,email',
                'profile:farmer_id,mobile_number',
            ])
            ->whereNull('farmers.inactive_at')
            ->orderBy('farmers.id')
            ->get()
            ->map(function (Farmer $farmer): ?object {
                $user = $farmer->users->first();
                $address = $user?->email ?: $farmer->profile?->mobile_number;

                if ($user === null || $address === null) {
                    return null;
                }

                return (object) [
                    'farmer_id' => $farmer->id,
                    'user_id' => $user->id,
                    'recipient_address' => $address,
                ];
            })
            ->filter()
            ->values();
    }

    private function purgeExistingSamples(): void
    {
        $notificationIds = DB::table('notifications')
            ->where('payload', 'like', '%"sample_tag":"' . self::SAMPLE_TAG . '"%')
            ->pluck('id');

        if ($notificationIds->isEmpty()) {
            return;
        }

        DB::table('notification_recipients')->whereIn('notification_id', $notificationIds)->delete();
        DB::table('notifications')->whereIn('id', $notificationIds)->delete();
    }

    private function seedSupportingNotifications(int $adminId, Collection $recipients, CarbonImmutable $now): void
    {
        $types = [
            NotificationType::QUERY_RESPONDED,
            NotificationType::ADVISORY_PUBLISHED,
            NotificationType::PAYMENT_ASSESSED,
        ];

        foreach ($types as $index => $type) {
            $notificationId = DB::table('notifications')->insertGetId([
                'type' => $type->value,
                'channel' => 'database',
                'subject' => 'Sample ' . $type->label(),
                'message' => 'Seeded notification used to verify the admin history feed and farmer notification list.',
                'payload' => json_encode([
                    'sample_tag' => self::SAMPLE_TAG,
                    'module' => $type->value,
                    'sequence' => $index + 1,
                ], JSON_THROW_ON_ERROR),
                'status' => 'sent',
                'queued_at' => $now->subHours(6 + $index),
                'sent_at' => $now->subHours(6 + $index)->addMinutes(2),
                'created_by' => $adminId,
                'created_at' => $now->subHours(6 + $index),
                'updated_at' => $now->subHours(6 + $index),
            ]);

            $recipient = $recipients[$index % $recipients->count()];

            DB::table('notification_recipients')->insert([
                'notification_id' => $notificationId,
                'user_id' => $recipient->user_id,
                'farmer_id' => $recipient->farmer_id,
                'recipient_address' => $recipient->recipient_address,
                'status' => 'delivered',
                'delivered_at' => $now->subHours(6 + $index)->addMinutes(3),
                'read_at' => $index % 2 === 0 ? $now->subHours(5 + $index) : null,
                'failed_at' => null,
                'failure_reason' => null,
                'created_at' => $now->subHours(6 + $index),
                'updated_at' => $now->subHours(6 + $index),
            ]);
        }
    }
}
