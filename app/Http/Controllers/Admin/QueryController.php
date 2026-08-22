<?php

namespace App\Http\Controllers\Admin;

use App\Enums\NotificationType;
use App\Exports\FarmerInquiryExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RespondToQueryRequest;
use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\Barangay;
use App\Models\Query;
use App\Models\QueryResponse;
use App\Services\Analytics\AnalyticsService;
use App\Services\Audit\AuditTrailService;
use App\Services\Notifications\NotificationDispatchService;
use App\Services\Queries\QueryWorkflowService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QueryController extends Controller
{
    public function __construct(
        private readonly QueryWorkflowService $queryWorkflowService,
        private readonly NotificationDispatchService $notificationDispatchService,
        private readonly AuditTrailService $auditTrailService,
        private readonly AnalyticsService $analyticsService,
    ) {
    }

    public function index(Request $request): InertiaResponse
    {
        $filters = [
            'status' => $request->query('status'),
            'year' => $request->integer('year') ?: null,
            'barangay_id' => $request->integer('barangay_id') ?: null,
        ];

        $baseQuery = Query::query()
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['year'] ?? null, fn (Builder $query, int $year) => $query->whereYear('created_at', $year))
            ->when($filters['barangay_id'] ?? null, function (Builder $query, int $barangayId): void {
                $query->whereHas('farmer', fn (Builder $farmerQuery) => $farmerQuery->where('barangay_id', $barangayId));
            });

        $queries = (clone $baseQuery)
            ->select(['id', 'farmer_id', 'subject', 'message', 'status', 'created_at'])
            ->with(['farmer.profile:id,farmer_id,first_name,middle_name,last_name,suffix', 'farmer:id,farmer_code', 'responses.responder:id,name'])
            ->withCount('responses')
            ->latest('created_at')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Admin/Queries/Index', [
            'filters' => $filters,
            'filterOptions' => [
                'statuses' => [
                    ['value' => '', 'label' => 'All statuses'],
                    ['value' => 'New', 'label' => 'New'],
                    ['value' => 'In Progress', 'label' => 'In Progress'],
                    ['value' => 'Resolved', 'label' => 'Resolved'],
                    ['value' => 'Escalated', 'label' => 'Escalated'],
                ],
                'years' => Query::query()
                    ->selectRaw('YEAR(created_at) as year')
                    ->distinct()
                    ->orderByDesc('year')
                    ->pluck('year')
                    ->filter()
                    ->map(fn ($year): array => ['value' => (int) $year, 'label' => (string) $year])
                    ->values()
                    ->all(),
                'barangays' => Barangay::query()
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn (Barangay $barangay): array => ['value' => $barangay->id, 'label' => $barangay->name])
                    ->values()
                    ->all(),
            ],
            'summary' => $this->summary($filters),
            'queries' => $queries->through(fn (Query $query): array => $this->serializeQueueRow($query)),
            'urls' => [
                'index' => route('admin.queries.index'),
                'export' => route('admin.queries.export'),
                'quickAction' => route('admin.tasks.quick-action'),
            ],
        ]);
    }

    public function export(Request $request): BinaryFileResponse|StreamedResponse
    {
        $filters = $this->filters($request);
        $format = strtolower((string) $request->query('format', 'pdf'));
        $selectedColumns = $this->selectedExportColumns($request);
        $queries = $this->exportQueriesQuery($filters)->get();

        if ($format === 'xlsx') {
            return Excel::download(
                new FarmerInquiryExport($queries, $selectedColumns),
                'farmer-inquiries-' . now()->format('Y-m-d') . '.xlsx'
            );
        }

        $pdf = Pdf::loadView('admin.queries.export-pdf', [
            'queries' => $queries,
            'generatedAt' => now()->format('F d, Y h:i A'),
            'filterLabels' => $this->exportFilterLabels($filters),
            'selectedColumns' => collect($selectedColumns)
                ->mapWithKeys(fn (string $column): array => [$column => FarmerInquiryExport::availableColumns()[$column]])
                ->all(),
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(
            static function () use ($pdf): void {
                echo $pdf->output();
            },
            'farmer-inquiries-' . now()->format('Y-m-d') . '.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    public function show(Query $query): InertiaResponse
    {
        $query->load([
            'farmer.profile:id,farmer_id,first_name,middle_name,last_name,suffix',
            'farmer.barangay:id,name',
            'farmer.association:id,name',
            'attachments',
            'responses.attachments',
            'responses.responder:id,name',
            'internalNotes.creator:id,name',
        ]);

        return Inertia::render('Admin/Queries/Show', [
            'queryRecord' => $this->serializeThread($query),
            'internalNotes' => $query->internalNotes
                ->map(fn ($note): array => [
                    'id' => $note->id,
                    'body' => $note->body,
                    'createdBy' => $note->creator?->name ?? 'Staff',
                    'createdAt' => optional($note->created_at)->format('M d, Y h:i A'),
                ])
                ->values()
                ->all(),
            'urls' => [
                'index' => route('admin.queries.index'),
                'respond' => route('admin.queries.respond', $query),
                'close' => route('admin.queries.close', $query),
                'reopen' => route('admin.queries.reopen', $query),
                'escalate' => route('admin.queries.escalate', $query),
                'storeInternalNote' => route('admin.queries.internal-notes.store', $query),
            ],
            'responseTemplates' => $this->responseTemplates(),
        ]);
    }

    public function respond(RespondToQueryRequest $request, Query $query): RedirectResponse
    {
        if ($query->status === 'Resolved') {
            throw ValidationException::withMessages([
                'response' => 'Reopen this inquiry before sending another response.',
            ]);
        }

        $attachmentCount = count(array_filter((array) $request->file('attachments', [])));

        DB::transaction(function () use ($request, $query): void {
            $payload = $this->queryWorkflowService->respond($query, [
                'message' => $request->validated('message') ?? '',
                'responded_by' => Auth::id(),
            ]);

            $query->fill([
                'status' => $payload['query']['status'],
            ])->save();

            $response = QueryResponse::query()->create([
                'query_id' => $query->id,
                'responded_by' => Auth::id(),
                'message' => $payload['response']['message'] ?? '',
                'responded_at' => $payload['response']['responded_at'],
            ]);

            $this->storeResponseAttachments($response, $request->file('attachments', []));
            $this->queueResponseNotification($query, $response);
        });

        $this->auditTrailService->record(
            'queries',
            'query_responded',
            'Responded to a farmer inquiry.',
            Auth::user(),
            $query,
            $this->auditTrailService->activityMetadata(
                AuditTrailService::ACTION_RESPONDED,
                'Inquiry response',
                [
                'subject' => $query->subject,
                'attachments_added' => $attachmentCount,
                ]
            )
        );

        $this->analyticsService->track('query_responded', [
            'module' => 'queries',
            'properties' => [
                'query_id' => $query->id,
                'attachments_added' => $attachmentCount,
                'status' => $query->status,
            ],
        ]);

        return redirect()
            ->route('admin.queries.show', $query)
            ->with('success', 'Response sent to farmer inquiry.');
    }

    public function close(Query $query): RedirectResponse
    {
        $payload = $this->queryWorkflowService->close($query);

        $query->fill([
            'status' => $payload['status'],
        ])->save();

        $this->auditTrailService->record(
            'queries',
            'query_closed',
            'Closed a farmer inquiry.',
            Auth::user(),
            $query,
            [
                'subject' => $query->subject,
            ]
        );

        return redirect()
            ->route('admin.queries.show', $query)
            ->with('success', 'Inquiry closed.');
    }

    public function reopen(Query $query): RedirectResponse
    {
        $payload = $this->queryWorkflowService->reopen($query);

        $query->fill([
            'status' => $payload['status'],
        ])->save();

        $this->auditTrailService->record(
            'queries',
            'query_reopened',
            'Reopened a farmer inquiry.',
            Auth::user(),
            $query,
            [
                'subject' => $query->subject,
            ]
        );

        return redirect()
            ->route('admin.queries.show', $query)
            ->with('success', 'Inquiry reopened.');
    }

    public function escalate(Query $query): RedirectResponse
    {
        $payload = $this->queryWorkflowService->escalate($query);

        $query->fill([
            'status' => $payload['status'],
        ])->save();

        $this->auditTrailService->record(
            'queries',
            'query_escalated',
            'Escalated a farmer inquiry.',
            Auth::user(),
            $query,
            [
                'subject' => $query->subject,
            ]
        );

        return redirect()
            ->route('admin.queries.show', $query)
            ->with('success', 'Inquiry escalated.');
    }

    public function viewImage(Query $query, Attachment $image): StreamedResponse
    {
        abort_unless((int) $image->module_id === (int) $query->id && $image->module_type === Query::class, 404);

        return $this->streamAttachment((string) $image->file_path, $image->original_name);
    }

    public function viewResponseAttachment(Query $query, QueryResponse $response, Attachment $attachment): StreamedResponse
    {
        abort_unless((int) $response->query_id === (int) $query->id, 404);
        abort_unless((int) $attachment->module_id === (int) $response->id && $attachment->module_type === QueryResponse::class, 404);

        return $this->streamAttachment((string) $attachment->file_path, $attachment->original_name);
    }

    private function serializeQueueRow(Query $query): array
    {
        $isAdmin = Auth::user()?->role === \App\Models\User::ROLE_ADMIN;
        $canMarkComplete = ! in_array($query->status, ['Resolved'], true);
        $latestResponder = $query->relationLoaded('responses')
            ? $query->responses->sortByDesc('responded_at')->first()?->responder?->name
            : $query->responses()->with('responder:id,name')->latest('responded_at')->first()?->responder?->name;

        return [
            'id' => $query->id,
            'recordKey' => (string) $query->getRouteKey(),
            'subject' => $query->subject,
            'messagePreview' => str($query->message)->limit(90)->toString(),
            'status' => $query->status,
            'responsesCount' => (int) $query->responses_count,
            'submittedAt' => optional($query->created_at)->format('M d, Y h:i A') ?? 'Not recorded',
            'farmer' => [
                'name' => $query->farmer?->full_name ?? 'Unknown Farmer',
                'code' => $query->farmer?->farmer_code ?? 'No code',
            ],
            'actions' => [
                'show' => route('admin.queries.show', $query),
            ],
            'accountability' => $this->accountability($query, $latestResponder),
            'quickActions' => [
                'canReview' => true,
                'canMarkComplete' => $canMarkComplete,
                'canRequestCorrection' => false,
                'canForwardToAdmin' => ! $isAdmin,
            ],
        ];
    }

    private function serializeThread(Query $query): array
    {
        $isAdmin = Auth::user()?->role === \App\Models\User::ROLE_ADMIN;
        $latestResponder = $query->responses->sortByDesc('responded_at')->first()?->responder?->name;

        return [
            'id' => $query->id,
            'subject' => $query->subject,
            'message' => $query->message,
            'status' => $query->status,
            'canEscalateToAdmin' => ! $isAdmin,
            'submittedAt' => optional($query->created_at)->format('M d, Y h:i A') ?? 'Not recorded',
            'accountability' => $this->accountability($query, $latestResponder),
            'farmer' => [
                'name' => $query->farmer?->full_name ?? 'Unknown Farmer',
                'code' => $query->farmer?->farmer_code ?? 'No code',
                'barangay' => $query->farmer?->barangay?->name ?? 'Not set',
                'association' => $query->farmer?->association?->name ?? 'Not set',
            ],
            'attachments' => $query->attachments
                ->map(fn (Attachment $attachment): array => [
                    'id' => $attachment->id,
                    'name' => $attachment->original_name ?: basename((string) $attachment->file_path),
                    'url' => route('admin.queries.images.show', [$query, $attachment]),
                ])
                ->values()
                ->all(),
            'responses' => $query->responses
                ->sortBy('id')
                ->map(fn (QueryResponse $response): array => [
                    'id' => $response->id,
                    'message' => $response->message,
                    'respondedAt' => optional($response->responded_at)->format('M d, Y h:i A') ?? 'Recorded reply',
                    'responder' => $response->responder?->name ?? 'Staff',
                    'attachments' => $response->attachments
                        ->map(fn (Attachment $attachment): array => [
                            'id' => $attachment->id,
                            'name' => $attachment->original_name ?: basename((string) $attachment->file_path),
                            'url' => route('admin.queries.responses.attachments.show', [$query, $response, $attachment]),
                        ])
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all(),
        ];
    }

    private function accountability(Query $query, ?string $assignedStaff = null): array
    {
        $latestActivity = AuditLog::query()
            ->with('actor:id,name')
            ->where('subject_type', $query->getMorphClass())
            ->where('subject_id', $query->getKey())
            ->latest('created_at')
            ->first(['actor_user_id', 'actor_name', 'created_at']);

        return [
            'lastUpdatedBy' => $latestActivity?->actor?->name ?? $latestActivity?->actor_name ?? $assignedStaff ?? 'System',
            'lastUpdatedAt' => optional($latestActivity?->created_at ?? $query->created_at)->format('M d, Y h:i A'),
            'assignedStaff' => $assignedStaff,
            'reviewedBy' => null,
        ];
    }

    private function responseTemplates(): array
    {
        return [
            [
                'key' => 'document_follow_up',
                'label' => 'Document Follow-up',
                'message' => "Thank you for reaching out. We are currently reviewing your concern and checking the required documents. We will update you once verification is complete.",
            ],
            [
                'key' => 'renewal_guidance',
                'label' => 'Renewal Guidance',
                'message' => "Your inquiry has been received. Please prepare your renewal requirements and keep your contact line open. Our office will confirm the next step shortly.",
            ],
            [
                'key' => 'barangay_confirmation',
                'label' => 'Barangay Confirmation',
                'message' => "We are coordinating with your barangay for confirmation. We will notify you as soon as we receive the required verification from the local office.",
            ],
            [
                'key' => 'payment_reference',
                'label' => 'Payment Clarification',
                'message' => "Please keep a copy of your payment reference or official receipt. If you already paid, send the reference number so we can validate it in the system.",
            ],
        ];
    }

    /**
     * @param  array<int, UploadedFile>|UploadedFile|null  $files
     */
    private function storeResponseAttachments(QueryResponse $response, array|UploadedFile|null $files): void
    {
        $uploads = $files instanceof UploadedFile ? [$files] : array_filter($files ?? []);

        foreach ($uploads as $file) {
            $path = $file->store('queries/' . $response->query_id . '/responses/' . $response->id, 'public');

            $response->attachments()->create([
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'file_path' => $path,
                'uploaded_at' => now(),
            ]);
        }
    }

    private function queueResponseNotification(Query $query, QueryResponse $response): void
    {
        $farmer = $query->farmer;

        if ($farmer === null) {
            return;
        }

        $this->notificationDispatchService->persist(
            NotificationType::QUERY_RESPONDED,
            [[
                'farmer_id' => $farmer->id,
                'recipient_address' => $farmer->profile?->mobile_number ?? null,
            ]],
            [
                'subject' => 'Inquiry Response Available',
                'message' => $query->subject,
                'query_id' => $query->id,
                'query_public_id' => $query->getRouteKey(),
                'responded_at' => optional($response->responded_at)?->toDateTimeString(),
            ],
            Auth::id(),
        );
    }

    private function streamAttachment(string $path, ?string $originalName): StreamedResponse
    {
        $filename = $originalName ?: basename($path);

        return Storage::disk('public')->response(
            $path,
            $filename,
            ['Content-Type' => 'application/octet-stream'],
            'inline',
        );
    }

    private function filters(Request $request): array
    {
        return [
            'status' => $request->filled('status') ? (string) $request->input('status') : null,
            'year' => $request->filled('year') ? (int) $request->input('year') : null,
            'barangay_id' => $request->filled('barangay_id') ? (int) $request->input('barangay_id') : null,
        ];
    }

    private function baseFilteredQuery(array $filters): Builder
    {
        return Query::query()
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['year'] ?? null, fn (Builder $query, int $year) => $query->whereYear('created_at', $year))
            ->when($filters['barangay_id'] ?? null, function (Builder $query, int $barangayId): void {
                $query->whereHas('farmer', fn (Builder $farmerQuery) => $farmerQuery->where('barangay_id', $barangayId));
            });
    }

    private function exportQueriesQuery(array $filters): Builder
    {
        return $this->baseFilteredQuery($filters)
            ->with([
                'farmer.profile:id,farmer_id,first_name,middle_name,last_name,suffix',
                'farmer.barangay:id,name',
                'farmer:id,farmer_code,barangay_id',
            ])
            ->withCount('responses')
            ->latest('created_at')
            ->latest('id');
    }

    private function selectedExportColumns(Request $request): array
    {
        $availableColumns = array_keys(FarmerInquiryExport::availableColumns());
        $requestedColumns = collect($request->input('columns', []))
            ->map(fn ($value): string => (string) $value)
            ->filter(fn (string $value): bool => in_array($value, $availableColumns, true))
            ->values()
            ->all();

        return $requestedColumns !== []
            ? $requestedColumns
            : array_keys(FarmerInquiryExport::availableColumns());
    }

    private function exportFilterLabels(array $filters): array
    {
        return [
            'status' => $filters['status'] ?: 'All statuses',
            'year' => $filters['year'] ?: 'All years',
            'barangay' => $filters['barangay_id']
                ? (Barangay::query()->whereKey($filters['barangay_id'])->value('name') ?? 'Selected barangay')
                : 'All barangays',
        ];
    }

    private function summary(array $filters): array
    {
        $filtered = $this->baseFilteredQuery($filters);

        return [
            'total' => (clone $filtered)->count(),
            'new' => (clone $filtered)->where('status', 'New')->count(),
            'inProgress' => (clone $filtered)->where('status', 'In Progress')->count(),
            'resolved' => (clone $filtered)->where('status', 'Resolved')->count(),
            'escalated' => (clone $filtered)->where('status', 'Escalated')->count(),
            'archived' => (clone $filtered)->whereNotNull('archived_at')->count(),
        ];
    }
}
