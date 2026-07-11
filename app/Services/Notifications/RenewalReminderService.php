<?php

namespace App\Services\Notifications;

use App\Enums\FarmerStatus;
use App\Enums\NotificationType;
use App\Enums\MembershipStatus;
use App\Models\Farmer;
use App\Models\FeeSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class RenewalReminderService
{
    public function __construct(
        private readonly NotificationDispatchService $notificationDispatchService,
    ) {
    }

    public function dueFarmers(iterable $farmers, int $targetYear): array
    {
        $due = [];

        foreach ($farmers as $farmer) {
            $data = $this->normalize($farmer);
            $status = FarmerStatus::tryFrom((string) ($data['status'] ?? ''));
            $lastRenewalYear = isset($data['last_renewal_year']) ? (int) $data['last_renewal_year'] : null;

            if ($status !== FarmerStatus::ACTIVE) {
                continue;
            }

            if ($lastRenewalYear === null || $lastRenewalYear < $targetYear) {
                $due[] = $data;
            }
        }

        return $due;
    }

    public function reminderRecipient(Farmer $farmer): array
    {
        $farmer->loadMissing([
            'profile:farmer_id,mobile_number',
            'users:id,farmer_id,email',
        ]);

        return [
            'farmer_id' => $farmer->id,
            'user_id' => $farmer->users->first()?->id,
            'status' => $farmer->status->value,
            'last_renewal_year' => $this->lastRenewalYear($farmer),
            'recipient_address' => $farmer->users->first()?->email ?: $farmer->profile?->mobile_number,
            'mobile_number' => $farmer->profile?->mobile_number,
        ];
    }

    public function eligibleFarmerQuery(int $targetYear): Builder
    {
        return Farmer::query()
            ->whereNull('inactive_at')
            ->where('membership_status', MembershipStatus::ACTIVE->value)
            ->whereDoesntHave('membershipLedgers', function (Builder $ledgerQuery) use ($targetYear): void {
                $ledgerQuery
                    ->where('membership_ledgers.year', $targetYear)
                    ->where('membership_ledgers.amount_paid', '>', 0);
            });
    }

    public function syncInactiveLapsedFarmers(int $targetYear, int $graceYears = 5): int
    {
        $farmers = Farmer::query()
            ->with([
                'membershipLedgers' => fn ($query) => $query
                    ->select('membership_ledgers.id', 'membership_ledgers.membership_transaction_id', 'membership_ledgers.year', 'membership_ledgers.amount_paid')
                    ->where('amount_paid', '>', 0),
            ])
            ->whereNull('inactive_at')
            ->where('membership_status', MembershipStatus::ACTIVE->value)
            ->get();

        $updated = 0;

        foreach ($farmers as $farmer) {
            $lastRenewalYear = $this->lastRenewalYear($farmer);
            $yearsWithoutRenewal = $lastRenewalYear === null
                ? max(0, $targetYear - (int) optional($farmer->activated_at ?? $farmer->registered_at ?? $farmer->created_at)->format('Y'))
                : max(0, $targetYear - $lastRenewalYear);

            if ($yearsWithoutRenewal < $graceYears) {
                continue;
            }

            $farmer->forceFill([
                'inactive_at' => now(),
                'inactive_reason' => 'No renewal for 5 consecutive years',
            ])->save();

            $updated++;
        }

        return $updated;
    }

    public function queueReminders(
        iterable $farmers,
        int $targetYear,
        ?string $deadline = null,
        int $daysBefore = 5,
        bool $force = false,
    ): array
    {
        $deadlineDate = $this->resolveDeadline($targetYear, $deadline);
        $reminderDate = $deadlineDate->subDays(max(0, $daysBefore));

        if (! $force && ! CarbonImmutable::now()->isSameDay($reminderDate)) {
            return $this->notificationDispatchService->queue(
                NotificationType::RENEWAL_REMINDER,
                [],
                [
                    'subject' => 'Annual Renewal Reminder',
                    'message' => "Renew your membership on or before {$deadlineDate->toDateString()}.",
                    'target_year' => $targetYear,
                    'deadline' => $deadlineDate->toDateString(),
                    'days_before' => $daysBefore,
                    'reminder_date' => $reminderDate->toDateString(),
                ],
            );
        }

        $dueFarmers = $this->dueFarmers($farmers, $targetYear);
        $dueFarmers = $this->withoutExistingReminderRecipients(
            $dueFarmers,
            $targetYear,
            $reminderDate->toDateString(),
        );

        return $this->notificationDispatchService->queue(
            NotificationType::RENEWAL_REMINDER,
            $dueFarmers,
            [
                'subject' => 'Annual Renewal Reminder',
                'message' => "Renew your membership on or before {$deadlineDate->toDateString()}.",
                'target_year' => $targetYear,
                'deadline' => $deadlineDate->toDateString(),
                'days_before' => $daysBefore,
                'reminder_date' => $reminderDate->toDateString(),
            ],
        );
    }

    private function resolveDeadline(int $targetYear, ?string $deadline = null): CarbonImmutable
    {
        if ($deadline !== null) {
            return CarbonImmutable::parse($deadline);
        }

        $schedule = FeeSchedule::query()
            ->where('year', $targetYear)
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->first();

        if ($schedule?->renewal_deadline !== null) {
            return CarbonImmutable::parse($schedule->renewal_deadline);
        }

        return CarbonImmutable::create($targetYear, 2, 14);
    }

    private function withoutExistingReminderRecipients(array $farmers, int $targetYear, string $reminderDate): array
    {
        $farmerIds = collect($farmers)
            ->pluck('farmer_id')
            ->filter()
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        if ($farmerIds->isEmpty()) {
            return $farmers;
        }

        $existingRecipientIds = DB::table('notification_recipients as recipients')
            ->join('notifications', 'notifications.id', '=', 'recipients.notification_id')
            ->where('notifications.type', NotificationType::RENEWAL_REMINDER->value)
            ->where('notifications.payload->target_year', $targetYear)
            ->where('notifications.payload->reminder_date', $reminderDate)
            ->whereIn('recipients.farmer_id', $farmerIds->all())
            ->pluck('recipients.farmer_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        if ($existingRecipientIds === []) {
            return $farmers;
        }

        return array_values(array_filter(
            $farmers,
            fn (array $farmer): bool => ! in_array((int) ($farmer['farmer_id'] ?? 0), $existingRecipientIds, true),
        ));
    }

    private function lastRenewalYear(Farmer $farmer): ?int
    {
        if ($farmer->relationLoaded('membershipLedgers')) {
            $year = $farmer->membershipLedgers
                ->where('amount_paid', '>', 0)
                ->max('year');

            return $year !== null ? (int) $year : null;
        }

        $year = $farmer->membershipLedgers()
            ->where('amount_paid', '>', 0)
            ->max('year');

        return $year !== null ? (int) $year : null;
    }

    private function normalize(array|object $payload): array
    {
        if (is_array($payload)) {
            return $payload;
        }

        if (method_exists($payload, 'toArray')) {
            return $payload->toArray();
        }

        return get_object_vars($payload);
    }
}
