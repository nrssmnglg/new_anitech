<?php

namespace App\Http\Controllers\Admin;

use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Models\MembershipApplication;
use App\Models\Query;
use App\Models\RenewalRequest;
use App\Services\Notifications\NotificationDispatchService;
use App\Services\Routing\PublicRouteKeyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationDispatchService $notificationDispatchService,
        private readonly PublicRouteKeyService $publicRouteKeyService,
    ) {
    }

    public function index(Request $request): InertiaResponse
    {
        $filters = $this->filtersFromRequest($request);
        $notifications = $this->orderedNotificationQuery($filters)
            ->paginate(15)
            ->withQueryString()
            ->through(fn (object $notification): object => $this->transformNotification($notification));

        return Inertia::render('Admin/Notifications/Index', [
            'notifications' => $notifications,
            'summary' => $this->summary($filters),
            'filters' => $filters,
            'moduleOptions' => $this->moduleOptions(),
            'stateOptions' => $this->stateOptions(),
            'urls' => [
                'index' => route('admin.notifications.index'),
                'readAll' => route('admin.notifications.read-all'),
            ],
            'managementSummary' => $this->managementSummary(),
            'dispatches' => $this->dispatchHistoryQuery($filters)
                ->paginate(12, ['*'], 'dispatch_page')
                ->withQueryString()
                ->through(fn (object $dispatch): object => $this->transformDispatch($dispatch)),
            'failedRecipients' => $this->failedRecipientsQuery($filters)
                ->limit(10)
                ->get()
                ->map(fn (object $failure): object => $this->transformFailedRecipient($failure)),
            'scheduledReminders' => $this->scheduledReminderQuery()
                ->limit(10)
                ->get()
                ->map(fn (object $reminder): object => $this->transformDispatch($reminder)),
        ]);
    }

    public function feed(Request $request): JsonResponse
    {
        $filters = $this->filtersFromRequest($request);
        $summary = $this->summary($filters);
        $notifications = $this->orderedNotificationQuery($filters)
            ->limit(15)
            ->get()
            ->map(fn (object $notification): object => $this->transformNotification($notification));

        return response()->json([
            'summary' => $summary,
            'unread_count' => $this->globalUnreadCount(),
            'notifications' => $notifications->map(fn (object $notification): array => $this->serializeNotification($notification))->values()->all(),
            'notifications_html' => view('admin.notifications._rows', [
                'notifications' => $notifications,
            ])->render(),
            'latest_notification' => $this->latestUnreadNotification(),
        ]);
    }

    public function show(string $notification): InertiaResponse
    {
        $notificationId = $this->decodeNotificationRouteKey($notification);

        $dispatch = $this->dispatchHistoryQuery()
            ->where('notifications.id', $notificationId)
            ->firstOrFail();

        $dispatch = $this->transformDispatch($dispatch);

        $recipients = DB::table('notification_recipients as recipients')
            ->leftJoin('users', 'users.id', '=', 'recipients.user_id')
            ->leftJoin('farmers', 'farmers.id', '=', 'recipients.farmer_id')
            ->where('recipients.notification_id', $notificationId)
            ->select([
                'recipients.id',
                'recipients.user_id',
                'recipients.farmer_id',
                'recipients.recipient_address',
                'recipients.status',
                'recipients.delivered_at',
                'recipients.read_at',
                'recipients.failed_at',
                'recipients.failure_reason',
                'recipients.created_at',
                'users.name as user_name',
                'farmers.farmer_code',
            ])
            ->orderByDesc('recipients.created_at')
            ->get()
            ->map(function (object $recipient): object {
                $recipient->status_label = match ((string) $recipient->status) {
                    'delivered' => 'Delivered',
                    'failed' => 'Failed',
                    'read' => 'Read',
                    default => 'Pending',
                };

                return $recipient;
            });

        return Inertia::render('Admin/Notifications/Show', [
            'dispatch' => $dispatch,
            'recipients' => $recipients->values()->all(),
            'urls' => [
                'index' => route('admin.notifications.index'),
            ],
        ]);
    }

    public function markAllRead(Request $request): JsonResponse|RedirectResponse
    {
        DB::table('notification_recipients')
            ->where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
                'status' => 'read',
                'updated_at' => now(),
            ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read.',
            ]);
        }

        return redirect()
            ->route('admin.notifications.index')
            ->with('success', 'All notifications marked as read.');
    }

    public function markRead(Request $request, int $recipient): JsonResponse|RedirectResponse
    {
        $updated = $this->markRecipientRead($recipient);
        $message = $updated ? 'Notification marked as read.' : 'Notification already marked as read.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'updated' => $updated,
            ]);
        }

        return redirect()
            ->route('admin.notifications.index')
            ->with('success', $message);
    }

    public function open(Request $request, int $recipient): JsonResponse|RedirectResponse
    {
        $notification = $this->notificationQuery()
            ->where('recipients.id', $recipient)
            ->firstOrFail();

        $this->markRecipientRead($recipient);

        $payload = $this->decodePayload($notification->payload ?? null);
        $targetUrl = $this->targetUrl((string) $notification->type, $payload);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'target_url' => $targetUrl,
            ]);
        }

        if ($targetUrl !== null) {
            return redirect()->to($targetUrl);
        }

        return redirect()
            ->route('admin.notifications.index')
            ->with('success', 'Notification marked as read.');
    }

    public function resend(string $notification): RedirectResponse
    {
        $notificationId = $this->decodeNotificationRouteKey($notification);

        $record = DB::table('notifications')
            ->where('id', $notificationId)
            ->first();

        abort_unless($record !== null, 404);

        $recipients = DB::table('notification_recipients')
            ->where('notification_id', $notificationId)
            ->get()
            ->map(fn (object $recipient): array => [
                'user_id' => $recipient->user_id,
                'farmer_id' => $recipient->farmer_id,
                'recipient_address' => $recipient->recipient_address,
                'status' => 'pending',
                'delivered_at' => null,
                'read_at' => null,
                'failed_at' => null,
                'failure_reason' => null,
            ])
            ->all();

        $payload = $this->decodePayload($record->payload ?? null);

        $this->notificationDispatchService->persistQueued([
            'type' => $record->type,
            'channel' => $record->channel,
            'subject' => $record->subject,
            'message' => $record->message,
            'payload' => $payload,
            'status' => 'queued',
            'queued_at' => now()->toDateTimeString(),
            'created_by' => auth()->id(),
            'recipients' => $recipients,
        ]);

        return redirect()
            ->route('admin.notifications.index')
            ->with('success', 'Notification has been re-queued for resend.');
    }

    private function filtersFromRequest(Request $request): array
    {
        $module = (string) $request->query('module', 'all');
        $state = (string) $request->query('state', 'all');
        $delivery = (string) $request->query('delivery', 'all');

        return [
            'module' => array_key_exists($module, $this->moduleOptions()) ? $module : 'all',
            'state' => array_key_exists($state, $this->stateOptions()) ? $state : 'all',
            'delivery' => in_array($delivery, ['all', 'queued', 'delivered', 'failed', 'read'], true) ? $delivery : 'all',
        ];
    }

    private function orderedNotificationQuery(array $filters = [])
    {
        return $this->applyFilters($this->notificationQuery(), $filters)
            ->orderByRaw('CASE WHEN recipients.read_at IS NULL THEN 0 ELSE 1 END')
            ->orderByDesc('notifications.created_at');
    }

    private function applyFilters($query, array $filters = [])
    {
        $module = $filters['module'] ?? 'all';
        $state = $filters['state'] ?? 'all';
        $delivery = $filters['delivery'] ?? 'all';

        if ($module !== 'all') {
            $types = $this->moduleTypeMap()[$module] ?? [];

            if ($types !== []) {
                $query->whereIn('notifications.type', $types);
            }
        }

        $query = match ($state) {
            'unread' => $query->whereNull('recipients.read_at'),
            'read' => $query->whereNotNull('recipients.read_at'),
            default => $query,
        };

        return match ($delivery) {
            'queued' => $query->where('recipients.status', 'pending'),
            'delivered' => $query->where('recipients.status', 'delivered'),
            'failed' => $query->where('recipients.status', 'failed'),
            'read' => $query->whereNotNull('recipients.read_at'),
            default => $query,
        };
    }

    private function transformNotification(object $notification): object
    {
        $payload = $this->decodePayload($notification->payload ?? null);
        $type = (string) $notification->type;
        $moduleKey = $this->moduleForType($type);

        $notification->payload_data = $payload;
        $notification->type_label = NotificationType::tryFrom($type)?->label()
            ?? (string) str($type)->replace('_', ' ')->title();
        $notification->module_key = $moduleKey;
        $notification->module_label = $this->moduleOptions()[$moduleKey] ?? 'System';
        $notification->source_label = filled($payload['source'] ?? null)
            ? (string) str((string) $payload['source'])->replace('_', ' ')->upper()
            : null;
        $notification->target_url = $this->targetUrl($type, $payload);
        $notification->signature = $notification->notification_id . '|' . (string) ($notification->created_at ?? '');

        return $notification;
    }

    private function latestUnreadNotification(): ?object
    {
        $notification = $this->orderedNotificationQuery(['state' => 'unread'])->first();

        return $notification ? $this->transformNotification($notification) : null;
    }

    private function notificationQuery()
    {
        return DB::table('notification_recipients as recipients')
            ->join('notifications', 'notifications.id', '=', 'recipients.notification_id')
            ->where('recipients.user_id', auth()->id())
            ->select([
                'recipients.id as recipient_id',
                'recipients.status as recipient_status',
                'recipients.read_at',
                'recipients.created_at as recipient_created_at',
                'notifications.id as notification_id',
                'notifications.type',
                'notifications.subject',
                'notifications.message',
                'notifications.payload',
                'notifications.created_at',
            ]);
    }

    private function summary(array $filters = []): array
    {
        $baseQuery = $this->applyFilters($this->notificationQuery(), $filters);

        return [
            'total' => (clone $baseQuery)->count(),
            'unread' => (clone $baseQuery)->whereNull('read_at')->count(),
            'read' => (clone $baseQuery)->whereNotNull('read_at')->count(),
        ];
    }

    private function managementSummary(): array
    {
        return [
            'totalDispatches' => DB::table('notifications')->count(),
            'queuedDispatches' => DB::table('notifications')->where('status', 'queued')->count(),
            'deliveredRecipients' => DB::table('notification_recipients')->where('status', 'delivered')->count(),
            'failedRecipients' => DB::table('notification_recipients')->where('status', 'failed')->count(),
            'scheduledReminders' => DB::table('notifications')
                ->where('type', NotificationType::RENEWAL_REMINDER->value)
                ->where('status', 'queued')
                ->count(),
        ];
    }

    private function dispatchHistoryQuery(array $filters = [])
    {
        $query = DB::table('notifications')
            ->leftJoin('users', 'users.id', '=', 'notifications.created_by')
            ->leftJoin('notification_recipients', 'notification_recipients.notification_id', '=', 'notifications.id')
            ->select([
                'notifications.id',
                'notifications.type',
                'notifications.channel',
                'notifications.subject',
                'notifications.message',
                'notifications.payload',
                'notifications.status',
                'notifications.queued_at',
                'notifications.sent_at',
                'notifications.created_at',
                'users.name as created_by_name',
            ])
            ->selectRaw('COUNT(notification_recipients.id) as recipient_count')
            ->selectRaw("SUM(CASE WHEN notification_recipients.status = 'delivered' THEN 1 ELSE 0 END) as delivered_count")
            ->selectRaw("SUM(CASE WHEN notification_recipients.status = 'failed' THEN 1 ELSE 0 END) as failed_count")
            ->selectRaw("SUM(CASE WHEN notification_recipients.read_at IS NOT NULL THEN 1 ELSE 0 END) as read_count")
            ->groupBy([
                'notifications.id',
                'notifications.type',
                'notifications.channel',
                'notifications.subject',
                'notifications.message',
                'notifications.payload',
                'notifications.status',
                'notifications.queued_at',
                'notifications.sent_at',
                'notifications.created_at',
                'users.name',
            ])
            ->orderByDesc('notifications.created_at');

        if (($filters['module'] ?? 'all') !== 'all') {
            $types = $this->moduleTypeMap()[$filters['module']] ?? [];

            if ($types !== []) {
                $query->whereIn('notifications.type', $types);
            }
        }

        if (($filters['delivery'] ?? 'all') === 'queued') {
            $query->where('notifications.status', 'queued');
        }

        if (($filters['delivery'] ?? 'all') === 'failed') {
            $query->havingRaw("SUM(CASE WHEN notification_recipients.status = 'failed' THEN 1 ELSE 0 END) > 0");
        }

        if (($filters['delivery'] ?? 'all') === 'delivered') {
            $query->havingRaw("SUM(CASE WHEN notification_recipients.status = 'delivered' THEN 1 ELSE 0 END) > 0");
        }

        if (($filters['delivery'] ?? 'all') === 'read') {
            $query->havingRaw("SUM(CASE WHEN notification_recipients.read_at IS NOT NULL THEN 1 ELSE 0 END) > 0");
        }

        return $query;
    }

    private function scheduledReminderQuery()
    {
        return DB::table('notifications')
            ->leftJoin('users', 'users.id', '=', 'notifications.created_by')
            ->leftJoin('notification_recipients', 'notification_recipients.notification_id', '=', 'notifications.id')
            ->where('notifications.type', NotificationType::RENEWAL_REMINDER->value)
            ->where('notifications.status', 'queued')
            ->select([
                'notifications.id',
                'notifications.type',
                'notifications.channel',
                'notifications.subject',
                'notifications.message',
                'notifications.payload',
                'notifications.status',
                'notifications.queued_at',
                'notifications.sent_at',
                'notifications.created_at',
                'users.name as created_by_name',
            ])
            ->selectRaw('COUNT(notification_recipients.id) as recipient_count')
            ->selectRaw("SUM(CASE WHEN notification_recipients.status = 'delivered' THEN 1 ELSE 0 END) as delivered_count")
            ->selectRaw("SUM(CASE WHEN notification_recipients.status = 'failed' THEN 1 ELSE 0 END) as failed_count")
            ->selectRaw("SUM(CASE WHEN notification_recipients.read_at IS NOT NULL THEN 1 ELSE 0 END) as read_count")
            ->groupBy([
                'notifications.id',
                'notifications.type',
                'notifications.channel',
                'notifications.subject',
                'notifications.message',
                'notifications.payload',
                'notifications.status',
                'notifications.queued_at',
                'notifications.sent_at',
                'notifications.created_at',
                'users.name',
            ])
            ->orderByDesc('notifications.created_at');
    }

    private function failedRecipientsQuery(array $filters = [])
    {
        $query = DB::table('notification_recipients as recipients')
            ->join('notifications', 'notifications.id', '=', 'recipients.notification_id')
            ->where('recipients.status', 'failed')
            ->select([
                'recipients.id as recipient_id',
                'recipients.recipient_address',
                'recipients.failure_reason',
                'recipients.failed_at',
                'notifications.id as notification_id',
                'notifications.type',
                'notifications.subject',
                'notifications.payload',
            ])
            ->orderByDesc('recipients.failed_at');

        if (($filters['module'] ?? 'all') !== 'all') {
            $types = $this->moduleTypeMap()[$filters['module']] ?? [];

            if ($types !== []) {
                $query->whereIn('notifications.type', $types);
            }
        }

        return $query;
    }

    private function transformDispatch(object $dispatch): object
    {
        $payload = $this->decodePayload($dispatch->payload ?? null);
        $type = (string) $dispatch->type;
        $moduleKey = $this->moduleForType($type);

        $dispatch->payload_data = $payload;
        $dispatch->type_label = NotificationType::tryFrom($type)?->label()
            ?? (string) str($type)->replace('_', ' ')->title();
        $dispatch->module_label = $this->moduleOptions()[$moduleKey] ?? 'System';
        $dispatch->target_url = $this->targetUrl($type, $payload);
        $dispatch->delivery_label = match (true) {
            (int) ($dispatch->failed_count ?? 0) > 0 => 'Failed',
            (int) ($dispatch->delivered_count ?? 0) > 0 => 'Delivered',
            (string) ($dispatch->status ?? '') === 'queued' => 'Queued',
            default => ucfirst((string) ($dispatch->status ?? 'queued')),
        };
        $dispatch->show_url = route('admin.notifications.show', $this->notificationRouteKey($dispatch->id));
        $dispatch->resend_url = route('admin.notifications.resend', $this->notificationRouteKey($dispatch->id));

        return $dispatch;
    }

    private function notificationRouteKey(int|string $notificationId): string
    {
        return $this->publicRouteKeyService->encode($notificationId);
    }

    private function decodeNotificationRouteKey(string $notification): int
    {
        if (is_numeric($notification)) {
            return (int) $notification;
        }

        $decodedId = $this->publicRouteKeyService->decode($notification);

        abort_if($decodedId === null, 404);

        return $decodedId;
    }

    private function transformFailedRecipient(object $failure): object
    {
        $failure->type_label = NotificationType::tryFrom((string) $failure->type)?->label()
            ?? (string) str((string) $failure->type)->replace('_', ' ')->title();

        return $failure;
    }

    private function globalUnreadCount(): int
    {
        return DB::table('notification_recipients')
            ->where('user_id', auth()->id())
            ->whereNull('read_at')
            ->count();
    }

    private function markRecipientRead(int $recipient): int
    {
        return DB::table('notification_recipients')
            ->where('id', $recipient)
            ->where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
                'status' => 'read',
                'updated_at' => now(),
            ]);
    }

    private function decodePayload(mixed $payload): array
    {
        if (! is_string($payload) || $payload === '') {
            return [];
        }

        $decoded = json_decode($payload, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function moduleForType(string $type): string
    {
        foreach ($this->moduleTypeMap() as $module => $types) {
            if (in_array($type, $types, true)) {
                return $module;
            }
        }

        return 'system';
    }

    private function moduleTypeMap(): array
    {
        return [
            'applications' => [
                NotificationType::MEMBERSHIP_APPLICATION_SUBMITTED->value,
                NotificationType::MEMBERSHIP_APPLICATION_UPDATED->value,
            ],
            'renewals' => [
                NotificationType::RENEWAL_REQUEST_SUBMITTED->value,
                NotificationType::RENEWAL_REQUEST_APPROVED->value,
                NotificationType::RENEWAL_REQUEST_REJECTED->value,
                NotificationType::RENEWAL_REMINDER->value,
            ],
            'queries' => [
                NotificationType::QUERY_RECEIVED->value,
                NotificationType::QUERY_RESPONDED->value,
            ],
            'advisories' => [
                NotificationType::ADVISORY_PUBLISHED->value,
            ],
            'mortuary' => [
                NotificationType::MORTUARY_CLAIM_FILED->value,
            ],
            'system' => [
                NotificationType::MEMBERSHIP_APPLICATION_APPROVED->value,
                NotificationType::MEMBERSHIP_APPLICATION_REJECTED->value,
                NotificationType::DOCUMENT_VERIFIED->value,
                NotificationType::PAYMENT_ASSESSED->value,
                NotificationType::PAYMENT_RECORDED->value,
                NotificationType::REACTIVATION_SUBMITTED->value,
                NotificationType::FARMER_MARKED_INACTIVE->value,
            ],
        ];
    }

    private function moduleOptions(): array
    {
        return [
            'all' => 'All Modules',
            'applications' => 'Applications',
            'renewals' => 'Renewals',
            'queries' => 'Queries',
            'advisories' => 'Advisories',
            'mortuary' => 'Mortuary',
            'system' => 'System',
        ];
    }

    private function stateOptions(): array
    {
        return [
            'all' => 'All Statuses',
            'unread' => 'Unread Only',
            'read' => 'Read Only',
        ];
    }

    private function targetUrl(string $type, array $payload): ?string
    {
        return match ($type) {
            NotificationType::MEMBERSHIP_APPLICATION_SUBMITTED->value,
            NotificationType::MEMBERSHIP_APPLICATION_UPDATED->value
                => isset($payload['application_id']) && Route::has('admin.membership-applications.show')
                    ? $this->modelRoute('admin.membership-applications.show', MembershipApplication::class, $payload['application_id'])
                    : null,
            NotificationType::RENEWAL_REQUEST_SUBMITTED->value
                => isset($payload['renewal_request_id']) && Route::has('admin.renewals.show')
                    ? $this->modelRoute('admin.renewals.show', RenewalRequest::class, $payload['renewal_request_id'])
                    : null,
            NotificationType::QUERY_RECEIVED->value
                => isset($payload['query_id']) && Route::has('admin.queries.show')
                    ? $this->modelRoute('admin.queries.show', Query::class, $payload['query_id'])
                    : null,
            default => null,
        };
    }

    private function modelRoute(string $routeName, string $modelClass, mixed $id): ?string
    {
        $model = $modelClass::query()->find($id);

        return $model ? route($routeName, $model) : null;
    }

    private function serializeNotification(object $notification): array
    {
        return [
            'recipientId' => $notification->recipient_id,
            'notificationId' => $notification->notification_id,
            'typeLabel' => $notification->type_label,
            'moduleLabel' => $notification->module_label,
            'sourceLabel' => $notification->source_label,
            'subject' => $notification->subject,
            'message' => $notification->message,
            'createdAt' => \Carbon\Carbon::parse($notification->created_at)->format('M d, Y h:i A'),
            'isRead' => $notification->read_at !== null,
            'targetUrl' => $notification->target_url,
            'readUrl' => route('admin.notifications.read', $notification->recipient_id),
            'openUrl' => route('admin.notifications.open', $notification->recipient_id),
        ];
    }
}

