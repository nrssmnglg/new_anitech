<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\FarmerStatus;
use App\Enums\MembershipApplicationRejectionReason;
use App\Enums\MembershipStatus;
use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Models\Association;
use App\Models\AnalyticsEvent;
use App\Models\Advisory;
use App\Models\AuditLog;
use App\Models\Barangay;
use App\Models\Farmer;
use App\Models\FeeSchedule;
use App\Models\MemberType;
use App\Models\MembershipApplication;
use App\Models\MortuaryClaim;
use App\Models\Payment;
use App\Models\Query;
use App\Models\RenewalRequest;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\Audit\AuditTrailService;
use App\Services\Membership\MembershipApplicationService;
use App\Services\Membership\RenewalRequestService;
use App\Services\Notifications\NotificationDispatchService;
use App\Services\Queries\QueryWorkflowService;

class DashboardController extends Controller
{
    public function __construct(
        private readonly MembershipApplicationService $membershipApplicationService,
        private readonly RenewalRequestService $renewalRequestService,
        private readonly QueryWorkflowService $queryWorkflowService,
        private readonly NotificationDispatchService $notificationDispatchService,
        private readonly AuditTrailService $auditTrailService,
    ) {
    }

    public function index(Request $request): Response
    {
        try {
            $isAdmin = $request->user()?->hasRole(User::ROLE_ADMIN) ?? false;
            $selectedYear = $request->filled('year') ? $request->integer('year') : null;
            $barangays = Barangay::query()
                ->orderBy('name')
                ->get(['id', 'name']);

            $selectedBarangayId = $request->filled('barangay_id')
                ? $request->integer('barangay_id')
                : null;

            if ($selectedBarangayId !== null && ! $barangays->contains('id', $selectedBarangayId)) {
                $selectedBarangayId = null;
            }

            $dashboard = [
                'filters' => [
                    'baseUrl' => route('admin.dashboard.index'),
                    'selectedYear' => $selectedYear,
                    'selectedBarangayId' => $selectedBarangayId,
                    'availableYears' => $this->availableYears($selectedYear),
                    'barangays' => $barangays->map(fn (Barangay $barangay): array => [
                        'id' => $barangay->id,
                        'name' => $barangay->name,
                    ])->values()->all(),
                ],
                'actions' => [
                    'createMembershipApplicationUrl' => route('admin.membership-applications.create'),
                    'viewFarmersUrl' => route('admin.farmers.index'),
                    'tasksUrl' => ! $isAdmin ? route('admin.tasks.index') : null,
                    'manageFeeSchedulesUrl' => $isAdmin ? route('admin.fee-schedules.index') : null,
                    'viewUsersUrl' => $isAdmin ? route('admin.users.index') : null,
                ],
                'summary' => $this->summary($selectedYear, $selectedBarangayId, $isAdmin),
                'summaryCardLinks' => $this->summaryCardLinks($request, $selectedYear, $selectedBarangayId),
                'adminOperations' => $isAdmin ? $this->adminOperations($selectedYear, $selectedBarangayId) : null,
                'staffWorkspace' => ! $isAdmin ? $this->staffWorkspace($request, $selectedYear, $selectedBarangayId) : null,
                'breakdowns' => [
                    'membershipStatus' => $this->membershipStatusBreakdown($selectedYear, $selectedBarangayId),
                    'memberTypes' => $this->memberTypeBreakdown($selectedYear, $selectedBarangayId),
                    'topBarangays' => $this->topBarangays($selectedYear, $selectedBarangayId),
                    'topAssociations' => $this->topAssociations($selectedYear, $selectedBarangayId),
                    'feeSchedules' => $isAdmin ? $this->feeSchedules($selectedYear) : [],
                    'registrationTrend' => $this->registrationTrend($selectedYear, $selectedBarangayId),
                ],
                'recentFarmers' => $this->recentFarmers($selectedYear, $selectedBarangayId),
                'officeUsers' => $isAdmin ? $this->officeUsers() : [],
                'permissions' => [
                    'isAdmin' => $isAdmin,
                ],
            ];

            return Inertia::render($isAdmin ? 'Admin/Dashboard/Index' : 'Admin/Dashboard/StaffIndex', [
                'dashboard' => $dashboard,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Admin dashboard failed to render.', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'selected_year' => $request->input('year'),
                'selected_barangay_id' => $request->input('barangay_id'),
                'user_id' => $request->user()?->id,
            ]);

            throw $exception;
        }
    }

    public function tasks(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user?->hasRole([User::ROLE_ADMIN, User::ROLE_STAFF]), 403);

        $filters = [
            'timing' => $request->filled('timing') ? (string) $request->input('timing') : '',
            'priority' => $request->filled('priority') ? (string) $request->input('priority') : '',
            'module' => $request->filled('module') ? (string) $request->input('module') : '',
        ];

        $tasks = collect()
            ->merge($this->applicationTasks($user->id, $filters))
            ->merge($this->renewalTasks($user->id, $filters))
            ->merge($this->inquiryTasks($user->id, $filters))
            ->merge($this->advisoryTasks($filters))
            ->merge($this->incompleteFarmerTasks($filters))
            ->sortByDesc(fn (array $task) => $task['sort_at'])
            ->values();

