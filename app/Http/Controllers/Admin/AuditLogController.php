<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advisory;
use App\Models\Association;
use App\Models\AuditLog;
use App\Models\Barangay;
use App\Models\Farmer;
use App\Models\MembershipApplication;
use App\Models\MortuaryClaim;
use App\Models\Query;
use App\Models\QueryResponse;
use App\Models\RenewalRequest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AuditLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('role.in:' . User::ROLE_ADMIN);
    }

    public function index(Request $request): InertiaResponse
    {
        $filters = [
            'module' => $request->query('module'),
            'event' => $request->query('event'),
            'actor_user_id' => $request->integer('actor_user_id') ?: null,
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
            'search' => trim((string) $request->query('search', '')),
        ];

        $logsQuery = AuditLog::query()
            ->with([
                'actor:id,name',
                'subject',
            ])
            ->when($filters['module'], fn (Builder $query, string $module) => $query->where('module', $module))
            ->when($filters['event'], fn (Builder $query, string $event) => $query->where('event', $event))
            ->when($filters['actor_user_id'], fn (Builder $query, int $actorUserId) => $query->where('actor_user_id', $actorUserId))
            ->when($filters['date_from'], fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'], fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $search = $filters['search'];

                $query->where(function (Builder $nested) use ($search): void {
                    $nested
                        ->where('description', 'like', "%{$search}%")
                        ->orWhere('actor_name', 'like', "%{$search}%")
                        ->orWhere('module', 'like', "%{$search}%")
                        ->orWhere('event', 'like', "%{$search}%")
                        ->orWhere('subject_label', 'like', "%{$search}%");
                });
            });

        $logs = (clone $logsQuery)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $logs->getCollection()->loadMorph('subject', [
            User::class => [],
            Farmer::class => ['profile:id,farmer_id,first_name,middle_name,last_name,suffix'],
            MembershipApplication::class => ['farmer.profile:id,farmer_id,first_name,middle_name,last_name,suffix'],
            RenewalRequest::class => ['farmer.profile:id,farmer_id,first_name,middle_name,last_name,suffix'],
        ]);

        $today = CarbonImmutable::today();
        $activeFilterCount = collect($filters)
            ->filter(fn (mixed $value): bool => filled($value))
            ->count();

        return Inertia::render('Admin/AuditLogs/Index', [
            'filters' => $filters,
            'activeFilterCount' => $activeFilterCount,
            'moduleOptions' => AuditLog::query()
                ->select('module')
                ->distinct()
                ->orderBy('module')
                ->pluck('module')
                ->map(fn (string $module): array => [
                    'value' => $module,
                    'label' => str($module)->replace('_', ' ')->title()->toString(),
                ])
                ->values()
                ->all(),
            'eventOptions' => AuditLog::query()
                ->select('event')
                ->distinct()
                ->orderBy('event')
                ->pluck('event')
                ->map(fn (string $event): array => [
                    'value' => $event,
                    'label' => str($event)->replace('_', ' ')->title()->toString(),
                ])
                ->values()
                ->all(),
            'actorOptions' => User::query()
                ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_STAFF])
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (User $actor): array => [
                    'value' => $actor->id,
                    'label' => $actor->name,
                ])
                ->values()
                ->all(),
            'summary' => [
                'total' => AuditLog::query()->count(),
                'today' => AuditLog::query()->whereBetween('created_at', [$today->startOfDay(), $today->endOfDay()])->count(),
                'auth' => AuditLog::query()->where('module', 'auth')->count(),
                'admin' => AuditLog::query()->where('actor_role', User::ROLE_ADMIN)->count(),
            ],
            'logs' => $logs->through(fn (AuditLog $log): array => $this->serializeLog($log)),
            'urls' => [
                'index' => route('admin.audit-logs.index'),
            ],
        ]);
    }

    private function serializeLog(AuditLog $log): array
    {
        return [
            'id' => $log->id,
            'module' => [
                'value' => $log->module,
                'label' => str($log->module)->replace('_', ' ')->title()->toString(),
            ],
            'event' => [
                'value' => $log->event,
                'label' => str($log->event)->replace('_', ' ')->title()->toString(),
            ],
            'description' => $log->description,
            'actorName' => $log->actor_name ?: 'System',
            'actorRole' => $log->actor_role,
            'createdAt' => optional($log->created_at)->format('M d, Y g:i A'),
            'subjectLabel' => $this->subjectLabel($log),
            'recordUrl' => $this->recordUrl($log),
            'activity' => [
                'action' => data_get($log->metadata, 'activity_action'),
                'label' => data_get($log->metadata, 'activity_label'),
            ],
            'changeSummary' => collect($log->change_summary ?? [])
                ->take(3)
                ->map(fn (mixed $change): array => is_array($change) ? [
                    'field' => $change['field'] ?? 'Field',
                    'from' => $change['from'] ?? 'None',
                    'to' => $change['to'] ?? 'None',
                ] : [
                    'field' => 'Change',
                    'from' => 'None',
                    'to' => $this->stringifyMetadataValue($change),
                ])
                ->values()
                ->all(),
            'metadata' => collect($log->metadata ?? [])
                ->except(['activity_action', 'activity_label'])
                ->map(fn (mixed $value, string $key): array => [
                    'key' => str($key)->replace('_', ' ')->title()->toString(),
                    'value' => $this->stringifyMetadataValue($value),
                ])
                ->take(4)
                ->values()
                ->all(),
        ];
    }

    private function subjectLabel(AuditLog $log): ?string
    {
        $subject = $log->subject;

        if ($subject instanceof User) {
            $parts = array_filter([
                $subject->name,
                $subject->email,
            ]);

            return $parts !== [] ? 'User: ' . implode(' | ', $parts) : null;
        }

        if ($subject instanceof Farmer) {
            $parts = array_filter([
                $subject->full_name,
                $subject->farmer_code,
            ]);

            return $parts !== [] ? 'Farmer: ' . implode(' | ', $parts) : null;
        }

        if ($subject instanceof MembershipApplication) {
            $parts = array_filter([
                $subject->application_no,
                $subject->farmer?->full_name,
            ]);

            return $parts !== [] ? 'Application: ' . implode(' | ', $parts) : null;
        }

        if ($subject instanceof RenewalRequest) {
            $parts = array_filter([
                filled($subject->year) ? 'Year ' . $subject->year : null,
                $subject->farmer?->full_name,
            ]);

            return $parts !== [] ? 'Renewal: ' . implode(' | ', $parts) : null;
        }

        if (filled($log->subject_label)) {
            return (string) $log->subject_label;
        }

        if (! $log->subject_type || ! $log->subject_id) {
            return null;
        }

        $label = str(class_basename((string) $log->subject_type))
            ->replace('_', ' ')
            ->headline()
            ->replace('Query Response', 'Query Reply')
            ->toString();

        return $label !== '' ? $label : null;
    }

    private function recordUrl(AuditLog $log): ?string
    {
        $subject = $log->subject;

        return match (true) {
            $subject instanceof User => route('admin.users.show', $subject),
            $subject instanceof Farmer => route('admin.farmers.show', $subject),
            $subject instanceof MembershipApplication => route('admin.membership-applications.show', $subject),
            $subject instanceof RenewalRequest => route('admin.renewals.show', $subject),
            $subject instanceof Query => route('admin.queries.show', $subject),
            $subject instanceof QueryResponse => $subject->query_id
                ? optional(Query::query()->find($subject->query_id), fn (Query $query): string => route('admin.queries.show', $query))
                : null,
            $subject instanceof Barangay => route('admin.barangays.show', $subject),
            $subject instanceof Association => route('admin.associations.show', $subject),
            $subject instanceof Advisory => route('admin.advisories.show', $subject),
            $subject instanceof MortuaryClaim => route('admin.mortuary-claims.show', $subject),
            default => null,
        };
    }

    private function stringifyMetadataValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'None';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            return collect($value)
                ->map(fn (mixed $item): string => $this->stringifyMetadataValue($item))
                ->implode(', ');
        }

        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        return (string) $value;
    }
}
