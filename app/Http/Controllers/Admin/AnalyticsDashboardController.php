<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AssessmentStatus;
use App\Enums\DocumentVerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\AnalyticsEvent;
use App\Models\FarmerDocument;
use App\Models\MortuaryClaim;
use App\Models\MembershipApplication;
use App\Models\Payment;
use App\Models\Query;
use App\Models\RenewalRequest;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AnalyticsDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('role.in:' . User::ROLE_ADMIN);
    }

    public function index(Request $request): Response
    {
        [$from, $to, $days] = $this->resolveRange($request);
        $analytics = $this->buildAnalyticsPayload($from, $to, $days);

        return Inertia::render('Admin/Analytics/Index', [
            'analytics' => $analytics,
        ]);
    }

    public function export(Request $request): StreamedResponse|BinaryFileResponse|\Illuminate\Http\Response
    {
        [$from, $to, $days] = $this->resolveRange($request);
        $analytics = $this->buildAnalyticsPayload($from, $to, $days);
        $format = strtolower((string) $request->query('format', 'csv'));
        $fileBase = 'analytics-' . $from->format('Ymd') . '-' . $to->format('Ymd');

        if ($format === 'xlsx') {
            return Excel::download(
                new \App\Exports\AnalyticsSummaryExport($this->analyticsExportRows($analytics)),
                $fileBase . '.xlsx'
            );
        }

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('admin.analytics.export-pdf', [
                'analytics' => $analytics,
                'generatedAt' => now()->format('F d, Y h:i A'),
            ])->setPaper('a4', 'portrait');

            if ($request->boolean('preview')) {
                return response($pdf->output(), 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="' . $fileBase . '.pdf"',
                ]);
            }

            return response()->streamDownload(
                static function () use ($pdf): void {
                    echo $pdf->output();
                },
                $fileBase . '.pdf',
                ['Content-Type' => 'application/pdf']
            );
        }

        $events = AnalyticsEvent::query()
            ->with('user:id,name,role')
            ->whereBetween('occurred_at', [$from, $to])
            ->latest('occurred_at')
            ->get();

        $filename = $fileBase . '.csv';

        return response()->streamDownload(function () use ($events): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Occurred At', 'Event Name', 'Module', 'Actor', 'Role', 'Page', 'Route Name', 'URL']);

            foreach ($events as $event) {
                fputcsv($handle, [
                    optional($event->occurred_at)->toDateTimeString(),
                    $event->event_name,
                    $event->module,
                    $event->user?->name,
                    $event->user?->role,
                    $event->page,
                    $event->route_name,
                    $event->url,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function buildAnalyticsPayload($from, $to, int $days): array
    {
        $baseQuery = AnalyticsEvent::query()
            ->whereBetween('occurred_at', [$from, $to]);

        $summary = [
            'pageViews' => (clone $baseQuery)->where('event_name', 'page_view')->count(),
            'searches' => (clone $baseQuery)->where('event_name', 'admin_search')->count(),
            'activeUsers' => (clone $baseQuery)->whereNotNull('user_id')->distinct('user_id')->count('user_id'),
        ];

        $trend = collect(range(0, $days - 1))
            ->map(function (int $offset) use ($from): array {
                $date = $from->copy()->addDays($offset);

                return [
                    'date' => $date->toDateString(),
                    'label' => $date->format('M d'),
                    'pageViews' => 0,
                    'searches' => 0,
                ];
            })
            ->keyBy('date');

        $dailyRows = (clone $baseQuery)
            ->selectRaw('DATE(occurred_at) as event_date')
            ->selectRaw("SUM(CASE WHEN event_name = 'page_view' THEN 1 ELSE 0 END) as page_views")
            ->selectRaw("SUM(CASE WHEN event_name = 'admin_search' THEN 1 ELSE 0 END) as searches")
            ->groupBy('event_date')
            ->orderBy('event_date')
            ->get();

        foreach ($dailyRows as $row) {
            if (! $trend->has($row->event_date)) {
                continue;
            }

            $trendRow = $trend->get($row->event_date);
            $trendRow['pageViews'] = (int) $row->page_views;
            $trendRow['searches'] = (int) $row->searches;
            $trend->put($row->event_date, $trendRow);
        }

        $applicationTrend = $this->buildDailyEventTrend(
            clone $baseQuery,
            $from,
            $days,
            [
                'membership_application_created' => 'created',
                'membership_application_approved' => 'approved',
                'membership_application_rejected' => 'rejected',
            ],
        );

        $renewalTrend = $this->buildDailyEventTrend(
            clone $baseQuery,
            $from,
            $days,
            [
                'renewal_created' => 'created',
                'renewal_approved' => 'approved',
                'renewal_rejected' => 'rejected',
            ],
        );

        $paymentTrend = $this->buildDailyEventTrend(
            clone $baseQuery,
            $from,
            $days,
            [
                'membership_application_payment_recorded' => 'applications',
                'renewal_payment_recorded' => 'renewals',
            ],
        );

        $advisoryMonthlyTrend = $this->buildMonthlyEventTrend(
            clone $baseQuery,
            $from,
            $to,
            [
                'advisory_published' => 'published',
            ],
        );

        $topEvents = (clone $baseQuery)
            ->select('event_name')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('event_name')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn ($row): array => [
                'label' => str($row->event_name)->replace('_', ' ')->title()->toString(),
                'value' => (int) $row->total,
            ])
            ->all();

        $applicationRecords = MembershipApplication::query()
            ->whereBetween('submitted_at', [$from, $to]);

        $renewalRecords = RenewalRequest::query()
            ->whereBetween('submitted_at', [$from, $to]);

        $paymentsQuery = Payment::query()
            ->where('status', '!=', 'Rejected')
            ->whereBetween('paid_at', [$from, $to]);

        $queueAging = [
            'applications' => $this->queueAging(MembershipApplication::query()->where('status', 'Pending')),
            'renewals' => $this->queueAging(RenewalRequest::query()->where('status', 'Pending')),
        ];

        $turnaround = [
            'applications' => $this->averageTurnaround(MembershipApplication::query()->whereBetween('submitted_at', [$from, $to])),
            'renewals' => $this->averageTurnaround(RenewalRequest::query()->whereBetween('submitted_at', [$from, $to])),
        ];

        $funnels = [
            'applications' => [
                'submitted' => (clone $applicationRecords)->count(),
                'approved' => (clone $applicationRecords)->where('status', 'Approved')->count(),
                'rejected' => (clone $applicationRecords)->where('status', 'Rejected')->count(),
                'paid' => (clone $applicationRecords)->whereHas('paymentAssessments', fn ($query) => $query->whereIn('status', ['Paid', 'Partially Paid']))->count(),
                'completed' => (clone $applicationRecords)->whereHas('paymentAssessments', fn ($query) => $query->where('status', 'Paid'))->count(),
            ],
            'renewals' => [
                'submitted' => (clone $renewalRecords)->count(),
                'approved' => (clone $renewalRecords)->where('status', 'Approved')->count(),
                'rejected' => (clone $renewalRecords)->where('status', 'Rejected')->count(),
                'paid' => (clone $renewalRecords)->whereHas('paymentAssessments', fn ($query) => $query->whereIn('status', ['Paid', 'Partially Paid']))->count(),
                'completed' => (clone $renewalRecords)->whereHas('paymentAssessments', fn ($query) => $query->where('status', 'Paid'))->count(),
            ],
        ];

        $collections = [
            'total' => round((float) (clone $paymentsQuery)->sum('amount_paid'), 2),
            'membershipFees' => round((float) (clone $paymentsQuery)->sum('membership_fee'), 2),
            'annualDue' => round((float) (clone $paymentsQuery)->sum('annual_due'), 2),
            'mortuaryFee' => round((float) (clone $paymentsQuery)->sum('mortuary_fee'), 2),
            'applicationPayments' => round((float) (clone $paymentsQuery)->whereHas('paymentAssessment.membershipTransaction', fn ($query) => $query->where('transaction_type', 'Application'))->sum('amount_paid'), 2),
            'renewalPayments' => round((float) (clone $paymentsQuery)->whereHas('paymentAssessment.membershipTransaction', fn ($query) => $query->where('transaction_type', 'Renewal'))->sum('amount_paid'), 2),
        ];

        $rejectionReasons = collect([
            ...(clone $applicationRecords)->whereNotNull('rejection_reason')->selectRaw('rejection_reason as reason, COUNT(*) as total')->groupBy('rejection_reason')->get()->all(),
            ...(clone $renewalRecords)->whereNotNull('rejection_reason')->selectRaw('rejection_reason as reason, COUNT(*) as total')->groupBy('rejection_reason')->get()->all(),
        ])
            ->groupBy('reason')
            ->map(fn ($rows, $reason): array => [
                'label' => str((string) $reason)->replace('_', ' ')->title()->toString(),
                'value' => (int) collect($rows)->sum('total'),
            ])
            ->sortByDesc('value')
            ->take(8)
            ->values()
            ->all();

        $documentIssues = FarmerDocument::query()
            ->with('documentType:id,name,code')
            ->whereBetween('updated_at', [$from, $to])
            ->where('verification_status', 'Rejected')
            ->get()
            ->groupBy(fn (FarmerDocument $document) => $document->documentType?->name ?? $document->documentType?->code ?? 'Unknown')
            ->map(fn ($rows, $label): array => [
                'label' => (string) $label,
                'value' => $rows->count(),
            ])
            ->sortByDesc('value')
            ->take(8)
            ->values()
            ->all();

        $recentActivity = (clone $baseQuery)
            ->with('user:id,name,role')
            ->latest('occurred_at')
            ->limit(12)
            ->get()
            ->map(fn (AnalyticsEvent $event): array => [
                'id' => $event->id,
                'eventName' => str($event->event_name)->replace('_', ' ')->title()->toString(),
                'module' => $event->module ? str($event->module)->replace('_', ' ')->title()->toString() : 'Uncategorized',
                'actor' => $event->user?->name ?? 'System',
                'actorRole' => $event->user?->role,
                'occurredAt' => optional($event->occurred_at)->format('M d, Y h:i A'),
                'url' => $event->url,
            ])
            ->all();

        $activeInactiveByBarangay = DB::table('farmers')
            ->leftJoin('barangays', 'barangays.id', '=', 'farmers.barangay_id')
            ->selectRaw("COALESCE(barangays.name, 'Unassigned') as barangay")
            ->selectRaw("SUM(CASE WHEN TRIM(COALESCE(farmers.inactive_reason, '')) = '' AND LOWER(COALESCE(farmers.membership_status, '')) = 'active' THEN 1 ELSE 0 END) as active_count")
            ->selectRaw("SUM(CASE WHEN TRIM(COALESCE(farmers.inactive_reason, '')) <> '' OR LOWER(COALESCE(farmers.membership_status, '')) <> 'active' THEN 1 ELSE 0 END) as inactive_count")
            ->groupBy('barangay')
            ->orderByDesc('active_count')
            ->orderBy('barangay')
            ->limit(12)
            ->get()
            ->map(fn ($row): array => [
                'barangay' => (string) $row->barangay,
                'active' => (int) $row->active_count,
                'inactive' => (int) $row->inactive_count,
                'total' => (int) $row->active_count + (int) $row->inactive_count,
            ])
            ->all();

        $renewalCompliance = $this->renewalComplianceByYear();

        $applicationDecisionTrend = DB::table('membership_transactions')
            ->where('transaction_type', 'Application')
            ->whereNotNull('submitted_at')
            ->selectRaw('YEAR(submitted_at) as year')
            ->selectRaw("SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved_count")
            ->selectRaw("SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected_count")
            ->groupByRaw('YEAR(submitted_at)')
            ->orderByRaw('YEAR(submitted_at)')
            ->limit(8)
            ->get()
            ->map(fn ($row): array => [
                'year' => (int) $row->year,
                'approved' => (int) $row->approved_count,
                'rejected' => (int) $row->rejected_count,
            ])
            ->all();

        $inquiryTopics = Query::query()
            ->leftJoin('query_categories', 'query_categories.id', '=', 'queries.category_id')
            ->selectRaw("COALESCE(query_categories.name, queries.subject, 'Uncategorized') as topic")
            ->selectRaw('COUNT(*) as total')
            ->groupBy('topic')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn ($row): array => [
                'label' => str((string) $row->topic)->limit(48)->toString(),
                'value' => (int) $row->total,
            ])
            ->all();

        $paymentCollectionSummary = [
            'verifiedCount' => (clone $paymentsQuery)->count(),
            'averagePayment' => round((float) (clone $paymentsQuery)->avg('amount_paid'), 2),
            'latestPaidAt' => ($latestPaidAt = (clone $paymentsQuery)->max('paid_at'))
                ? \Carbon\Carbon::parse($latestPaidAt)->toDateTimeString()
                : null,
            'byMethod' => Payment::query()
                ->leftJoin('payment_methods', 'payment_methods.id', '=', 'payments.payment_method_id')
                ->where('payments.status', '!=', 'Rejected')
                ->whereBetween('payments.paid_at', [$from, $to])
                ->selectRaw("COALESCE(payment_methods.name, payment_methods.code, 'Unknown') as method")
                ->selectRaw('COUNT(payments.id) as total_count')
                ->selectRaw('SUM(payments.amount_paid) as total_amount')
                ->groupBy('method')
                ->orderByDesc('total_amount')
                ->limit(6)
                ->get()
                ->map(fn ($row): array => [
                    'label' => (string) $row->method,
                    'count' => (int) $row->total_count,
                    'amount' => round((float) $row->total_amount, 2),
                ])
                ->all(),
        ];

        $mobileMonitoring = $this->mobileMonitoring($from, $to);

        $mortuaryClaimsQuery = MortuaryClaim::query()
            ->whereBetween('claim_date', [$from->toDateString(), $to->toDateString()]);

        $mortuaryAssistanceSummary = [
            'totalClaims' => (clone $mortuaryClaimsQuery)->count(),
            'approvedClaims' => (clone $mortuaryClaimsQuery)->where('status', 'Approved')->count(),
            'releasedClaims' => (clone $mortuaryClaimsQuery)->where('status', 'Released')->count(),
            'pendingClaims' => (clone $mortuaryClaimsQuery)->where('status', 'Pending')->count(),
            'rejectedClaims' => (clone $mortuaryClaimsQuery)->where('status', 'Rejected')->count(),
            'totalAmount' => round((float) (clone $mortuaryClaimsQuery)->sum('claim_amount'), 2),
            'averageAmount' => round((float) (clone $mortuaryClaimsQuery)->avg('claim_amount'), 2),
            'byBarangay' => MortuaryClaim::query()
                ->join('membership_ledgers', 'membership_ledgers.id', '=', 'mortuary_claims.membership_ledger_id')
                ->join('membership_transactions', 'membership_transactions.id', '=', 'membership_ledgers.membership_transaction_id')
                ->join('farmers', 'farmers.id', '=', 'membership_transactions.farmer_id')
                ->leftJoin('barangays', 'barangays.id', '=', 'farmers.barangay_id')
                ->whereBetween('mortuary_claims.claim_date', [$from->toDateString(), $to->toDateString()])
                ->selectRaw("COALESCE(barangays.name, 'Unassigned') as barangay")
                ->selectRaw('COUNT(mortuary_claims.id) as total_claims')
                ->selectRaw('SUM(mortuary_claims.claim_amount) as total_amount')
                ->groupBy('barangay')
                ->orderByDesc('total_claims')
                ->limit(8)
                ->get()
                ->map(fn ($row): array => [
                    'label' => (string) $row->barangay,
                    'count' => (int) $row->total_claims,
                    'amount' => round((float) $row->total_amount, 2),
                ])
                ->all(),
        ];

        return [
            'filters' => [
                'days' => $days,
                'options' => [7, 14, 30, 90],
                'baseUrl' => route('admin.analytics.index'),
                'dateFrom' => $from->toDateString(),
                'dateTo' => $to->toDateString(),
            ],
            'summary' => $summary,
            'trend' => array_values($trend->all()),
            'topEvents' => $topEvents,
            'queueAging' => $queueAging,
            'turnaround' => $turnaround,
            'funnels' => $funnels,
            'collections' => $collections,
            'rejectionReasons' => $rejectionReasons,
            'documentIssues' => $documentIssues,
            'applicationTrend' => $applicationTrend,
            'renewalTrend' => $renewalTrend,
            'paymentTrend' => $paymentTrend,
            'advisoryMonthlyTrend' => $advisoryMonthlyTrend,
            'recentActivity' => $recentActivity,
            'activeInactiveByBarangay' => $activeInactiveByBarangay,
            'renewalCompliance' => $renewalCompliance,
            'applicationDecisionTrend' => $applicationDecisionTrend,
            'inquiryTopics' => $inquiryTopics,
            'paymentCollectionSummary' => $paymentCollectionSummary,
            'mobileMonitoring' => $mobileMonitoring,
            'mortuaryAssistanceSummary' => $mortuaryAssistanceSummary,
            'urls' => [
                'export' => route('admin.analytics.export', [
                    'days' => $days,
                    'date_from' => $from->toDateString(),
                    'date_to' => $to->toDateString(),
                ]),
            ],
        ];
    }

    private function resolveDays(?int $days): int
    {
        return in_array($days, [7, 14, 30, 90], true) ? $days : 30;
    }

    private function resolveRange(Request $request): array
    {
        $days = $this->resolveDays($request->integer('days'));
        $fromInput = $request->date('date_from');
        $toInput = $request->date('date_to');

        if ($fromInput && $toInput) {
            $from = $fromInput->startOfDay();
            $to = $toInput->endOfDay();

            if ($from->gt($to)) {
                [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
            }

            return [$from, $to, $days];
        }

        $from = now()->subDays($days - 1)->startOfDay();
        $to = now()->endOfDay();

        return [$from, $to, $days];
    }

    private function buildDailyEventTrend($query, $from, int $days, array $eventMap): array
    {
        $seed = collect(range(0, $days - 1))
            ->map(function (int $offset) use ($from, $eventMap): array {
                $date = $from->copy()->addDays($offset);
                $row = [
                    'date' => $date->toDateString(),
                    'label' => $date->format('M d'),
                ];

                foreach ($eventMap as $series) {
                    $row[$series] = 0;
                }

                return $row;
            })
            ->keyBy('date');

        $rows = $query
            ->whereIn('event_name', array_keys($eventMap))
            ->selectRaw('DATE(occurred_at) as event_date')
            ->selectRaw('event_name')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('event_date', 'event_name')
            ->orderBy('event_date')
            ->get();

        foreach ($rows as $row) {
            if (! $seed->has($row->event_date)) {
                continue;
            }

            $series = $eventMap[$row->event_name] ?? null;

            if ($series === null) {
                continue;
            }

            $seedRow = $seed->get($row->event_date);
            $seedRow[$series] = (int) $row->total;
            $seed->put($row->event_date, $seedRow);
        }

        return array_values($seed->all());
    }

    private function buildMonthlyEventTrend($query, $from, $to, array $eventMap): array
    {
        $months = collect();
        $cursor = $from->copy()->startOfMonth();
        $end = $to->copy()->startOfMonth();

        while ($cursor->lte($end)) {
            $row = [
                'month' => $cursor->format('Y-m'),
                'label' => $cursor->format('M Y'),
            ];

            foreach ($eventMap as $series) {
                $row[$series] = 0;
            }

            $months->put($row['month'], $row);
            $cursor->addMonth();
        }

        $rows = $query
            ->whereIn('event_name', array_keys($eventMap))
            ->selectRaw("DATE_FORMAT(occurred_at, '%Y-%m') as event_month")
            ->selectRaw('event_name')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('event_month', 'event_name')
            ->orderBy('event_month')
            ->get();

        foreach ($rows as $row) {
            if (! $months->has($row->event_month)) {
                continue;
            }

            $series = $eventMap[$row->event_name] ?? null;

            if ($series === null) {
                continue;
            }

            $monthRow = $months->get($row->event_month);
            $monthRow[$series] = (int) $row->total;
            $months->put($row->event_month, $monthRow);
        }

        return array_values($months->all());
    }

    private function queueAging($query): array
    {
        $records = $query->get(['submitted_at']);

        return [
            'oneToThreeDays' => $records->filter(fn ($row) => $this->ageInDays($row->submitted_at) >= 1 && $this->ageInDays($row->submitted_at) <= 3)->count(),
            'fourToSevenDays' => $records->filter(fn ($row) => $this->ageInDays($row->submitted_at) >= 4 && $this->ageInDays($row->submitted_at) <= 7)->count(),
            'eightPlusDays' => $records->filter(fn ($row) => $this->ageInDays($row->submitted_at) >= 8)->count(),
        ];
    }

    private function averageTurnaround($query): array
    {
        $records = $query
            ->whereNotNull('submitted_at')
            ->whereNotNull('reviewed_at')
            ->get(['submitted_at', 'reviewed_at', 'status']);

        $approved = $records->where('status', 'Approved');
        $rejected = $records->where('status', 'Rejected');

        return [
            'approvedHours' => $this->averageHours($approved),
            'rejectedHours' => $this->averageHours($rejected),
        ];
    }

    private function averageHours($records): float
    {
        if ($records->isEmpty()) {
            return 0;
        }

        return round($records->avg(fn ($row) => max($row->reviewed_at?->diffInHours($row->submitted_at) ?? 0, 0)), 1);
    }

    private function ageInDays($submittedAt): int
    {
        if (! $submittedAt) {
            return 0;
        }

        return max(now()->diffInDays($submittedAt), 0);
    }

    private function renewalComplianceByYear(): array
    {
        $years = DB::table('membership_ledgers')
            ->select('year')
            ->whereNotNull('year')
            ->groupBy('year')
            ->orderBy('year')
            ->pluck('year')
            ->map(fn ($year) => (int) $year)
            ->filter()
            ->values();

        if ($years->isEmpty()) {
            return [];
        }

        return $years->take(-8)->map(function (int $year): array {
            $registeredCutoff = now()->setYear($year)->endOfYear();

            $eligibleFarmers = DB::table('farmers')
                ->where(function ($query) use ($registeredCutoff): void {
                    $query->whereNull('registered_at')
                        ->orWhere('registered_at', '<=', $registeredCutoff);
                })
                ->where(function ($query) use ($registeredCutoff): void {
                    $query->whereNull('inactive_at')
                        ->orWhere('inactive_at', '>', $registeredCutoff);
                })
                ->count();

            $compliantFarmers = DB::table('membership_ledgers')
                ->join('membership_transactions', 'membership_transactions.id', '=', 'membership_ledgers.membership_transaction_id')
                ->where('membership_ledgers.year', $year)
                ->where('membership_transactions.transaction_type', 'Renewal')
                ->where(function ($query): void {
                    $query->where('membership_ledgers.amount_paid', '>', 0)
                        ->orWhereIn('membership_ledgers.payment_status', ['Paid', 'Overpaid', 'Waived']);
                })
                ->distinct('membership_transactions.farmer_id')
                ->count('membership_transactions.farmer_id');

            $rate = $eligibleFarmers > 0
                ? round(($compliantFarmers / $eligibleFarmers) * 100, 1)
                : 0.0;

            return [
                'year' => $year,
                'eligible' => $eligibleFarmers,
                'compliant' => $compliantFarmers,
                'rate' => $rate,
            ];
        })->all();
    }

    private function analyticsExportRows(array $analytics): array
    {
        $rows = [];

        $push = static function (string $section, string $metric, mixed $value) use (&$rows): void {
            $rows[] = [
                'section' => $section,
                'metric' => $metric,
                'value' => is_array($value) ? json_encode($value) : $value,
            ];
        };

        foreach ($analytics['summary'] as $metric => $value) {
            $push('System Summary', str($metric)->replace('_', ' ')->title()->toString(), $value);
        }

        foreach ($analytics['activeInactiveByBarangay'] as $row) {
            $push('Farmer Status by Barangay', $row['barangay'] . ' Active', $row['active']);
            $push('Farmer Status by Barangay', $row['barangay'] . ' Inactive', $row['inactive']);
        }

        foreach ($analytics['renewalCompliance'] as $row) {
            $push('Renewal Compliance', (string) $row['year'] . ' Compliance Rate', $row['rate'] . '%');
        }

        foreach ($analytics['applicationDecisionTrend'] as $row) {
            $push('Application Decision Trend', (string) $row['year'] . ' Approved', $row['approved']);
            $push('Application Decision Trend', (string) $row['year'] . ' Rejected', $row['rejected']);
        }

        foreach ($analytics['inquiryTopics'] as $row) {
            $push('Inquiry Topics', $row['label'], $row['value']);
        }

        foreach ($analytics['paymentCollectionSummary']['byMethod'] as $row) {
            $push('Payment Collection by Method', $row['label'] . ' Count', $row['count']);
            $push('Payment Collection by Method', $row['label'] . ' Amount', $row['amount']);
        }

        foreach ($analytics['mobileMonitoring']['summary'] as $metric => $value) {
            $push('Mobile Service Monitoring', str($metric)->replace('_', ' ')->title()->toString(), $value);
        }

        foreach ($analytics['mobileMonitoring']['notificationEngagement'] as $metric => $value) {
            $push('Mobile Notification Engagement', str($metric)->replace('_', ' ')->title()->toString(), $value);
        }

        foreach ($analytics['mortuaryAssistanceSummary']['byBarangay'] as $row) {
            $push('Mortuary Assistance by Barangay', $row['label'] . ' Claims', $row['count']);
            $push('Mortuary Assistance by Barangay', $row['label'] . ' Amount', $row['amount']);
        }

        return $rows;
    }

    private function mobileMonitoring($from, $to): array
    {
        $totalMobileUsers = DB::table('users')
            ->whereNotNull('farmer_id')
            ->where('role', User::ROLE_FARMER)
            ->count();

        $activeUsersInRange = AnalyticsEvent::query()
            ->whereBetween('occurred_at', [$from, $to])
            ->whereNotNull('farmer_id')
            ->distinct('farmer_id')
            ->count('farmer_id');

        $failedLoginIssues = AnalyticsEvent::query()
            ->whereBetween('occurred_at', [$from, $to])
            ->where('event_name', 'farmer_login_failed')
            ->count();

        $failedOtpIssues = AnalyticsEvent::query()
            ->whereBetween('occurred_at', [$from, $to])
            ->where('event_name', 'farmer_otp_failed')
            ->count();

        $submittedInquiriesViaMobile = Query::query()
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('farmer_id')
            ->count();

        $mobileRenewalRequests = RenewalRequest::query()
            ->whereBetween('submitted_at', [$from, $to])
            ->where('source', 'mobile')
            ->count();

        $notificationBase = DB::table('notification_recipients as recipients')
            ->join('notifications', 'notifications.id', '=', 'recipients.notification_id')
            ->whereNotNull('recipients.farmer_id')
            ->whereBetween('notifications.created_at', [$from, $to]);

        $sentNotifications = (clone $notificationBase)->count();
        $readNotifications = (clone $notificationBase)->whereNotNull('recipients.read_at')->count();
        $deliveredNotifications = (clone $notificationBase)->where('recipients.status', 'delivered')->count();
        $failedNotifications = (clone $notificationBase)->where('recipients.status', 'failed')->count();

        return [
            'summary' => [
                'total_mobile_users' => $totalMobileUsers,
                'active_users_in_range' => $activeUsersInRange,
                'failed_login_issues' => $failedLoginIssues,
                'failed_otp_issues' => $failedOtpIssues,
                'submitted_inquiries_via_mobile' => $submittedInquiriesViaMobile,
                'mobile_renewal_requests' => $mobileRenewalRequests,
            ],
            'notificationEngagement' => [
                'sent' => $sentNotifications,
                'delivered' => $deliveredNotifications,
                'failed' => $failedNotifications,
                'read' => $readNotifications,
                'read_rate' => $sentNotifications > 0 ? round(($readNotifications / $sentNotifications) * 100, 1) : 0,
            ],
        ];
    }
}