        $currentPage = max(1, (int) $request->integer('page', 1));
        $perPage = 12;
        $pageItems = $tasks->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $pageItems,
            $tasks->count(),
            $perPage,
            $currentPage,
            [
                'path' => route('admin.tasks.index'),
                'query' => array_filter($filters, fn ($value) => $value !== ''),
            ]
        );

        return Inertia::render('Admin/Tasks/Index', [
            'tasks' => $paginator->through(fn (array $task): array => $task),
            'filters' => $filters,
            'filterOptions' => [
                'timings' => [
                    ['value' => '', 'label' => 'Any time'],
                    ['value' => 'pending_today', 'label' => 'Pending today'],
                    ['value' => 'overdue', 'label' => 'Overdue'],
                ],
                'priorities' => [
                    ['value' => '', 'label' => 'All priorities'],
                    ['value' => 'high_priority', 'label' => 'High priority'],
                ],
                'modules' => [
                    ['value' => '', 'label' => 'All modules'],
                    ['value' => 'applications', 'label' => 'Applications'],
                    ['value' => 'renewals', 'label' => 'Renewals'],
                    ['value' => 'inquiries', 'label' => 'Inquiries'],
                    ['value' => 'advisories', 'label' => 'Advisories'],
                    ['value' => 'farmers', 'label' => 'Farmer Records'],
                ],
            ],
            'summary' => [
                'total' => $tasks->count(),
                'pendingToday' => $tasks->filter(fn (array $task): bool => $task['timing'] === 'pending_today')->count(),
                'overdue' => $tasks->filter(fn (array $task): bool => $task['timing'] === 'overdue')->count(),
                'highPriority' => $tasks->filter(fn (array $task): bool => $task['priority'] === 'high_priority')->count(),
            ],
            'urls' => [
                'index' => route('admin.tasks.index'),
            ],
        ]);
    }

    public function quickAction(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->hasRole([User::ROLE_ADMIN, User::ROLE_STAFF]), 403);

        $payload = $request->validate([
            'module' => ['required', 'string', 'in:applications,renewals,inquiries'],
            'record' => ['required', 'string'],
            'action' => ['required', 'string', 'in:mark_complete,request_correction,forward_to_admin'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            match ($payload['module']) {
                'applications' => $this->handleApplicationQuickAction($payload['record'], $payload['action'], $payload['note'] ?? null, $user),
                'renewals' => $this->handleRenewalQuickAction($payload['record'], $payload['action'], $payload['note'] ?? null, $user),
                'inquiries' => $this->handleInquiryQuickAction($payload['record'], $payload['action'], $payload['note'] ?? null, $user),
            };
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Quick action completed.');
    }

    private function summary(?int $year, ?int $barangayId, bool $isAdmin): array
    {
        $farmers = $this->farmerQuery($year, $barangayId);
        $registeredFarmers = (clone $farmers)->get()->filter(
            fn (Farmer $farmer): bool => $this->resolveFarmerDashboardStatus($farmer, $year) !== FarmerStatus::PENDING
        );
        $pendingApplications = $this->membershipApplicationQuery($year, $barangayId)
            ->whereIn('status', $this->databaseStatusesForPendingQueue())
            ->count();

        return [
            'totalFarmers' => $registeredFarmers->count(),
            'activeFarmers' => $this->applyFarmerStatusFilterForYear(clone $farmers, FarmerStatus::ACTIVE, $year)->count(),
            'pendingFarmers' => $this->applyFarmerStatusFilterForYear(clone $farmers, FarmerStatus::PENDING, $year)->count(),
            'inactiveFarmers' => $this->applyFarmerStatusFilterForYear(clone $farmers, FarmerStatus::INACTIVE, $year)->count(),
            'pendingApplications' => $pendingApplications,
            'activeBarangays' => Barangay::query()
                ->when($barangayId !== null, fn (Builder $query) => $query->whereKey($barangayId))
                ->where('status', 'Active')
                ->count(),
            'activeAssociations' => Association::query()
                ->when($barangayId !== null, fn (Builder $query) => $query->where('barangay_id', $barangayId))
                ->where('status', 'Active')
                ->count(),
            'activeMemberTypes' => MemberType::query()->where('status', 'Active')->count(),
            'activeFeeSchedules' => $isAdmin
                ? FeeSchedule::query()
                    ->when($year !== null, fn (Builder $query) => $query->where('year', $year))
                    ->where('is_active', true)
                    ->count()
                : 0,
            'activeOfficeUsers' => $isAdmin
                ? User::query()
                    ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_STAFF])
                    ->where('status', User::STATUS_ACTIVE)
                    ->count()
                : 0,
        ];
    }

    private function summaryCardLinks(Request $request, ?int $year, ?int $barangayId): array
    {
        $isAdmin = $request->user()?->hasRole(User::ROLE_ADMIN) ?? false;
        $baseFilters = array_filter([
            'year' => $year,
            'barangay_id' => $barangayId,
        ], fn ($value) => $value !== null && $value !== '');

        return [
            'totalFarmers' => route('admin.farmers.index', $baseFilters),
            'activeFarmers' => route('admin.farmers.index', [...$baseFilters, 'status' => FarmerStatus::ACTIVE->value]),
            'pendingApplications' => route('admin.membership-applications.index', [
                'status' => 'pending',
                ...$baseFilters,
            ]),
            'inactiveFarmers' => route('admin.farmers.index', [...$baseFilters, 'status' => FarmerStatus::INACTIVE->value]),
            'activeBarangays' => $isAdmin ? route('admin.barangays.index') : null,
            'activeAssociations' => $isAdmin ? route('admin.associations.index', $baseFilters) : null,
            'activeOfficeUsers' => $isAdmin ? route('admin.users.index') : null,
            'activeFeeSchedules' => $isAdmin ? route('admin.fee-schedules.index', array_filter(['year' => $year], fn ($value) => $value !== null && $value !== '')) : null,
        ];
    }

    private function membershipStatusBreakdown(?int $year, ?int $barangayId): array
    {
        $farmers = $this->farmerQuery($year, $barangayId)
            ->withCount([
                'membershipLedgers as selected_year_settled_membership_count' => fn (Builder $query) => $this->applySettledMembershipYearConstraint($query, $year),
            ])
            ->get();

        return $farmers
            ->filter(fn (Farmer $farmer): bool => $farmer->inactive_at === null)
            ->groupBy(function (Farmer $farmer): string {
                if ((int) ($farmer->selected_year_settled_membership_count ?? 0) > 0) {
                    return MembershipStatus::ACTIVE->value;
                }

                return $farmer->membership_status?->value ?? 'pending_application';
            })
            ->map(function ($group, string $value): array {
                return [
                    'value' => $value,
                    'label' => MembershipStatus::tryFrom($value)?->label() ?? str($value)->replace('_', ' ')->title()->value(),
                    'total' => $group->count(),
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    private function registrationTrend(?int $year, ?int $barangayId): array
    {
        $monthly = collect(range(1, 12))->mapWithKeys(fn (int $month): array => [
            $month => [
                'month' => $month,
                'label' => now()->setMonth($month)->startOfMonth()->format('M'),
                'total' => 0,
            ],
        ]);

        $rows = Farmer::query()
            ->when($barangayId !== null, fn (Builder $query) => $query->where('barangay_id', $barangayId))
            ->selectRaw('MONTH(COALESCE(registered_at, created_at)) as month_value, COUNT(*) as total_count')
            ->when($year !== null, fn (Builder $query) => $query->where(function (Builder $builder) use ($year): void {
                $builder->whereYear('registered_at', $year)
                    ->orWhere(function (Builder $fallbackQuery) use ($year): void {
                        $fallbackQuery->whereNull('registered_at')
                            ->whereYear('created_at', $year);
                    });
            }))
            ->groupBy('month_value')
            ->orderBy('month_value')
            ->get();

        foreach ($rows as $row) {
            $month = (int) $row->month_value;

            if (! $monthly->has($month)) {
                continue;
            }

            $monthly->put($month, [
                'month' => $month,
                'label' => now()->setMonth($month)->startOfMonth()->format('M'),
                'total' => (int) $row->total_count,
            ]);
        }

        $currentTotal = (int) $monthly->sum('total');
        $previousTotal = $year === null
            ? 0
            : (int) Farmer::query()
                ->when($barangayId !== null, fn (Builder $query) => $query->where('barangay_id', $barangayId))
                ->where(function (Builder $query) use ($year): void {
                    $query->whereYear('registered_at', $year - 1)
                        ->orWhere(function (Builder $fallbackQuery) use ($year): void {
                            $fallbackQuery->whereNull('registered_at')
                                ->whereYear('created_at', $year - 1);
                        });
                })
                ->count();

        $yearOverYearPercent = $year === null
            ? 0
            : ($previousTotal > 0
                ? round((($currentTotal - $previousTotal) / $previousTotal) * 100)
                : ($currentTotal > 0 ? 100 : 0));

        return [
            'series' => array_values($monthly->all()),
            'currentYearTotal' => $currentTotal,
            'previousYearTotal' => $previousTotal,
            'yearOverYearPercent' => $yearOverYearPercent,
        ];
    }

    private function staffOperations(?int $year, ?int $barangayId): array
    {
        $baseFilters = array_filter([
            'year' => $year,
            'barangay_id' => $barangayId,
        ], fn ($value) => $value !== null && $value !== '');

        $pendingApplications = $this->membershipApplicationQuery($year, $barangayId)
            ->whereIn('status', $this->databaseStatusesForPendingQueue())
            ->count();

        $renewalQueueCount = $this->renewalQueueQuery($year, $barangayId)->count();
        $renewalRecordsCount = $this->renewalRecordQuery($year, $barangayId)
            ->where('status', 'Pending')
            ->count();
        $openInquiryCount = $this->queryQueueQuery($year, $barangayId)
            ->whereIn('status', ['New', 'In Progress', 'Escalated'])
            ->count();
        $mortuaryQueueCount = $this->mortuaryQueueQuery($year, $barangayId)->count();

        return [
            [
                'key' => 'applications',
                'label' => 'Pending Applications',
                'count' => $pendingApplications,
                'description' => 'New membership applications waiting for review and encoding.',
                'href' => route('admin.membership-applications.index', [
                    'status' => 'pending',
                    ...$baseFilters,
                ]),
                'accent' => 'emerald',
            ],
            [
                'key' => 'renewal_queue',
                'label' => 'Renewal Queue',
                'count' => $renewalQueueCount,
                'description' => 'Active farmers who still need renewal for the selected year.',
                'href' => route('admin.renewals.index', [
                    'queue_year' => $year ?? now()->year,
                    ...$baseFilters,
                ]),
                'accent' => 'lime',
            ],
            [
                'key' => 'renewal_records',
                'label' => 'Renewal Processing',
                'count' => $renewalRecordsCount,
                'description' => 'Renewal records still pending approval or payment processing.',
                'href' => route('admin.renewals.index', [
                    'section' => 'records',
                    'record_year' => $year ?? now()->year,
                    'record_status' => 'pending',
                    ...$baseFilters,
                ]),
                'accent' => 'amber',
            ],
            [
                'key' => 'queries',
                'label' => 'Open Inquiries',
                'count' => $openInquiryCount,
                'description' => 'Farmer messages that still need a response from office staff.',
                'href' => route('admin.queries.index', [
                    'status' => 'New',
                    ...$baseFilters,
                ]),
                'accent' => 'sky',
            ],
            [
                'key' => 'mortuary',
                'label' => 'Mortuary Queue',
                'count' => $mortuaryQueueCount,
                'description' => 'Eligible mortuary records waiting to be filed or processed.',
                'href' => route('admin.mortuary-claims.index', [
                    ...$baseFilters,
                ]),
                'accent' => 'rose',
            ],
        ];
    }

    private function staffWorkspace(Request $request, ?int $year, ?int $barangayId): array
    {
        $user = $request->user();
        $userId = $user?->id;
        $today = now();
        $baseFilters = array_filter([
            'barangay_id' => $barangayId,
        ], fn ($value) => $value !== null && $value !== '');
        $assignedTaskFilters = array_filter([
            'barangay_id' => $barangayId,
            'assigned_to' => $userId,
        ], fn ($value) => $value !== null && $value !== '');
        $myPendingApplications = $this->membershipApplicationQuery($year, $barangayId)
            ->where('reviewed_by', $userId)
            ->whereIn('status', $this->databaseStatusesForPendingQueue())
            ->count();

        $myRenewalsToReview = $this->renewalRecordQuery($year, $barangayId)
            ->where('reviewed_by', $userId)
            ->where('status', 'Pending')
            ->count();

        $myOpenInquiries = Query::query()
            ->when($barangayId !== null, function (Builder $query) use ($barangayId): void {
                $query->whereHas('farmer', fn (Builder $farmerQuery) => $farmerQuery->where('barangay_id', $barangayId));
            })
            ->when($year !== null, fn (Builder $query) => $query->whereYear('created_at', $year))
            ->whereIn('status', ['New', 'In Progress', 'Escalated'])
            ->whereHas('responses', fn (Builder $query) => $query->where('responded_by', $userId))
            ->count();

        $failedOtpCases = AnalyticsEvent::query()
            ->where('event_name', 'farmer_otp_failed')
            ->when($barangayId !== null, function (Builder $query) use ($barangayId): void {
                $query->whereHas('farmer', fn (Builder $farmerQuery) => $farmerQuery->where('barangay_id', $barangayId));
            })
            ->when($year !== null, fn (Builder $query) => $query->whereYear('occurred_at', $year))
            ->count();

        $mobileInquiriesNotAnswered = Query::query()
            ->whereNotNull('farmer_id')
            ->when($barangayId !== null, function (Builder $query) use ($barangayId): void {
                $query->whereHas('farmer', fn (Builder $farmerQuery) => $farmerQuery->where('barangay_id', $barangayId));
            })
            ->when($year !== null, fn (Builder $query) => $query->whereYear('created_at', $year))
            ->where('status', 'New')
            ->count();

        $mobileRenewalSubmissionsPendingReview = $this->renewalRecordQuery($year, $barangayId)
            ->where('source', 'mobile')
            ->where('status', 'Pending')
            ->count();

        $incompleteFarmerRecords = $this->farmerQuery($year, $barangayId)
            ->where(function (Builder $query): void {
                $query->whereNull('member_type_id')
                    ->orWhereNull('barangay_id')
                    ->orWhereDoesntHave('profile')
                    ->orWhereHas('profile', function (Builder $profileQuery): void {
                        $profileQuery
                            ->whereNull('first_name')
                            ->orWhere('first_name', '')
                            ->orWhereNull('last_name')
                            ->orWhere('last_name', '')
                            ->orWhereNull('birth_date')
                            ->orWhereNull('address')
                            ->orWhere('address', '')
                            ->orWhereNull('mobile_number')
                            ->orWhere('mobile_number', '');
                    });
            })
            ->count();

        $todayActionsQuery = AuditLog::query()
            ->where('actor_user_id', $userId)
            ->whereDate('created_at', $today->toDateString());

        $todayActions = (clone $todayActionsQuery)->count();

        $recentActions = $todayActionsQuery
            ->latest('created_at')
            ->limit(6)
            ->get(['id', 'module', 'description', 'subject_label', 'created_at'])
            ->map(fn (AuditLog $log): array => [
                'id' => $log->id,
                'module' => str($log->module)->replace('_', ' ')->title()->value(),
                'description' => $log->description,
                'subjectLabel' => $log->subject_label,
                'createdAt' => optional($log->created_at)->format('M d, Y h:i A'),
            ])
            ->all();

        $cards = [
            [
                'key' => 'pending_applications',
                'label' => 'Pending Applications',
                'count' => $myPendingApplications,
                'description' => 'Application reviews currently assigned to you and still pending.',
                'href' => route('admin.tasks.index', [
                    ...$assignedTaskFilters,
                    'module' => 'applications',
                ]),
                'accent' => 'emerald',
            ],
            [
                'key' => 'renewals_to_review',
                'label' => 'Renewals to Review',
                'count' => $myRenewalsToReview,
                'description' => 'Renewal records under your review that still need action.',
                'href' => route('admin.tasks.index', [
                    ...$assignedTaskFilters,
                    'module' => 'renewals',
                ]),
                'accent' => 'lime',
            ],
            [
                'key' => 'inquiries_awaiting_response',
                'label' => 'Inquiries Awaiting Response',
                'count' => $myOpenInquiries,
                'description' => 'Open inquiry threads you already handled and still need follow-up.',
                'href' => route('admin.tasks.index', [
                    'module' => 'inquiries',
                ]),
                'accent' => 'sky',
            ],
            [
                'key' => 'incomplete_farmer_records',
                'label' => 'Incomplete Farmer Records',
                'count' => $incompleteFarmerRecords,
                'description' => 'Registry profiles missing required farmer data for the selected filters.',
                'href' => route('admin.tasks.index', [
                    'module' => 'farmers',
                    'priority' => 'high_priority',
                ]),
                'accent' => 'amber',
            ],
            [
                'key' => 'today_actions',
                'label' => "Today's Actions",
                'count' => $todayActions,
                'description' => 'Actions you completed today across reviews, updates, and responses.',
                'href' => null,
                'accent' => 'rose',
            ],
        ];

        return [
            'headline' => [
                'assignedWorkCount' => $myPendingApplications + $myRenewalsToReview + $myOpenInquiries,
                'selectedYear' => $year,
                'selectedBarangayId' => $barangayId,
            ],
            'cards' => $cards,
            'mobileSupport' => [
                [
                    'key' => 'failed_otp_cases',
                    'label' => 'Failed OTP Cases',
                    'count' => $failedOtpCases,
                    'description' => 'Farmer mobile OTP failures that may need staff support or account verification.',
                    'href' => route('admin.analytics.index', [
                        'days' => 365,
                    ]),
                    'accent' => 'rose',
                ],
                [
                    'key' => 'mobile_inquiries_unanswered',
                    'label' => 'Mobile Inquiries Unanswered',
                    'count' => $mobileInquiriesNotAnswered,
                    'description' => 'Farmer-submitted mobile inquiries still in the new queue and waiting for a first response.',
                    'href' => route('admin.queries.index', [
                        'status' => 'New',
                        'year' => $year,
                        ...$baseFilters,
                    ]),
                    'accent' => 'sky',
                ],
                [
                    'key' => 'mobile_renewals_pending',
                    'label' => 'Mobile Renewals Pending',
                    'count' => $mobileRenewalSubmissionsPendingReview,
                    'description' => 'Mobile renewal submissions still pending review in the current staff workflow.',
                    'href' => route('admin.renewals.index', [
                        'section' => 'records',
                        'record_year' => $year ?? now()->year,
                        'record_status' => 'pending',
                        'record_source' => 'mobile',
                        ...$baseFilters,
                    ]),
                    'accent' => 'amber',
                ],
            ],
            'priority' => collect($cards)
                ->sortByDesc('count')
                ->first(),
            'recentActions' => $recentActions,
        ];
    }

    private function adminOperations(?int $year, ?int $barangayId): array
    {
        $today = now();
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();
        $selectedYear = $year ?? (int) $today->year;

        $pendingApplicationsToday = MembershipApplication::query()
            ->whereIn('status', $this->databaseStatusesForPendingQueue())
            ->whereDate('created_at', $today->toDateString())
            ->when($barangayId !== null, function (Builder $query) use ($barangayId): void {
                $query->whereHas('farmer', fn (Builder $farmerQuery) => $farmerQuery->where('barangay_id', $barangayId));
            })
            ->count();

        $renewalsDueThisMonth = $this->renewalQueueQuery($year, $barangayId)
            ->whereHas('memberType.feeSchedules', function (Builder $query) use ($selectedYear, $monthStart, $monthEnd): void {
                $query->where('year', $selectedYear)
                    ->where('is_active', true)
                    ->whereBetween('renewal_deadline', [$monthStart->toDateString(), $monthEnd->toDateString()]);
            })
            ->count();

        $unreadInquiries = Query::query()
            ->where('status', 'New')
            ->when($barangayId !== null, function (Builder $query) use ($barangayId): void {
                $query->whereHas('farmer', fn (Builder $farmerQuery) => $farmerQuery->where('barangay_id', $barangayId));
            })
            ->count();

        $mortuaryInProcess = MortuaryClaim::query()
            ->whereIn('status', ['Pending', 'Approved'])
            ->when($barangayId !== null, function (Builder $query) use ($barangayId): void {
                $query->whereHas('membershipLedger.membershipTransaction.farmer', fn (Builder $farmerQuery) => $farmerQuery->where('barangay_id', $barangayId));
            })
            ->count();

        $recentPaymentsQuery = Payment::query()
            ->with([
                'paymentAssessment.membershipTransaction.farmer.profile:id,farmer_id,first_name,middle_name,last_name,suffix',
                'paymentAssessment.membershipTransaction.farmer:id,farmer_code,barangay_id',
            ])
            ->whereDate('paid_at', $today->toDateString())
            ->when($barangayId !== null, function (Builder $query) use ($barangayId): void {
                $query->whereHas('paymentAssessment.membershipTransaction.farmer', fn (Builder $farmerQuery) => $farmerQuery->where('barangay_id', $barangayId));
            });

        $recentPaymentsTotal = round((float) (clone $recentPaymentsQuery)->sum('amount_paid'), 2);

        $recentPayments = $recentPaymentsQuery
            ->latest('paid_at')
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(function (Payment $payment): array {
                $farmer = $payment->paymentAssessment?->membershipTransaction?->farmer;

                return [
                    'id' => $payment->id,
                    'amount' => (float) $payment->amount_paid,
                    'paidAt' => optional($payment->paid_at)->format('M d, Y h:i A'),
                    'farmerName' => $farmer?->full_name ?: ($farmer?->farmer_code ?? 'Unknown Farmer'),
                    'farmerCode' => $farmer?->farmer_code,
                ];
            })
            ->all();

        $incompleteFarmerRecords = Farmer::query()
            ->when($barangayId !== null, fn (Builder $query) => $query->where('barangay_id', $barangayId))
            ->where(function (Builder $query): void {
                $query->whereNull('barangay_id')
                    ->orWhereNull('member_type_id')
                    ->orWhereNull('association_id')
                    ->orWhereDoesntHave('profile')
                    ->orWhereHas('profile', function (Builder $profileQuery): void {
                        $profileQuery
                            ->whereNull('first_name')
                            ->orWhere('first_name', '')
                            ->orWhereNull('last_name')
                            ->orWhere('last_name', '')
                            ->orWhereNull('mobile_number')
                            ->orWhere('mobile_number', '')
                            ->orWhereNull('address')
                            ->orWhere('address', '');
                    });
            })
            ->count();

        return [
            'queueCards' => [
                [
                    'key' => 'today_pending_applications',
                    'label' => "Today's Pending Applications",
                    'value' => $pendingApplicationsToday,
                    'meta' => 'New applications submitted today that still need review.',
                    'href' => route('admin.membership-applications.index', ['status' => 'pending']),
                    'tone' => 'emerald',
                ],
                [
                    'key' => 'renewals_due_this_month',
                    'label' => 'Renewals Due This Month',
                    'value' => $renewalsDueThisMonth,
                    'meta' => 'Active farmers whose renewal deadline falls within the current month.',
                    'href' => route('admin.renewals.index', ['queue_year' => $selectedYear]),
                    'tone' => 'lime',
                ],
                [
                    'key' => 'unread_inquiries',
                    'label' => 'Unread Farmer Inquiries',
                    'value' => $unreadInquiries,
                    'meta' => 'Open farmer messages that still need response or action.',
                    'href' => route('admin.queries.index', ['status' => 'New']),
                    'tone' => 'sky',
                ],
                [
                    'key' => 'mortuary_in_process',
                    'label' => 'Mortuary Cases In Process',
                    'value' => $mortuaryInProcess,
                    'meta' => 'Pending or approved mortuary cases that are not yet fully closed.',
                    'href' => route('admin.mortuary-claims.index', ['section' => 'records']),
                    'tone' => 'rose',
                ],
            ],
            'recentPayments' => [
                'totalCollectedToday' => $recentPaymentsTotal,
                'href' => route('admin.analytics.index'),
                'items' => $recentPayments,
            ],
            'alerts' => [
                [
                    'key' => 'incomplete_farmer_records',
                    'label' => 'Incomplete Farmer Records',
                    'value' => $incompleteFarmerRecords,
                    'meta' => 'Farmer profiles missing required registry information.',
                    'href' => route('admin.farmers.index'),
                ],
            ],
        ];
    }

    private function memberTypeBreakdown(?int $year, ?int $barangayId): array
    {
        return MemberType::query()
            ->withCount(['farmers as filtered_farmers_count' => function (Builder $query) use ($year, $barangayId): void {
                $this->applyFarmerYearFilter($query, $year);

                if ($barangayId !== null) {
                    $query->where('barangay_id', $barangayId);
                }
            }])
            ->orderByDesc('filtered_farmers_count')
            ->get(['id', 'name'])
            ->filter(fn (MemberType $memberType): bool => (int) $memberType->filtered_farmers_count > 0)
            ->map(fn (MemberType $memberType): array => [
                'label' => $memberType->name,
                'total' => (int) $memberType->filtered_farmers_count,
            ])
            ->values()
            ->all();
    }

    private function topBarangays(?int $year, ?int $barangayId): array
    {
        return Barangay::query()
            ->when($barangayId !== null, fn (Builder $query) => $query->whereKey($barangayId))
            ->withCount(['farmers as filtered_farmers_count' => function (Builder $query) use ($year): void {
                $this->applyFarmerYearFilter($query, $year);
            }])
            ->orderByDesc('filtered_farmers_count')
            ->limit(8)
            ->get(['id', 'name'])
            ->filter(fn (Barangay $barangay): bool => (int) $barangay->filtered_farmers_count > 0)
            ->map(fn (Barangay $barangay): array => [
                'label' => $barangay->name,
                'total' => (int) $barangay->filtered_farmers_count,
            ])
            ->values()
            ->all();
    }

    private function topAssociations(?int $year, ?int $barangayId): array
    {
        return Association::query()
            ->when($barangayId !== null, fn (Builder $query) => $query->where('barangay_id', $barangayId))
            ->withCount(['farmers as filtered_farmers_count' => function (Builder $query) use ($year): void {
                $this->applyFarmerYearFilter($query, $year);
            }])
            ->orderByDesc('filtered_farmers_count')
            ->limit(8)
            ->get(['id', 'name'])
            ->filter(fn (Association $association): bool => (int) $association->filtered_farmers_count > 0)
            ->map(fn (Association $association): array => [
                'label' => $association->name,
                'total' => (int) $association->filtered_farmers_count,
            ])
            ->values()
            ->all();
    }

    private function feeSchedules(?int $year): array
    {
        return FeeSchedule::query()
            ->with('memberType:id,name')
            ->when($year !== null, fn (Builder $query) => $query->where('year', $year))
            ->orderByDesc('is_active')
            ->orderByDesc('year')
            ->orderBy('member_type_id')
            ->get()
            ->map(fn (FeeSchedule $schedule): array => [
                'memberType' => $schedule->memberType?->name ?? 'Unassigned',
                'year' => (int) $schedule->year,
                'membershipFee' => (float) $schedule->membership_fee,
                'annualDue' => (float) $schedule->annual_due,
                'mortuaryFee' => (float) $schedule->mortuary_fee,
                'effectiveFrom' => optional($schedule->effective_from)?->format('Y-m-d'),
                'effectiveTo' => optional($schedule->effective_to)?->format('Y-m-d'),
                'isActive' => (bool) $schedule->is_active,
            ])
            ->all();
    }

    private function recentFarmers(?int $year, ?int $barangayId): array
    {
        return $this->farmerQuery($year, $barangayId)
            ->withCount([
                'membershipLedgers as selected_year_settled_membership_count' => fn (Builder $query) => $this->applySettledMembershipYearConstraint($query, $year),
            ])
            ->with([
                'profile:id,farmer_id,first_name,middle_name,last_name,suffix',
                'barangay:id,name',
                'association:id,name',
                'memberType:id,name',
            ])
            ->orderByDesc('registered_at')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map(function (Farmer $farmer) use ($year): array {
                $profile = $farmer->profile;
                $fullName = trim(collect([
                    $profile?->first_name,
                    $profile?->middle_name,
                    $profile?->last_name,
                    $profile?->suffix,
                ])->filter()->implode(' '));

                return [
                    'id' => $farmer->id,
                    'farmerCode' => $farmer->farmer_code,
                    'fullName' => $fullName !== '' ? $fullName : $farmer->farmer_code,
                    'barangay' => $farmer->barangay?->name,
                    'association' => $farmer->association?->name,
                    'memberType' => $farmer->memberType?->name,
                    'membershipStatus' => (int) ($farmer->selected_year_settled_membership_count ?? 0) > 0
                        ? MembershipStatus::ACTIVE->value
                        : ($farmer->membership_status?->value ?? null),
                    'membershipStatusLabel' => (int) ($farmer->selected_year_settled_membership_count ?? 0) > 0
                        ? MembershipStatus::ACTIVE->label()
                        : $farmer->membership_status?->label(),
                    'farmerStatus' => $this->dashboardFarmerStatusForYear($farmer, $year)->value,
                    'registeredAt' => optional($farmer->registered_at ?? $farmer->created_at)?->format('Y-m-d H:i'),
                ];
            })
            ->all();
    }

    private function officeUsers(): array
    {
        return User::query()
            ->with('officeProfile:user_id,employee_id,job_title')
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_STAFF])
            ->orderBy('role')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'status'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
                'employeeId' => $user->officeProfile?->employee_id,
                'jobTitle' => $user->officeProfile?->job_title,
            ])
            ->all();
    }

    private function availableYears(?int $selectedYear): array
    {
        $years = collect([
            now()->year,
            $selectedYear,
        ])
            ->merge(
                Farmer::query()
                    ->selectRaw('DISTINCT YEAR(COALESCE(registered_at, created_at)) as year_value')
                    ->where(function (Builder $query): void {
                        $query->whereNotNull('registered_at')
                            ->orWhereNotNull('created_at');
                    })
                    ->pluck('year_value')
            )
            ->merge(
                FeeSchedule::query()
                    ->select('year')
                    ->distinct()
                    ->pluck('year')
            )
            ->filter()
            ->map(fn ($year) => (int) $year)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        return $years === [] && $selectedYear !== null ? [$selectedYear] : $years;
    }

    private function farmerQuery(?int $year, ?int $barangayId): Builder
    {
        return Farmer::query()
            ->when($barangayId !== null, fn (Builder $query) => $query->where('barangay_id', $barangayId))
            ->when($year !== null, fn (Builder $query) => $query->where(function (Builder $builder) use ($year): void {
                $builder->whereYear('registered_at', $year)
                    ->orWhere(function (Builder $fallbackQuery) use ($year): void {
                        $fallbackQuery->whereNull('registered_at')
                            ->whereYear('created_at', $year);
                    });
            }));
    }

    private function membershipApplicationQuery(?int $year, ?int $barangayId): Builder
    {
        return MembershipApplication::query()
            ->when($barangayId !== null, function (Builder $query) use ($barangayId): void {
                $query->whereHas('farmer', fn (Builder $farmerQuery) => $farmerQuery->where('barangay_id', $barangayId));
            })
            ->when($year !== null, fn (Builder $query) => $query->where(function (Builder $builder) use ($year): void {
                $builder->whereYear('submitted_at', $year)
                    ->orWhere(function (Builder $fallbackQuery) use ($year): void {
                        $fallbackQuery->whereNull('submitted_at')
                            ->whereYear('created_at', $year);
                    });
            }));
    }

    private function renewalQueueQuery(?int $year, ?int $barangayId): Builder
    {
        $selectedYear = $year ?? now()->year;

        return Farmer::query()
            ->when($barangayId !== null, fn (Builder $query) => $query->where('barangay_id', $barangayId))
            ->whereNull('inactive_at')
            ->where(function (Builder $query) use ($selectedYear): void {
                $query
                    ->where('membership_status', MembershipStatus::ACTIVE->value)
                    ->orWhereHas('membershipLedgers', fn (Builder $ledgerQuery) => $this->applySettledMembershipYearConstraint($ledgerQuery, $selectedYear - 1));
            })
            ->whereDoesntHave('membershipLedgers', fn (Builder $ledgerQuery) => $this->applySettledMembershipYearConstraint($ledgerQuery, $selectedYear));
    }

    private function renewalRecordQuery(?int $year, ?int $barangayId): Builder
    {
        return RenewalRequest::query()
            ->when($barangayId !== null, function (Builder $query) use ($barangayId): void {
                $query->whereHas('farmer', fn (Builder $farmerQuery) => $farmerQuery->where('barangay_id', $barangayId));
            })
            ->when($year !== null, fn (Builder $query) => $query->where('year', $year));
    }

    private function queryQueueQuery(?int $year, ?int $barangayId): Builder
    {
        return Query::query()
            ->when($barangayId !== null, function (Builder $query) use ($barangayId): void {
                $query->whereHas('farmer', fn (Builder $farmerQuery) => $farmerQuery->where('barangay_id', $barangayId));
            })
            ->when($year !== null, fn (Builder $query) => $query->whereYear('created_at', $year));
    }

    private function mortuaryQueueQuery(?int $year, ?int $barangayId): Builder
    {
        return MortuaryClaim::query()
            ->when($barangayId !== null, function (Builder $query) use ($barangayId): void {
                $query->whereHas('membershipLedger.membershipTransaction.farmer', fn (Builder $farmerQuery) => $farmerQuery->where('barangay_id', $barangayId));
            })
            ->when($year !== null, fn (Builder $query) => $query->whereYear('claim_date', $year))
            ->where('status', 'Pending');
    }

    private function applyFarmerYearFilter(Builder $query, ?int $year): void
    {
        if ($year === null) {
            return;
        }

        $query->where(function (Builder $builder) use ($year): void {
            $builder->whereYear('registered_at', $year)
                ->orWhere(function (Builder $fallbackQuery) use ($year): void {
                    $fallbackQuery->whereNull('registered_at')
                        ->whereYear('created_at', $year);
                });
        });
    }

    private function applyFarmerStatusFilter(Builder $query, FarmerStatus $status): Builder
    {
        return $this->applyFarmerStatusFilterForYear($query, $status, now()->year);
    }

    private function applyFarmerStatusFilterForYear(Builder $query, FarmerStatus $status, ?int $year): Builder
    {
        return match ($status) {
            FarmerStatus::ACTIVE => $query
                ->whereNull('inactive_at')
                ->whereHas('membershipLedgers', fn (Builder $ledgerQuery) => $this->applySettledMembershipYearConstraint($ledgerQuery, $year)),
            FarmerStatus::INACTIVE => $query
                ->where(function (Builder $builder): void {
                    $builder
                        ->whereNotNull('inactive_at')
                        ->where(function (Builder $reasonBuilder): void {
                            $reasonBuilder->whereNull('inactive_reason')
                                ->orWhere('inactive_reason', '')
                                ->orWhereRaw("LOWER(COALESCE(inactive_reason, '')) NOT LIKE '%deceas%'");
                        });
                }),
            FarmerStatus::DECEASED => $query
                ->whereNotNull('inactive_at')
                ->whereRaw("LOWER(COALESCE(inactive_reason, '')) LIKE '%deceas%'"),
            FarmerStatus::PENDING => $query
                ->whereNull('inactive_at')
                ->whereDoesntHave('membershipLedgers', fn (Builder $ledgerQuery) => $this->applySettledMembershipYearConstraint($ledgerQuery, $year)),
        };
    }

    private function dashboardFarmerStatusForYear(Farmer $farmer, ?int $year): FarmerStatus
    {
        if ($farmer->inactive_at !== null) {
            return str_contains(strtolower((string) $farmer->inactive_reason), 'deceas')
                ? FarmerStatus::DECEASED
                : FarmerStatus::INACTIVE;
        }

        if ((int) ($farmer->selected_year_settled_membership_count ?? 0) > 0) {
            return FarmerStatus::ACTIVE;
        }

        return FarmerStatus::PENDING;
    }

    private function applySettledMembershipYearConstraint(Builder $query, ?int $year): void
    {
        if ($year === null) {
            $query->where(function (Builder $ledgerBuilder): void {
                $ledgerBuilder
                    ->where('membership_ledgers.amount_paid', '>', 0)
                    ->orWhereIn('membership_ledgers.payment_status', ['Paid', 'Overpaid', 'Waived']);
            });

            return;
        }

        $query
            ->where('membership_ledgers.year', $year)
            ->where(function (Builder $ledgerBuilder): void {
                $ledgerBuilder
                    ->where('membership_ledgers.amount_paid', '>', 0)
                    ->orWhereIn('membership_ledgers.payment_status', ['Paid', 'Overpaid', 'Waived']);
            });
    }

    /**
     * @return array<int, string>
     */
    private function databaseStatusesForPendingQueue(): array
    {
        return [
            $this->databaseStatusValue(ApplicationStatus::SUBMITTED),
            $this->databaseStatusValue(ApplicationStatus::UNDER_REVIEW),
        ];
    }

    private function databaseStatusValue(ApplicationStatus $status): string
    {
        return match ($status) {
            ApplicationStatus::APPROVED => 'Approved',
            ApplicationStatus::REJECTED => 'Rejected',
            default => 'Pending',
        };
    }

    private function applicationTasks(int $userId, array $filters): array
    {
        if (($filters['module'] ?? '') !== '' && $filters['module'] !== 'applications') {
            return [];
        }

        $query = MembershipApplication::query()
            ->with(['farmer.profile:id,farmer_id,first_name,last_name', 'farmer:id,farmer_code'])
            ->whereIn('status', $this->databaseStatusesForPendingQueue());

        return $query->get()->map(function (MembershipApplication $application): array {
            $submittedAt = $application->submitted_at ?? $application->created_at;
            $isOverdue = optional($submittedAt)?->lt(now()->subDays(2)) ?? false;

            return [
                'key' => 'application-' . $application->id,
                'module' => 'applications',
                'moduleLabel' => 'Application',
                'title' => $application->application_no ?: 'Application',
                'subject' => $application->farmer?->full_name ?? 'Unknown farmer',
                'meta' => $application->farmer?->farmer_code,
                'status' => 'Pending review',
                'timing' => optional($submittedAt)?->isToday() ? 'pending_today' : ($isOverdue ? 'overdue' : 'queued'),
                'timingLabel' => optional($submittedAt)?->isToday() ? 'Pending today' : ($isOverdue ? 'Overdue' : 'Queued'),
                'priority' => $isOverdue ? 'high_priority' : 'normal',
                'priorityLabel' => $isOverdue ? 'High priority' : 'Normal',
                'submittedAt' => optional($submittedAt)?->format('M d, Y h:i A'),
                'sort_at' => optional($submittedAt)?->timestamp ?? 0,
                'url' => route('admin.membership-applications.show', $application),
                'recordKey' => (string) $application->getRouteKey(),
                'quickActions' => [
                    'canReview' => true,
                    'canMarkComplete' => $application->source !== 'walk_in',
                    'canRequestCorrection' => true,
                    'canForwardToAdmin' => true,
                ],
            ];
        })->filter(fn (array $task): bool => $this->matchesTaskFilters($task, $filters))->values()->all();
    }

    private function renewalTasks(int $userId, array $filters): array
    {
        if (($filters['module'] ?? '') !== '' && $filters['module'] !== 'renewals') {
            return [];
        }

        $query = RenewalRequest::query()
            ->with(['farmer.profile:id,farmer_id,first_name,last_name', 'farmer:id,farmer_code'])
            ->where('status', 'Pending');

        return $query->get()->map(function (RenewalRequest $renewal): array {
            $submittedAt = $renewal->submitted_at ?? $renewal->created_at;
            $isOverdue = optional($submittedAt)?->lt(now()->subDays(3)) ?? false;

            return [
                'key' => 'renewal-' . $renewal->id,
                'module' => 'renewals',
                'moduleLabel' => 'Renewal',
                'title' => $renewal->application_no ?: 'Renewal',
                'subject' => $renewal->farmer?->full_name ?? 'Unknown farmer',
                'meta' => $renewal->farmer?->farmer_code,
                'status' => 'Needs review',
                'timing' => optional($submittedAt)?->isToday() ? 'pending_today' : ($isOverdue ? 'overdue' : 'queued'),
                'timingLabel' => optional($submittedAt)?->isToday() ? 'Pending today' : ($isOverdue ? 'Overdue' : 'Queued'),
                'priority' => $isOverdue ? 'high_priority' : 'normal',
                'priorityLabel' => $isOverdue ? 'High priority' : 'Normal',
                'submittedAt' => optional($submittedAt)?->format('M d, Y h:i A'),
                'sort_at' => optional($submittedAt)?->timestamp ?? 0,
                'url' => route('admin.renewals.show', $renewal),
                'recordKey' => (string) $renewal->getRouteKey(),
                'quickActions' => [
                    'canReview' => true,
                    'canMarkComplete' => Auth::user()?->hasRole(User::ROLE_ADMIN) && $renewal->source !== 'walk_in',
                    'canRequestCorrection' => Auth::user()?->hasRole(User::ROLE_ADMIN) ?? false,
                    'canForwardToAdmin' => true,
                ],
            ];
        })->filter(fn (array $task): bool => $this->matchesTaskFilters($task, $filters))->values()->all();
    }

    private function inquiryTasks(int $userId, array $filters): array
    {
        if (($filters['module'] ?? '') !== '' && $filters['module'] !== 'inquiries') {
            return [];
        }

        $query = Query::query()
            ->with(['farmer.profile:id,farmer_id,first_name,last_name', 'farmer:id,farmer_code'])
            ->whereIn('status', ['New', 'In Progress', 'Escalated']);

        return $query->get()->map(function (Query $query): array {
            $createdAt = $query->created_at;
            $isOverdue = optional($createdAt)?->lt(now()->subDays(1)) ?? false;

            return [
                'key' => 'inquiry-' . $query->id,
                'module' => 'inquiries',
                'moduleLabel' => 'Inquiry',
                'title' => $query->subject,
                'subject' => $query->farmer?->full_name ?? 'Unknown farmer',
                'meta' => $query->farmer?->farmer_code,
                'status' => 'Awaiting response',
                'timing' => optional($createdAt)?->isToday() ? 'pending_today' : ($isOverdue ? 'overdue' : 'queued'),
                'timingLabel' => optional($createdAt)?->isToday() ? 'Pending today' : ($isOverdue ? 'Overdue' : 'Queued'),
                'priority' => $isOverdue ? 'high_priority' : 'normal',
                'priorityLabel' => $isOverdue ? 'High priority' : 'Normal',
                'submittedAt' => optional($createdAt)?->format('M d, Y h:i A'),
                'sort_at' => optional($createdAt)?->timestamp ?? 0,
                'url' => route('admin.queries.show', $query),
                'recordKey' => (string) $query->getRouteKey(),
                'quickActions' => [
                    'canReview' => true,
                    'canMarkComplete' => true,
                    'canRequestCorrection' => false,
                    'canForwardToAdmin' => true,
                ],
            ];
        })->filter(fn (array $task): bool => $this->matchesTaskFilters($task, $filters))->values()->all();
    }

    private function incompleteFarmerTasks(array $filters): array
    {
        if (($filters['module'] ?? '') !== '' && $filters['module'] !== 'farmers') {
            return [];
        }

        return Farmer::query()
            ->with(['profile:id,farmer_id,first_name,last_name'])
            ->where(function (Builder $query): void {
                $query->whereNull('member_type_id')
                    ->orWhereNull('barangay_id')
                    ->orWhereDoesntHave('profile')
                    ->orWhereHas('profile', function (Builder $profileQuery): void {
                        $profileQuery
                            ->whereNull('first_name')
                            ->orWhere('first_name', '')
                            ->orWhereNull('last_name')
                            ->orWhere('last_name', '')
                            ->orWhereNull('birth_date')
                            ->orWhereNull('address')
                            ->orWhere('address', '')
                            ->orWhereNull('mobile_number')
                            ->orWhere('mobile_number', '');
                    });
            })
            ->latest('updated_at')
            ->limit(50)
            ->get()
            ->map(function (Farmer $farmer): array {
                $updatedAt = $farmer->updated_at ?? $farmer->created_at;

                return [
                    'key' => 'farmer-' . $farmer->id,
                    'module' => 'farmers',
                    'moduleLabel' => 'Farmer Record',
                    'title' => $farmer->full_name ?: $farmer->farmer_code,
                    'subject' => 'Incomplete registry profile',
                    'meta' => $farmer->farmer_code,
                    'status' => 'Needs completion',
                    'timing' => optional($updatedAt)?->isToday() ? 'pending_today' : 'queued',
                    'timingLabel' => optional($updatedAt)?->isToday() ? 'Pending today' : 'Queued',
                    'priority' => 'high_priority',
                    'priorityLabel' => 'High priority',
                    'submittedAt' => optional($updatedAt)?->format('M d, Y h:i A'),
                    'sort_at' => optional($updatedAt)?->timestamp ?? 0,
                    'url' => route('admin.farmers.show', $farmer),
                ];
            })
            ->filter(fn (array $task): bool => $this->matchesTaskFilters($task, $filters))
            ->values()
            ->all();
    }

    private function advisoryTasks(array $filters): array
    {
        if (($filters['module'] ?? '') !== '' && $filters['module'] !== 'advisories') {
            return [];
        }

        return Advisory::query()
            ->select(['id', 'title', 'status', 'audience_type', 'barangay_id', 'member_type_id', 'created_at', 'published_at'])
            ->with(['barangay:id,name', 'memberType:id,code,name'])
            ->where('status', 'Draft')
            ->latest('created_at')
            ->get()
            ->map(function (Advisory $advisory): array {
                $createdAt = $advisory->created_at;
                $isOverdue = optional($createdAt)?->lt(now()->subDays(2)) ?? false;

                return [
                    'key' => 'advisory-' . $advisory->id,
                    'module' => 'advisories',
                    'moduleLabel' => 'Advisory',
                    'title' => $advisory->title,
                    'subject' => $this->advisoryAudienceLabel($advisory),
                    'meta' => $advisory->status,
                    'status' => 'Draft advisory',
                    'timing' => optional($createdAt)?->isToday() ? 'pending_today' : ($isOverdue ? 'overdue' : 'queued'),
                    'timingLabel' => optional($createdAt)?->isToday() ? 'Pending today' : ($isOverdue ? 'Overdue' : 'Queued'),
                    'priority' => $isOverdue ? 'high_priority' : 'normal',
                    'priorityLabel' => $isOverdue ? 'High priority' : 'Normal',
                    'submittedAt' => optional($createdAt)?->format('M d, Y h:i A'),
                    'sort_at' => optional($createdAt)?->timestamp ?? 0,
                    'url' => route('admin.advisories.show', $advisory),
                    'recordKey' => (string) $advisory->getRouteKey(),
                    'quickActions' => [
                        'canMarkComplete' => false,
                        'canRequestCorrection' => false,
                        'canForwardToAdmin' => false,
                    ],
                ];
            })
            ->filter(fn (array $task): bool => $this->matchesTaskFilters($task, $filters))
            ->values()
            ->all();
    }

    private function advisoryAudienceLabel(Advisory $advisory): string
    {
        return match ($advisory->audience_type) {
            'barangay' => 'Barangay: ' . ($advisory->barangay?->name ?? 'Not set'),
            'group' => 'Member type: ' . ($advisory->memberType ? trim($advisory->memberType->code . ' - ' . $advisory->memberType->name, ' -') : 'Not set'),
            default => 'All farmers',
        };
    }

    private function matchesTaskFilters(array $task, array $filters): bool
    {
        if (($filters['timing'] ?? '') === 'pending_today' && $task['timing'] !== 'pending_today') {
            return false;
        }

        if (($filters['timing'] ?? '') === 'overdue' && $task['timing'] !== 'overdue') {
            return false;
        }

        if (($filters['priority'] ?? '') === 'high_priority' && $task['priority'] !== 'high_priority') {
            return false;
        }

        return true;
    }

    private function handleApplicationQuickAction(string $recordKey, string $action, ?string $note, User $user): void
    {
        $application = MembershipApplication::query()->where('application_no', $recordKey)->firstOrFail();

        match ($action) {
            'mark_complete' => $this->membershipApplicationService->approve($application, $user->id),
            'request_correction' => $this->membershipApplicationService->reject(
                $application,
                MembershipApplicationRejectionReason::OTHER->value,
                $note ?: 'Please review the submitted details and correct the application before resubmission.',
                $user->id,
            ),
            'forward_to_admin' => $this->forwardTaskToAdmins(
                module: 'membership_applications',
                type: NotificationType::MEMBERSHIP_APPLICATION_UPDATED,
                subject: 'Staff escalation for membership application',
                message: 'A staff member forwarded a membership application for admin review.',
                subjectModel: $application,
                actor: $user,
                targetUrl: route('admin.membership-applications.show', $application),
                metadata: [
                    'application_no' => $application->application_no,
                    'note' => $note,
                ],
            ),
            default => throw new DomainException('Unsupported application quick action.'),
        };
    }

    private function handleRenewalQuickAction(string $recordKey, string $action, ?string $note, User $user): void
    {
        $renewal = RenewalRequest::query()->whereKey(app(RenewalRequest::class)->resolveRouteBindingQuery(RenewalRequest::query(), $recordKey)->firstOrFail()->getKey())->firstOrFail();

        match ($action) {
            'mark_complete' => $user->hasRole(User::ROLE_ADMIN)
                ? $this->renewalRequestService->approve($renewal, $user->id)
                : throw new DomainException('Only administrators can complete renewal approvals from the task queue.'),
            'request_correction' => $user->hasRole(User::ROLE_ADMIN)
                ? $this->renewalRequestService->reject(
                    $renewal,
                    $note ?: 'Please correct the renewal details and resubmit for review.',
                    $user->id,
                )
                : throw new DomainException('Only administrators can request renewal corrections from the task queue.'),
            'forward_to_admin' => $this->forwardTaskToAdmins(
                module: 'renewals',
                type: NotificationType::RENEWAL_REQUEST_SUBMITTED,
                subject: 'Staff escalation for renewal request',
                message: 'A staff member forwarded a renewal request for admin review.',
                subjectModel: $renewal,
                actor: $user,
                targetUrl: route('admin.renewals.show', $renewal),
                metadata: [
                    'year' => $renewal->year,
                    'note' => $note,
                ],
            ),
            default => throw new DomainException('Unsupported renewal quick action.'),
        };
    }

    private function handleInquiryQuickAction(string $recordKey, string $action, ?string $note, User $user): void
    {
        $query = Query::query()->whereKey(app(Query::class)->resolveRouteBindingQuery(Query::query(), $recordKey)->firstOrFail()->getKey())->firstOrFail();

        match ($action) {
            'mark_complete' => tap($this->queryWorkflowService->close($query), function (array $payload) use ($query, $user): void {
                $query->forceFill(['status' => $payload['status']])->save();
                $this->auditTrailService->record(
                    'queries',
                    'query_closed',
                    'Closed a farmer inquiry from the task queue.',
                    $user,
                    $query,
                    ['subject' => $query->subject]
                );
            }),
            'forward_to_admin' => $this->forwardTaskToAdmins(
                module: 'queries',
                type: NotificationType::QUERY_RECEIVED,
                subject: 'Staff escalation for farmer inquiry',
                message: 'A staff member forwarded a farmer inquiry for admin review.',
                subjectModel: $query,
                actor: $user,
                targetUrl: route('admin.queries.show', $query),
                metadata: [
                    'subject' => $query->subject,
                    'note' => $note,
                ],
            ),
            default => throw new DomainException('Unsupported inquiry quick action.'),
        };
    }

    private function forwardTaskToAdmins(
        string $module,
        NotificationType $type,
        string $subject,
        string $message,
        object $subjectModel,
        User $actor,
        string $targetUrl,
        array $metadata = [],
    ): void {
        $recipients = User::query()
            ->where('role', User::ROLE_ADMIN)
            ->where('status', User::STATUS_ACTIVE)
            ->get(['id']);

        if ($recipients->isEmpty()) {
            throw new DomainException('No active administrator account is available for forwarding.');
        }

        $this->notificationDispatchService->persist($type, $recipients, [
            'subject' => $subject,
            'message' => $message,
            'target_url' => $targetUrl,
            ...$metadata,
        ], $actor->id);

        $this->auditTrailService->record(
            $module,
            'task_forwarded_to_admin',
            'Forwarded a queued record to an administrator.',
            $actor,
            $subjectModel,
            $this->auditTrailService->activityMetadata(
                AuditTrailService::ACTION_PROCESSED,
                'Forwarded to admin',
                $metadata
            )
        );
    }
}
