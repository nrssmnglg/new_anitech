<?php

namespace App\Services\Farmer;

use App\Enums\NotificationType;
use App\Models\Farmer;
use App\Models\Query;
use App\Services\Routing\PublicRouteKeyService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FarmerNotificationService
{
    public function __construct(
        private readonly PublicRouteKeyService $publicRouteKeyService,
    ) {
    }

    public function list(Farmer $farmer, int $limit = 20, ?string $module = null, bool $unreadOnly = false)
    {
        return $this->baseQuery($farmer)
            ->when($module, fn ($query, string $moduleName) => $this->applyModuleFilter($query, $moduleName))
            ->when($unreadOnly, fn ($query) => $query->whereNull('recipients.read_at'))
            ->orderByRaw('CASE WHEN recipients.read_at IS NULL THEN 0 ELSE 1 END')
            ->orderByDesc('notifications.created_at')
            ->limit($limit)
            ->get()
            ->map(function (object $notification): object {
                $payload = $this->decodePayload($notification->payload ?? null);
                $type = (string) $notification->type;
                $enum = NotificationType::tryFrom($type);
                $module = $this->moduleForType($type);
                $action = $this->actionFor($type, $payload);

                $notification->payload_data = $payload;
                $notification->target_url = $this->targetUrl($type, $payload);
                $notification->type_label = $enum?->label() ?? Str::headline(str_replace('_', ' ', $type));
                $notification->is_read = $notification->read_at !== null;
                $notification->module = $module['key'];
                $notification->module_label = $module['label'];
                $notification->action = $action;
                $notification->is_priority = in_array($type, [
                    NotificationType::QUERY_RESPONDED->value,
                    NotificationType::PAYMENT_ASSESSED->value,
                    NotificationType::RENEWAL_REQUEST_REJECTED->value,
                    NotificationType::MEMBERSHIP_APPLICATION_REJECTED->value,
                ], true);

                return $notification;
            });
    }

    public function summary(Farmer $farmer): array
    {
        $baseQuery = $this->recipientQuery($farmer);

        $grouped = (clone $baseQuery)
            ->select('notifications.type')
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw('SUM(CASE WHEN recipients.read_at IS NULL THEN 1 ELSE 0 END) as unread_count')
            ->groupBy('notifications.type')
            ->get();

        $modules = [];

        foreach ($grouped as $row) {
            $module = $this->moduleForType((string) $row->type);
            $key = $module['key'];
            $modules[$key] = [
                'label' => $module['label'],
                'total' => ($modules[$key]['total'] ?? 0) + (int) $row->total_count,
                'unread' => ($modules[$key]['unread'] ?? 0) + (int) $row->unread_count,
            ];
        }

        return [
            'total' => (clone $baseQuery)->count(),
            'unread' => (clone $baseQuery)->whereNull('recipients.read_at')->count(),
            'read' => (clone $baseQuery)->whereNotNull('recipients.read_at')->count(),
            'modules' => $modules,
        ];
    }

    public function markAsRead(Farmer $farmer, int $recipientId): bool
    {
        return $this->baseQuery($farmer)
                ->where('recipients.id', $recipientId)
                ->whereNull('recipients.read_at')
                ->update([
                    'recipients.read_at' => now(),
                    'recipients.status' => 'read',
                    'recipients.updated_at' => now(),
                ]) > 0;
    }

    public function unreadQueryReplyCounts(Farmer $farmer): array
    {
        return $this->recipientQuery($farmer)
            ->where('notifications.type', NotificationType::QUERY_RESPONDED->value)
            ->whereNull('recipients.read_at')
            ->selectRaw('JSON_UNQUOTE(JSON_EXTRACT(notifications.payload, "$.query_id")) as query_id')
            ->selectRaw('COUNT(*) as unread_count')
            ->groupBy('query_id')
            ->pluck('unread_count', 'query_id')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    public function markQueryRepliesAsRead(Farmer $farmer, Query $query): int
    {
        return $this->recipientQuery($farmer)
            ->where('notifications.type', NotificationType::QUERY_RESPONDED->value)
            ->whereNull('recipients.read_at')
            ->where('notifications.payload->query_id', $query->getKey())
            ->update([
                'recipients.read_at' => now(),
                'recipients.status' => 'read',
                'recipients.updated_at' => now(),
            ]);
    }

    private function baseQuery(Farmer $farmer)
    {
        return $this->recipientQuery($farmer)
            ->select([
                'recipients.id as recipient_id',
                'recipients.status as recipient_status',
                'recipients.read_at',
                'notifications.id as notification_id',
                'notifications.type',
                'notifications.subject',
                'notifications.message',
                'notifications.payload',
                'notifications.created_at',
            ]);
    }

    private function recipientQuery(Farmer $farmer)
    {
        return DB::table('notification_recipients as recipients')
            ->join('notifications', 'notifications.id', '=', 'recipients.notification_id')
            ->where(function ($query) use ($farmer): void {
                $query->where('recipients.farmer_id', $farmer->id);

                if ($userId = Auth::guard('farmer_pwa')->id()) {
                    $query->orWhere('recipients.user_id', $userId);
                }
            });
    }

    private function decodePayload(mixed $payload): array
    {
        if (! is_string($payload) || $payload === '') {
            return [];
        }

        $decoded = json_decode($payload, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function targetUrl(string $type, array $payload): ?string
    {
        return match ($type) {
            NotificationType::ADVISORY_PUBLISHED->value
                => $this->advisoryTargetUrl($payload),
            NotificationType::QUERY_RESPONDED->value
                => isset($payload['query_public_id']) ? url('/farmer/app/inquiries/' . $payload['query_public_id']) : (isset($payload['query_id']) ? url('/farmer/app/inquiries/' . $payload['query_id']) : null),
            NotificationType::PAYMENT_RECORDED->value,
            NotificationType::PAYMENT_ASSESSED->value
                => url('/farmer/app/payments'),
            NotificationType::RENEWAL_REQUEST_APPROVED->value,
            NotificationType::RENEWAL_REQUEST_REJECTED->value,
            NotificationType::RENEWAL_REMINDER->value
                => url('/farmer/app/renewals'),
            default => url('/farmer/app/advisories'),
        };
    }

    private function advisoryTargetUrl(array $payload): ?string
    {
        if (isset($payload['advisory_id']) && is_numeric($payload['advisory_id'])) {
            return url('/farmer/app/advisories/' . $this->publicRouteKeyService->encode((int) $payload['advisory_id']));
        }

        return isset($payload['slug'])
            ? url('/farmer/app/advisories/' . $payload['slug'])
            : null;
    }

    private function moduleForType(string $type): array
    {
        return match ($type) {
            NotificationType::MEMBERSHIP_APPLICATION_SUBMITTED->value,
            NotificationType::MEMBERSHIP_APPLICATION_UPDATED->value,
            NotificationType::MEMBERSHIP_APPLICATION_APPROVED->value,
            NotificationType::MEMBERSHIP_APPLICATION_REJECTED->value,
            NotificationType::DOCUMENT_VERIFIED->value
                => ['key' => 'applications', 'label' => 'Applications'],
            NotificationType::RENEWAL_REQUEST_SUBMITTED->value,
            NotificationType::RENEWAL_REQUEST_APPROVED->value,
            NotificationType::RENEWAL_REQUEST_REJECTED->value,
            NotificationType::RENEWAL_REMINDER->value
                => ['key' => 'renewals', 'label' => 'Renewals'],
            NotificationType::PAYMENT_ASSESSED->value,
            NotificationType::PAYMENT_RECORDED->value
                => ['key' => 'payments', 'label' => 'Payments'],
            NotificationType::QUERY_RECEIVED->value,
            NotificationType::QUERY_RESPONDED->value
                => ['key' => 'inquiries', 'label' => 'Inquiries'],
            NotificationType::ADVISORY_PUBLISHED->value
                => ['key' => 'advisories', 'label' => 'Advisories'],
            NotificationType::REACTIVATION_SUBMITTED->value,
            NotificationType::FARMER_MARKED_INACTIVE->value
                => ['key' => 'account_alerts', 'label' => 'Account Alerts'],
            default => ['key' => 'account_alerts', 'label' => 'Account Alerts'],
        };
    }

    private function actionFor(string $type, array $payload): array
    {
        $targetUrl = $this->targetUrl($type, $payload);

        return match ($type) {
            NotificationType::QUERY_RESPONDED->value => [
                'label' => 'View Reply',
                'target_url' => $targetUrl,
            ],
            NotificationType::PAYMENT_ASSESSED->value => [
                'label' => 'Pay Now',
                'target_url' => $targetUrl,
            ],
            NotificationType::PAYMENT_RECORDED->value => [
                'label' => 'View Payment',
                'target_url' => $targetUrl,
            ],
            NotificationType::RENEWAL_REQUEST_SUBMITTED->value,
            NotificationType::RENEWAL_REQUEST_APPROVED->value,
            NotificationType::RENEWAL_REQUEST_REJECTED->value,
            NotificationType::RENEWAL_REMINDER->value => [
                'label' => 'Open Renewal',
                'target_url' => $targetUrl,
            ],
            NotificationType::DOCUMENT_VERIFIED->value => [
                'label' => 'Upload Missing Document',
                'target_url' => url('/farmer/app/profile'),
            ],
            NotificationType::MEMBERSHIP_APPLICATION_SUBMITTED->value,
            NotificationType::MEMBERSHIP_APPLICATION_UPDATED->value,
            NotificationType::MEMBERSHIP_APPLICATION_APPROVED->value,
            NotificationType::MEMBERSHIP_APPLICATION_REJECTED->value => [
                'label' => 'Track Application',
                'target_url' => url('/farmer/app/membership'),
            ],
            default => [
                'label' => 'Open',
                'target_url' => $targetUrl,
            ],
        };
    }

    private function applyModuleFilter($query, string $module): void
    {
        $types = match ($module) {
            'applications' => [
                NotificationType::MEMBERSHIP_APPLICATION_SUBMITTED->value,
                NotificationType::MEMBERSHIP_APPLICATION_UPDATED->value,
                NotificationType::MEMBERSHIP_APPLICATION_APPROVED->value,
                NotificationType::MEMBERSHIP_APPLICATION_REJECTED->value,
                NotificationType::DOCUMENT_VERIFIED->value,
            ],
            'renewals' => [
                NotificationType::RENEWAL_REQUEST_SUBMITTED->value,
                NotificationType::RENEWAL_REQUEST_APPROVED->value,
                NotificationType::RENEWAL_REQUEST_REJECTED->value,
                NotificationType::RENEWAL_REMINDER->value,
            ],
            'payments' => [
                NotificationType::PAYMENT_ASSESSED->value,
                NotificationType::PAYMENT_RECORDED->value,
            ],
            'inquiries' => [
                NotificationType::QUERY_RECEIVED->value,
                NotificationType::QUERY_RESPONDED->value,
            ],
            'advisories' => [
                NotificationType::ADVISORY_PUBLISHED->value,
            ],
            'account_alerts' => [
                NotificationType::REACTIVATION_SUBMITTED->value,
                NotificationType::FARMER_MARKED_INACTIVE->value,
                NotificationType::MORTUARY_CLAIM_FILED->value,
            ],
            default => [],
        };

        if ($types !== []) {
            $query->whereIn('notifications.type', $types);
        }
    }
}
