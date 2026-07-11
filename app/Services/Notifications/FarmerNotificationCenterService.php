<?php

namespace App\Services\Notifications;

use App\Enums\NotificationType;
use App\Models\Farmer;
use Illuminate\Support\Facades\DB;

class FarmerNotificationCenterService
{
    public function markModuleRead(Farmer $farmer, string $module, ?int $userId = null): int
    {
        $query = DB::table('notification_recipients as recipients')
            ->join('notifications', 'notifications.id', '=', 'recipients.notification_id')
            ->whereNull('recipients.read_at')
            ->where(function ($builder) use ($farmer, $userId): void {
                $builder->where('recipients.farmer_id', $farmer->id);

                if ($userId !== null) {
                    $builder->orWhere('recipients.user_id', $userId);
                }
            });

        $types = $this->moduleTypes($module);

        if ($module === 'profile') {
            $query->whereNotIn('notifications.type', $this->mappedTypes());
        } elseif ($types !== []) {
            $query->whereIn('notifications.type', $types);
        } else {
            return 0;
        }

        return $query->update([
            'recipients.read_at' => now(),
            'recipients.status' => 'read',
            'recipients.updated_at' => now(),
        ]);
    }

    public function moduleTypes(string $module): array
    {
        return match ($module) {
            'renewal' => [
                NotificationType::PAYMENT_ASSESSED->value,
                NotificationType::PAYMENT_RECORDED->value,
                NotificationType::RENEWAL_REQUEST_APPROVED->value,
                NotificationType::RENEWAL_REQUEST_REJECTED->value,
                NotificationType::RENEWAL_REMINDER->value,
            ],
            'alerts' => [
                NotificationType::ADVISORY_PUBLISHED->value,
            ],
            'queries' => [
                NotificationType::QUERY_RECEIVED->value,
                NotificationType::QUERY_RESPONDED->value,
            ],
            default => [],
        };
    }

    public function mappedTypes(): array
    {
        return array_merge(
            $this->moduleTypes('renewal'),
            $this->moduleTypes('alerts'),
            $this->moduleTypes('queries'),
        );
    }
}
