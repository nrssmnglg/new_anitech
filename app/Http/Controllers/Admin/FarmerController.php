<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FarmerStatus;
use App\Enums\MembershipStatus;
use App\Enums\NotificationType;
use App\Exports\FarmerRegistryExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFarmerRequest;
use App\Http\Requests\Admin\UpdateFarmerRequest;
use App\Models\Association;
use App\Models\AuditLog;
use App\Models\Barangay;
use App\Models\Farmer;
use App\Models\FarmerDocument;
use App\Models\MemberType;
use App\Models\MembershipApplication;
use App\Models\MembershipLedger;
use App\Models\MortuaryClaim;
use App\Models\Payment;
use App\Models\PaymentAssessment;
use App\Models\Query;
use App\Models\RenewalRequest;
use App\Models\InternalNote;
use App\Models\User;
use App\Services\Audit\AuditTrailService;
use App\Services\Backup\BackupRecoveryService;
use App\Services\Farmers\FarmerRegistryService;
use App\Services\Farmers\LegacyMembershipRecorderService;
use App\Services\Farmers\FarmerStatusWorkflowService;
use App\Services\Notifications\NotificationDispatchService;
use Barryvdh\DomPDF\Facade\Pdf;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use InvalidArgumentException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FarmerController extends Controller
{
    public function __construct(
        private readonly FarmerRegistryService $registry,
        private readonly LegacyMembershipRecorderService $legacyMembershipRecorder,
        private readonly FarmerStatusWorkflowService $statusWorkflowService,
        private readonly AuditTrailService $auditTrailService,
        private readonly BackupRecoveryService $backupRecoveryService,
        private readonly NotificationDispatchService $notificationDispatchService,
    ) {
    }

    public function index(Request $request): InertiaResponse
    {
        $filters = $this->filters($request);
        $qualityCounts = $this->qualityIssueCounts();
        $summaryQuery = $this->applyIndexFilters($this->listedFarmersQuery(), $filters, includeStatus: false);
        $farmers = $this->applyIndexFilters($this->listedFarmersQuery(), $filters)
            ->withCount([
                'membershipTransactions as current_year_membership_payment_count' => fn (Builder $query) => $query
                    ->whereHas('membershipLedgers', fn (Builder $ledgerQuery) => $ledgerQuery
                        ->where('year', now()->year)
                        ->where('amount_paid', '>', 0)),
            ])
            ->with([
                'profile:id,farmer_id,first_name,middle_name,last_name,suffix,birth_date,mobile_number,address',
                'barangay:id,name',
                'association:id,name,barangay_id',
                'memberType:id,code,name',
            ])
            ->orderByDesc('registered_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Farmers/Index', [
            'farmers' => $farmers->through(fn (Farmer $farmer): array => [
                'registeredAt' => $this->registryListRegisteredAt($farmer),
                'id' => $farmer->id,
                'farmerCode' => $farmer->farmer_code,
                'fullName' => $this->farmerFullName($farmer),
                'status' => [
                    'value' => $farmer->status->value,
                    'label' => $farmer->status->label(),
                ],
                'inactiveReason' => $farmer->inactive_reason,
                'barangay' => $farmer->barangay?->name,
                'association' => $farmer->association?->name,
                'memberType' => $farmer->memberType ? [
                    'code' => $farmer->memberType->code,
                    'name' => $farmer->memberType->name,
                ] : null,
                'contact' => [
                    'mobileNumber' => $farmer->profile?->mobile_number,
                    'address' => $farmer->profile?->address,
                ],
                'qualityIssues' => $this->registry->detectQualityIssues($farmer),
                'renewal' => [
                    ...$this->renewalEligibility($farmer),
                ],
                'actions' => [
                    'showUrl' => route('admin.farmers.show', $farmer),
                    'editUrl' => $request->user()?->role === User::ROLE_ADMIN ? route('admin.farmers.edit', $farmer) : null,
                    'renewalUrl' => $this->renewalEligibility($farmer)['isAvailable']
                        ? route('admin.renewals.create', ['farmer_id' => $farmer->id, 'year' => $this->preferredRenewalYear($farmer)])
                        : null,
                ],
                'accountability' => $this->accountability($farmer),
            ]),
            'filters' => $filters,
            'filterOptions' => [
                'statuses' => $this->registryStatusOptions(),
                'barangays' => Barangay::query()->orderBy('name')->get(['id', 'name']),
                'associations' => Association::query()->orderBy('name')->get(['id', 'barangay_id', 'name']),
                'memberTypes' => MemberType::query()->orderBy('code')->get(['id', 'code', 'name']),
            ],
            'summary' => [
                'total' => (clone $summaryQuery)->count(),
                'active' => $this->applyStatusFilter(clone $summaryQuery, FarmerStatus::ACTIVE)->count(),
                'inactive' => $this->applyStatusFilter(clone $summaryQuery, FarmerStatus::INACTIVE)->count(),
                'deceased' => $this->applyStatusFilter(clone $summaryQuery, FarmerStatus::DECEASED)->count(),
                'duplicates' => $qualityCounts['duplicates'],
                'incompleteProfiles' => $qualityCounts['incomplete_profiles'],
                'invalidMobileNumbers' => $qualityCounts['invalid_mobile_numbers'],
                'barangayAssociationMismatches' => $qualityCounts['barangay_association_mismatches'],
                'inactiveForReview' => $qualityCounts['inactive_for_review'],
            ],
            'createApplicationUrl' => route('admin.membership-applications.create'),
            'exportBaseUrl' => route('admin.farmers.export'),
            'resetUrl' => route('admin.farmers.index'),
            'bulkActionUrls' => [
                'notify' => route('admin.farmers.bulk-notify'),
                'assign' => route('admin.farmers.bulk-assign'),
                'followUp' => route('admin.farmers.bulk-follow-up'),
                'statusReview' => $request->user()?->hasRole(User::ROLE_ADMIN) ? route('admin.farmers.bulk-status-review') : null,
                'archive' => $request->user()?->hasRole(User::ROLE_ADMIN) ? route('admin.farmers.bulk-archive') : null,
            ],
            'bulkPermissions' => [
                'canNotify' => $request->user()?->hasRole([User::ROLE_ADMIN, User::ROLE_STAFF]) ?? false,
                'canAssign' => $request->user()?->hasRole([User::ROLE_ADMIN, User::ROLE_STAFF]) ?? false,
                'canFollowUp' => $request->user()?->hasRole([User::ROLE_ADMIN, User::ROLE_STAFF]) ?? false,
                'canStatusReview' => $request->user()?->hasRole(User::ROLE_ADMIN) ?? false,
                'canArchive' => $request->user()?->hasRole(User::ROLE_ADMIN) ?? false,
            ],
        ]);
    }

    public function export(Request $request): BinaryFileResponse|StreamedResponse
    {
        $filters = $this->filters($request);
        $farmers = $this->exportFarmersQuery($filters)->get();
        $filterLabels = $this->exportFilterLabels($filters);
        $format = strtolower((string) $request->query('format', 'pdf'));
        $selectedColumns = $this->selectedExportColumns($request);

        if ($format === 'xlsx') {
            $fileName = 'farmer-registry-' . now()->format('Y-m-d') . '.xlsx';

            return Excel::download(new FarmerRegistryExport($farmers, $selectedColumns), $fileName);
        }

        $fileName = 'farmer-registry-' . now()->format('Y-m-d') . '.pdf';
        $pdf = Pdf::loadView('admin.farmers.export-pdf', [
            'farmers' => $farmers,
            'generatedAt' => now()->format('F d, Y h:i A'),
            'filterLabels' => $filterLabels,
            'selectedColumns' => collect($selectedColumns)
                ->mapWithKeys(fn (string $column): array => [$column => FarmerRegistryExport::availableColumns()[$column]])
                ->all(),
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(
            static function () use ($pdf): void {
                echo $pdf->output();
            },
            $fileName,
            ['Content-Type' => 'application/pdf']
        );
    }

    public function report(Request $request): \Illuminate\View\View
    {
        $filters = $this->filters($request);
        $farmers = $this->exportFarmersQuery($filters)
            ->with([
                'barangay:id,name',
                'association:id,name,barangay_id',
            ])
            ->get();

        $view = view('admin.farmers.report', [
            'farmers' => $farmers,
            'generatedAt' => now(),
            'filters' => $filters,
            'filterLabels' => $this->exportFilterLabels($filters),
        ]);

        return $view;
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('Admin/Farmers/Create', [
            'statuses' => collect(FarmerStatus::cases())
                ->reject(fn (FarmerStatus $status): bool => $status === FarmerStatus::PENDING)
                ->map(fn (FarmerStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ])->values()->all(),
            'barangays' => Barangay::query()->orderBy('name')->get(['id', 'name']),
            'associations' => Association::query()->orderBy('name')->get(['id', 'barangay_id', 'name']),
            'memberTypes' => MemberType::query()->orderBy('code')->get(['id', 'code', 'name']),
            'nextFarmerCode' => $this->registry->previewFarmerCode(),
            'defaultRenewalYear' => now()->year,
            'storeUrl' => route('admin.farmers.store'),
            'indexUrl' => route('admin.farmers.index'),
            'duplicateMatches' => session('duplicate_matches', []),
        ]);
    }

    public function store(StoreFarmerRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $registeredAt = filled($validated['registered_at'] ?? null)
            ? \Carbon\Carbon::parse((string) $validated['registered_at'])
            : now();

        if (($validated['status'] ?? null) === FarmerStatus::PENDING->value) {
            $validated['status'] = $registeredAt->lt(now()->subYears(5)->startOfDay())
                ? FarmerStatus::INACTIVE->value
                : FarmerStatus::ACTIVE->value;
        }

        $duplicates = $this->registry->findPotentialDuplicates($validated);

        if ($duplicates->isNotEmpty() && ! $request->boolean('confirm_duplicate_override')) {
            return back()
                ->withInput()
                ->withErrors(['duplicate_check' => 'Potential duplicate farmer records were found. Review the matches below before saving, or confirm the override if these records are different people.'])
                ->with('duplicate_matches', $this->formatDuplicateMatches($duplicates));
        }

        $renewalYear = $request->boolean('create_renewal_record') && filled($validated['renewal_year'] ?? null)
            ? (int) $validated['renewal_year']
            : null;

        try {
            $farmer = DB::transaction(function () use ($validated, $renewalYear): Farmer {
                $farmer = $this->registry->create([
                    ...$validated,
                    'record_origin' => 'Old Record',
                ]);

                return $this->legacyMembershipRecorder->syncForOldRecord($farmer, $renewalYear);
            });
        } catch (DomainException $exception) {
            $errorField = str_contains(strtolower($exception->getMessage()), 'renewal')
                ? 'renewal_year'
                : 'member_type_id';

            throw ValidationException::withMessages([
                $errorField => $exception->getMessage(),
            ]);
        }

        $successMessage = $renewalYear !== null
            ? 'Old farmer record saved successfully and renewal for ' . $renewalYear . ' was recorded.'
            : 'Old farmer record saved successfully.';

        $this->auditTrailService->recordChange(
            'farmers',
            'farmer_created',
            'Encoded an old farmer registry record.',
            $request->user(),
            $farmer,
            [],
            [
                'first_name' => $farmer->profile?->first_name,
                'middle_name' => $farmer->profile?->middle_name,
                'last_name' => $farmer->profile?->last_name,
                'barangay_id' => $farmer->barangay_id,
                'association_id' => $farmer->association_id,
                'member_type_id' => $farmer->member_type_id,
                'status' => $validated['status'],
                'mobile_number' => $farmer->profile?->mobile_number,
                'record_origin' => $farmer->record_origin,
            ],
            $this->auditTrailService->activityMetadata(
                AuditTrailService::ACTION_PROCESSED,
                'Old record encoded',
                [
                    'farmer_id' => $farmer->id,
                    'farmer_code' => $farmer->farmer_code,
                ]
            )
        );

        return redirect()
            ->route('admin.farmers.show', $farmer)
            ->with('success', $successMessage);
    }

    public function show(Farmer $farmer): InertiaResponse
    {
        $this->ensureRegistryRecord($farmer);

        $farmer->load([
            'profile',
            'barangay:id,name',
            'association:id,name,barangay_id',
            'memberType:id,code,name',
            'internalNotes.creator:id,name',
        ]);

        $recentApplications = MembershipApplication::query()
            ->withCount('documents')
            ->where('farmer_id', $farmer->id)
            ->latest('submitted_at')
            ->limit(5)
            ->get();

        $recentAssessments = PaymentAssessment::query()
            ->with([
                'payments' => fn ($query) => $query->latest('paid_at'),
                'membershipApplication:id,application_no',
                'renewalRequest:id,year',
            ])
            ->whereHas('membershipTransaction', fn (Builder $query) => $query->where('farmer_id', $farmer->id))
            ->latest('id')
            ->limit(5)
            ->get();

        $recentLedgers = MembershipLedger::query()
            ->whereHas('membershipTransaction', fn (Builder $query) => $query->where('farmer_id', $farmer->id))
            ->latest('year')
            ->latest('id')
            ->limit(5)
            ->get();

        $latestApplication = $recentApplications->first();
        $latestAssessment = $recentAssessments->first();
        $latestLedger = $recentLedgers->first();
        $latestPayment = Payment::query()
            ->whereHas('paymentAssessment.membershipTransaction', fn (Builder $query) => $query->where('farmer_id', $farmer->id))
            ->latest('paid_at')
            ->latest('id')
            ->first();

        $totalPaid = Payment::query()
            ->whereHas('paymentAssessment.membershipTransaction', fn ($query) => $query->where('farmer_id', $farmer->id))
            ->sum('amount_paid');
        $currentYearLedger = MembershipLedger::query()
            ->whereHas('membershipTransaction', fn ($query) => $query->where('farmer_id', $farmer->id))
            ->where('year', now()->year)
            ->latest('id')
            ->first();

        return Inertia::render('Admin/Farmers/Show', [
            'farmer' => $this->serializeFarmer($farmer),
            'summary' => [
                'totalPaid' => (float) $totalPaid,
                'currentYearLedger' => $currentYearLedger ? [
                    'year' => $currentYearLedger->year,
                    'paymentStatus' => (string) $currentYearLedger->payment_status,
                    'amountPaid' => (float) $currentYearLedger->amount_paid,
                    'status' => (string) $currentYearLedger->status,
                ] : null,
            ],
            'latestApplication' => $latestApplication ? $this->serializeApplicationSummary($latestApplication) : null,
            'latestAssessment' => $latestAssessment ? $this->serializeAssessmentSummary($latestAssessment) : null,
            'latestPayment' => $latestPayment ? $this->serializePaymentSummary($latestPayment) : null,
            'latestLedger' => $latestLedger ? $this->serializeLedgerSummary($latestLedger) : null,
            'recentApplications' => $recentApplications->map(fn (MembershipApplication $application): array => $this->serializeApplicationSummary($application))->values()->all(),
            'recentAssessments' => $recentAssessments->map(fn (PaymentAssessment $assessment): array => $this->serializeAssessmentSummary($assessment))->values()->all(),
            'recentLedgers' => $recentLedgers->map(fn (MembershipLedger $ledger): array => $this->serializeLedgerSummary($ledger))->values()->all(),
            'timeline' => $this->timeline($farmer),
            'internalNotes' => $farmer->internalNotes
                ->map(fn ($note): array => [
                    'id' => $note->id,
                    'body' => $note->body,
                    'createdBy' => $note->creator?->name ?? 'Staff',
                    'createdAt' => optional($note->created_at)->format('M d, Y h:i A'),
                ])
                ->values()
                ->all(),
            'urls' => [
                'index' => route('admin.farmers.index'),
                'edit' => route('admin.farmers.edit', $farmer),
                'createApplication' => route('admin.membership-applications.create'),
                'renewalCreate' => route('admin.renewals.create', ['farmer_id' => $farmer->id, 'year' => $this->preferredRenewalYear($farmer)]),
                'reactivate' => route('admin.farmers.reactivate', $farmer),
                'recover' => route('admin.backups.farmers.recover', $farmer),
                'storeInternalNote' => route('admin.farmers.internal-notes.store', $farmer),
            ],
            'permissions' => [
                'canEdit' => Auth::user()?->hasRole(User::ROLE_ADMIN) ?? false,
                'canReactivate' => Auth::user()?->hasRole([User::ROLE_ADMIN, User::ROLE_STAFF]) ?? false,
            ],
            'renewal' => $this->renewalEligibility($farmer),
            'recoverySnapshots' => collect($this->backupRecoveryService->listSnapshots())
                ->filter(fn (array $snapshot): bool => $snapshot['type'] === 'restore-points')
                ->filter(function (array $snapshot) use ($farmer): bool {
                    $ids = data_get($snapshot, 'metadata.farmer_ids', []);

                    return is_array($ids) && in_array($farmer->id, $ids, true);
                })
                ->take(5)
                ->map(fn (array $snapshot): array => [
                    'key' => $snapshot['key'],
                    'label' => $snapshot['file_name'],
                    'createdAt' => filled($snapshot['created_at']) ? \Carbon\Carbon::parse($snapshot['created_at'])->format('M d, Y h:i A') : null,
                ])
                ->values()
                ->all(),
        ]);
    }

    public function edit(Farmer $farmer): InertiaResponse
    {
        $this->ensureRegistryRecord($farmer);
        $farmer->load(['profile', 'barangay:id,name', 'association:id,name,barangay_id', 'memberType:id,code,name']);

        return Inertia::render('Admin/Farmers/Edit', [
            'farmer' => $this->serializeFarmer($farmer),
            'statuses' => collect(FarmerStatus::cases())
                ->map(fn (FarmerStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ])->values()->all(),
            'barangays' => Barangay::query()->orderBy('name')->get(['id', 'name']),
            'associations' => Association::query()->orderBy('name')->get(['id', 'barangay_id', 'name']),
            'memberTypes' => MemberType::query()->orderBy('code')->get(['id', 'code', 'name']),
            'updateUrl' => route('admin.farmers.update', $farmer),
            'indexUrl' => route('admin.farmers.index'),
            'showUrl' => route('admin.farmers.show', $farmer),
        ]);
    }

    public function update(UpdateFarmerRequest $request, Farmer $farmer): RedirectResponse
    {
        $this->ensureRegistryRecord($farmer);
        $farmer->loadMissing('profile');
        $profile = $farmer->profile;
        $originalStatus = $farmer->status;
        $before = [
            'first_name' => $profile?->first_name,
            'middle_name' => $profile?->middle_name,
            'last_name' => $profile?->last_name,
            'barangay_id' => $farmer->barangay_id,
            'association_id' => $farmer->association_id,
            'member_type_id' => $farmer->member_type_id,
            'status' => $farmer->status?->value ?? (string) $farmer->status,
            'mobile_number' => $profile?->mobile_number,
        ];
        $validated = $request->validated();
        $duplicates = $this->registry->findPotentialDuplicates($validated, $farmer);

        if ($duplicates->isNotEmpty() && ! $request->boolean('confirm_duplicate_override')) {
            return back()
                ->withInput()
                ->withErrors(['duplicate_check' => 'Potential duplicate farmer records were found. Review the matches below before saving, or confirm the override if these records are different people.'])
                ->with('duplicate_matches', $this->formatDuplicateMatches($duplicates));
        }

        try {
            $farmer = $this->registry->update($farmer, $validated);

            if ($farmer->status !== $originalStatus) {
                $farmer = $this->statusWorkflowService->syncManualUpdate($farmer, $request->user()?->id);
            }
        } catch (DomainException $exception) {
            throw ValidationException::withMessages([
                'status' => $exception->getMessage(),
            ]);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'member_type_id' => $exception->getMessage(),
            ]);
        }

        $this->auditTrailService->recordChange(
            'farmers',
            'farmer_updated',
            'Updated a farmer registry record.',
            $request->user(),
            $farmer,
            $before,
            [
                'first_name' => $farmer->profile?->first_name,
                'middle_name' => $farmer->profile?->middle_name,
                'last_name' => $farmer->profile?->last_name,
                'barangay_id' => $farmer->barangay_id,
                'association_id' => $farmer->association_id,
                'member_type_id' => $farmer->member_type_id,
                'status' => $farmer->status?->value ?? (string) $farmer->status,
                'mobile_number' => $farmer->profile?->mobile_number,
            ],
            $this->auditTrailService->activityMetadata(
                AuditTrailService::ACTION_EDITED,
                'Farmer record update',
                [
                    'farmer_code' => $farmer->farmer_code,
                    'farmer_name' => $farmer->full_name,
                ]
            )
        );

        return redirect()
            ->route('admin.farmers.show', $farmer)
            ->with('success', 'Farmer registry record updated.');
    }

    public function reactivate(Request $request, Farmer $farmer): RedirectResponse
    {
        $this->ensureRegistryRecord($farmer);
        $farmer->refresh();

        if ($farmer->status !== FarmerStatus::INACTIVE) {
            return back()->with('error', 'Only inactive farmer records can be reactivated.');
        }

        $farmer->forceFill([
            'membership_status' => \App\Enums\MembershipStatus::ACTIVE->value,
            'activated_at' => now(),
            'inactive_at' => null,
            'inactive_reason' => null,
        ])->save();

        $farmer = $this->statusWorkflowService->syncManualUpdate($farmer, $request->user()?->id);

        $this->auditTrailService->record(
            'farmers',
            'farmer_reactivated',
            'Reactivated an inactive farmer record.',
            $request->user(),
            $farmer,
            $this->auditTrailService->activityMetadata(
                AuditTrailService::ACTION_PROCESSED,
                'Farmer reactivation',
                [
                    'farmer_id' => $farmer->id,
                    'farmer_code' => $farmer->farmer_code,
                ]
            )
        );

        return back()->with('success', 'Farmer record reactivated successfully.');
    }

    public function destroy(Farmer $farmer): RedirectResponse
    {
        $this->ensureRegistryRecord($farmer);

        try {
            $this->registry->delete($farmer);
        } catch (DomainException $exception) {
            throw ValidationException::withMessages([
                'farmer' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('admin.farmers.index')
            ->with('success', 'Farmer registry record deleted.');
    }

    public function duplicateCheck(Request $request): JsonResponse
    {
        $ignoreFarmer = $request->filled('ignore_farmer_id')
            ? Farmer::query()->find($request->integer('ignore_farmer_id'))
            : null;

        $duplicates = $this->registry->findPotentialDuplicates($request->only([
            'first_name',
            'last_name',
            'birth_date',
            'mobile_number',
            'barangay_id',
        ]), $ignoreFarmer);

        return response()->json([
            'matches' => $this->formatDuplicateMatches($duplicates),
        ]);
    }

    public function bulkNotify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'scope' => ['required', 'in:selected,filtered'],
            'selected_ids' => ['array'],
            'selected_ids.*' => ['integer', 'exists:farmers,id'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'search' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'barangay_id' => ['nullable', 'string'],
            'member_type_id' => ['nullable', 'string'],
            'quality' => ['nullable', 'string'],
        ]);

        $farmers = $this->resolveBulkFarmers($validated);

        $recipients = $farmers
            ->filter(fn (Farmer $farmer): bool => filled($farmer->profile?->mobile_number))
            ->map(fn (Farmer $farmer): array => [
                'farmer_id' => $farmer->id,
                'recipient_address' => $farmer->profile?->mobile_number,
                'mobile_number' => $farmer->profile?->mobile_number,
            ])
            ->values()
            ->all();

        if ($recipients === []) {
            return back()->with('error', 'No selected farmer has a mobile number available for notification.');
        }

        $this->notificationDispatchService->persist(
            NotificationType::ADVISORY_PUBLISHED,
            $recipients,
            [
                'subject' => $validated['subject'],
                'message' => $validated['message'],
                'source' => 'bulk_farmer_notify',
                'farmer_ids' => $farmers->modelKeys(),
            ],
            $request->user()?->id,
        );

        $this->auditTrailService->record(
            'farmers',
            'bulk_notify_sent',
            'Sent a bulk notification to farmer records.',
            $request->user(),
            null,
            $this->auditTrailService->activityMetadata(
                AuditTrailService::ACTION_PROCESSED,
                'Bulk farmer notification',
                [
                    'record_count' => $farmers->count(),
                    'recipient_count' => count($recipients),
                    'scope' => $validated['scope'],
                    'subject' => $validated['subject'],
                ]
            )
        );

        return back()->with('success', count($recipients) . ' farmer notification(s) queued.');
    }

    public function bulkStatusReview(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'scope' => ['required', 'in:selected,filtered'],
            'selected_ids' => ['array'],
            'selected_ids.*' => ['integer', 'exists:farmers,id'],
            'review_status' => ['required', \Illuminate\Validation\Rule::in(FarmerStatus::values())],
            'inactive_reason' => ['nullable', 'string'],
            'search' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'barangay_id' => ['nullable', 'string'],
            'member_type_id' => ['nullable', 'string'],
            'quality' => ['nullable', 'string'],
        ]);

        $farmers = $this->resolveBulkFarmers($validated);
        $status = FarmerStatus::from((string) $validated['review_status']);
        $reason = trim((string) ($validated['inactive_reason'] ?? ''));

        foreach ($farmers as $farmer) {
            $farmer->forceFill(match ($status) {
                FarmerStatus::ACTIVE => [
                    'membership_status' => \App\Enums\MembershipStatus::ACTIVE->value,
                    'activated_at' => $farmer->activated_at ?? now(),
                    'inactive_at' => null,
                    'inactive_reason' => null,
                ],
                FarmerStatus::PENDING => [
                    'membership_status' => \App\Enums\MembershipStatus::PENDING_APPLICATION->value,
                    'inactive_at' => null,
                    'inactive_reason' => null,
                ],
                FarmerStatus::INACTIVE, FarmerStatus::DECEASED => [
                    'membership_status' => \App\Enums\MembershipStatus::PENDING_APPLICATION->value,
                    'inactive_at' => $farmer->inactive_at ?? now(),
                    'inactive_reason' => $reason !== ''
                        ? $reason
                        : ($status === FarmerStatus::DECEASED ? 'Marked deceased during bulk review.' : 'Marked inactive during bulk review.'),
                ],
            })->save();
        }

        $this->auditTrailService->record(
            'farmers',
            'bulk_status_reviewed',
            'Updated farmer statuses in bulk review.',
            $request->user(),
            null,
            $this->auditTrailService->activityMetadata(
                AuditTrailService::ACTION_PROCESSED,
                'Bulk status review',
                [
                    'record_count' => $farmers->count(),
                    'scope' => $validated['scope'],
                    'status' => $status->label(),
                    'inactive_reason' => $reason,
                ]
            )
        );

        return back()->with('success', $farmers->count() . ' farmer record(s) updated for status review.');
    }

    public function bulkAssign(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'scope' => ['required', 'in:selected,filtered'],
            'selected_ids' => ['array'],
            'selected_ids.*' => ['integer', 'exists:farmers,id'],
            'barangay_id' => ['nullable', 'exists:barangays,id'],
            'association_id' => ['nullable', 'exists:associations,id'],
            'member_type_id' => ['nullable', 'exists:member_types,id'],
            'search' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'barangay_id_filter' => ['nullable', 'string'],
            'member_type_id_filter' => ['nullable', 'string'],
            'quality' => ['nullable', 'string'],
        ]);

        $filters = [
            'scope' => $validated['scope'],
            'selected_ids' => $validated['selected_ids'] ?? [],
            'search' => $request->input('search'),
            'status' => $request->input('status'),
            'barangay_id' => $request->input('barangay_id_filter'),
            'member_type_id' => $request->input('member_type_id_filter'),
            'quality' => $request->input('quality'),
        ];
        $farmers = $this->resolveBulkFarmers($filters);

        $updates = collect([
            'barangay_id' => $validated['barangay_id'] ?? null,
            'association_id' => $validated['association_id'] ?? null,
            'member_type_id' => $validated['member_type_id'] ?? null,
        ])->filter(fn ($value) => filled($value));

        if ($updates->isEmpty()) {
            return back()->with('error', 'Choose at least one assignment field to update.');
        }

        if ($updates->has('association_id') && filled($updates['association_id']) && filled($updates['barangay_id'])) {
            $matches = Association::query()
                ->whereKey($updates['association_id'])
                ->where('barangay_id', $updates['barangay_id'])
                ->exists();

            if (! $matches) {
                return back()->with('error', 'The selected association does not belong to the selected barangay.');
            }
        }

        foreach ($farmers as $farmer) {
            $farmer->forceFill($updates->all())->save();
        }

        $this->auditTrailService->record(
            'farmers',
            'bulk_assignment_updated',
            'Updated bulk farmer assignments.',
            $request->user(),
            null,
            $this->auditTrailService->activityMetadata(
                AuditTrailService::ACTION_PROCESSED,
                'Bulk record assignment',
                array_merge([
                    'record_count' => $farmers->count(),
                    'scope' => $validated['scope'],
                ], $updates->all())
            )
        );

        return back()->with('success', $farmers->count() . ' farmer record(s) reassigned.');
    }

    public function bulkFollowUp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'scope' => ['required', 'in:selected,filtered'],
            'selected_ids' => ['array'],
            'selected_ids.*' => ['integer', 'exists:farmers,id'],
            'note' => ['required', 'string', 'max:1000'],
            'search' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'barangay_id' => ['nullable', 'string'],
            'member_type_id' => ['nullable', 'string'],
            'quality' => ['nullable', 'string'],
        ]);

        $farmers = $this->resolveBulkFarmers($validated);

        if ($farmers->isEmpty()) {
            return back()->with('error', 'No farmer records matched the follow-up selection.');
        }

        $body = 'Marked for follow-up: ' . trim((string) $validated['note']);

        foreach ($farmers as $farmer) {
            $farmer->internalNotes()->create([
                'body' => $body,
                'created_by' => $request->user()?->id,
            ]);
        }

        $this->auditTrailService->record(
            'farmers',
            'bulk_follow_up_marked',
            'Marked farmer records for follow-up in bulk.',
            $request->user(),
            null,
            $this->auditTrailService->activityMetadata(
                AuditTrailService::ACTION_EDITED,
                'Bulk follow-up',
                [
                    'record_count' => $farmers->count(),
                    'scope' => $validated['scope'],
                    'note_excerpt' => str($validated['note'])->limit(120)->value(),
                ]
            )
        );

        return back()->with('success', $farmers->count() . ' farmer record(s) marked for follow-up.');
    }

    public function bulkArchive(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'scope' => ['required', 'in:selected,filtered'],
            'selected_ids' => ['array'],
            'selected_ids.*' => ['integer', 'exists:farmers,id'],
            'archive_reason' => ['nullable', 'string'],
            'registered_before' => ['nullable', 'date'],
            'search' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'barangay_id' => ['nullable', 'string'],
            'member_type_id' => ['nullable', 'string'],
            'quality' => ['nullable', 'string'],
        ]);

        $farmers = $this->resolveBulkFarmers($validated)
            ->when(
                filled($validated['registered_before'] ?? null),
                fn ($collection) => $collection->filter(
                    fn (Farmer $farmer): bool => optional($farmer->registered_at)?->toDateString() <= $validated['registered_before']
                )
            )
            ->values();

        if ($farmers->isEmpty()) {
            return back()->with('error', 'No farmer records matched the archive criteria.');
        }

        $reason = trim((string) ($validated['archive_reason'] ?? 'Archived old registry record.'));
        $restorePoint = $this->backupRecoveryService->createFarmerArchiveSnapshot($farmers, $reason, $request->user()?->id);

        foreach ($farmers as $farmer) {
            $farmer->forceFill([
                'membership_status' => \App\Enums\MembershipStatus::PENDING_APPLICATION->value,
                'inactive_at' => $farmer->inactive_at ?? now(),
                'inactive_reason' => $reason,
            ])->save();
        }

        $this->auditTrailService->record(
            'farmers',
            'bulk_records_archived',
            'Archived old farmer records in bulk.',
            $request->user(),
            null,
            $this->auditTrailService->activityMetadata(
                AuditTrailService::ACTION_PROCESSED,
                'Bulk archive',
                [
                    'record_count' => $farmers->count(),
                    'scope' => $validated['scope'],
                    'registered_before' => $validated['registered_before'] ?? null,
                    'archive_reason' => $reason,
                    'restore_point_key' => $restorePoint['key'],
                ]
            )
        );

        return back()->with('success', $farmers->count() . ' farmer record(s) archived.');
    }

    private function formatDuplicateMatches(iterable $duplicates): array
    {
        return collect($duplicates)->map(function (Farmer $farmer): array {
            $farmer->loadMissing(['profile', 'barangay:id,name', 'memberType:id,code,name']);
            $profile = $farmer->profile;

            return [
                'id' => $farmer->id,
                'farmer_code' => $farmer->farmer_code,
                'full_name' => $farmer->full_name,
                'birth_date' => $profile?->birth_date?->format('Y-m-d'),
                'mobile_number' => $profile?->mobile_number,
                'barangay_name' => $farmer->barangay?->name,
                'member_type' => $farmer->memberType?->code,
                'status' => $farmer->status?->label() ?? (string) $farmer->status,
                'reasons' => $farmer->getAttribute('duplicate_reasons') ?? [],
                'show_url' => $farmer->is_registry_record
                    ? route('admin.farmers.show', $farmer)
                    : null,
                'is_registry_record' => (bool) $farmer->is_registry_record,
                'record_origin' => $farmer->record_origin,
            ];
        })->values()->all();
    }

    private function listedFarmersQuery(): Builder
    {
        $query = Farmer::query()
            ->whereIn(DB::raw($this->farmerStatusCaseExpression()), $this->visibleRegistryStatuses());

        if (Schema::hasColumn('farmers', 'is_registry_record')) {
            return $query->where('is_registry_record', true);
        }

        return $query;
    }

    private function filters(Request $request): array
    {
        return [
            'search' => trim((string) $request->string('search')),
            'status' => $request->filled('status') ? (string) $request->input('status') : null,
            'barangay_id' => $request->filled('barangay_id') ? (string) $request->input('barangay_id') : null,
            'member_type_id' => $request->filled('member_type_id') ? (string) $request->input('member_type_id') : null,
            'quality' => $request->filled('quality') ? (string) $request->input('quality') : null,
        ];
    }

    private function applyIndexFilters(Builder $query, array $filters, bool $includeStatus = true): Builder
    {
        return $query
            ->when($filters['search'] ?? null, function (Builder $builder, string $search): void {
                $builder->where(function (Builder $nested) use ($search): void {
                    $nested
                        ->where('farmer_code', 'like', "%{$search}%")
                        ->orWhereHas('profile', function (Builder $profileQuery) use ($search): void {
                            $profileQuery
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('middle_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('mobile_number', 'like', "%{$search}%")
                                ->orWhere('address', 'like', "%{$search}%");
                        });
                });
            })
            ->when($includeStatus && ($filters['status'] ?? null), function (Builder $builder, string $status): void {
                $this->applyStatusFilter($builder, $status);
            })
            ->when($filters['barangay_id'] ?? null, fn (Builder $builder, string $barangayId) => $builder->where('barangay_id', $barangayId))
            ->when($filters['member_type_id'] ?? null, fn (Builder $builder, string $memberTypeId) => $builder->where('member_type_id', $memberTypeId))
            ->when($filters['quality'] ?? null, function (Builder $builder, string $quality): void {
                match ($quality) {
                    'duplicate' => $this->applyDuplicateFilter($builder),
                    'incomplete_profile' => $this->applyIncompleteProfileFilter($builder),
                    'invalid_mobile' => $this->applyInvalidMobileFilter($builder),
                    'barangay_association_mismatch' => $this->applyBarangayAssociationMismatchFilter($builder),
                    'inactive_review' => $this->applyInactiveReviewFilter($builder),
                    default => null,
                };
            });
    }

    private function applyDuplicateFilter(Builder $query): void
    {
        $query->whereHas('profile', function (Builder $profileQuery): void {
            $profileQuery
                ->whereNotNull('first_name')
                ->whereNotNull('last_name')
                ->where(function (Builder $duplicates): void {
                    $duplicates->whereIn('mobile_number', function ($subquery): void {
                        $subquery->from('farmer_profiles')
                            ->select('mobile_number')
                            ->whereNotNull('mobile_number')
                            ->groupBy('mobile_number')
                            ->havingRaw('COUNT(*) > 1');
                    })->orWhere(function (Builder $nameBirthDuplicates): void {
                        $nameBirthDuplicates
                            ->whereNotNull('birth_date')
                            ->whereExists(function ($subquery): void {
                                $subquery
                                    ->selectRaw('1')
                                    ->from('farmer_profiles as duplicate_profiles')
                                    ->whereColumn('duplicate_profiles.farmer_id', '!=', 'farmer_profiles.farmer_id')
                                    ->whereRaw('LOWER(duplicate_profiles.first_name) = LOWER(farmer_profiles.first_name)')
                                    ->whereRaw('LOWER(duplicate_profiles.last_name) = LOWER(farmer_profiles.last_name)')
                                    ->whereColumn('duplicate_profiles.birth_date', 'farmer_profiles.birth_date');
                            });
                    });
                });
        });
    }

    private function applyIncompleteProfileFilter(Builder $query): void
    {
        $query->where(function (Builder $builder): void {
            $builder
                ->whereNull('member_type_id')
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
        });
    }

    private function applyInvalidMobileFilter(Builder $query): void
    {
        $query->whereHas('profile', function (Builder $profileQuery): void {
            $profileQuery
                ->whereNotNull('mobile_number')
                ->where('mobile_number', '!=', '')
                ->whereRaw("REPLACE(mobile_number, ' ', '') NOT REGEXP '^(09|[+]639)[0-9]{9}$'");
        });
    }

    private function applyBarangayAssociationMismatchFilter(Builder $query): void
    {
        $query
            ->whereNotNull('association_id')
            ->whereNotNull('barangay_id')
            ->whereDoesntHave('association', function (Builder $associationQuery): void {
                $associationQuery->whereColumn('associations.barangay_id', 'farmers.barangay_id');
            });
    }

    private function applyInactiveReviewFilter(Builder $query): void
    {
        $query
            ->whereNotNull('inactive_at')
            ->where(function (Builder $builder): void {
                $builder
                    ->whereNull('inactive_reason')
                    ->orWhere('inactive_reason', '');
            });
    }

    private function qualityIssueCounts(): array
    {
        return [
            'duplicates' => $this->countForQualityIssue(fn (Builder $query) => $this->applyDuplicateFilter($query)),
            'incomplete_profiles' => $this->countForQualityIssue(fn (Builder $query) => $this->applyIncompleteProfileFilter($query)),
            'invalid_mobile_numbers' => $this->countForQualityIssue(fn (Builder $query) => $this->applyInvalidMobileFilter($query)),
            'barangay_association_mismatches' => $this->countForQualityIssue(fn (Builder $query) => $this->applyBarangayAssociationMismatchFilter($query)),
            'inactive_for_review' => $this->countForQualityIssue(fn (Builder $query) => $this->applyInactiveReviewFilter($query)),
        ];
    }

    private function countForQualityIssue(\Closure $callback): int
    {
        $query = $this->listedFarmersQuery();
        $callback($query);

        return (clone $query)->count();
    }

    private function exportFarmersQuery(array $filters): Builder
    {
        return $this->applyIndexFilters($this->listedFarmersQuery(), $filters)
            ->with([
                'profile:id,farmer_id,first_name,middle_name,last_name,suffix,birth_date,mobile_number,address',
                'barangay:id,name',
                'association:id,name,barangay_id',
                'memberType:id,code,name',
            ])
            ->orderByDesc('registered_at')
            ->orderByDesc('id');
    }

    private function exportFilterLabels(array $filters): array
    {
        return [
            'status' => filled($filters['status'])
                ? collect($this->registryStatusOptions())->firstWhere('value', $filters['status'])['label'] ?? $filters['status']
                : 'All statuses',
            'barangay' => filled($filters['barangay_id'])
                ? Barangay::query()->whereKey($filters['barangay_id'])->value('name') ?? 'Selected barangay'
                : 'All barangays',
            'member_type' => filled($filters['member_type_id'])
                ? MemberType::query()->whereKey($filters['member_type_id'])->value('code') ?? 'Selected member type'
                : 'All member types',
            'quality' => match ($filters['quality'] ?? null) {
                'duplicate' => 'Possible duplicates',
                'incomplete_profile' => 'Incomplete profiles',
                'invalid_mobile' => 'Invalid mobile numbers',
                'barangay_association_mismatch' => 'Barangay and association mismatches',
                'inactive_review' => 'Inactive records needing review',
                default => 'All records',
            },
            'search' => filled($filters['search'])
                ? $filters['search']
                : 'All farmers',
        ];
    }

    private function selectedExportColumns(Request $request): array
    {
        $availableColumns = array_keys(FarmerRegistryExport::availableColumns());
        $requestedColumns = collect($request->input('columns', []))
            ->map(fn ($value): string => (string) $value)
            ->filter(fn (string $value): bool => in_array($value, $availableColumns, true))
            ->values()
            ->all();

        return $requestedColumns !== []
            ? $requestedColumns
            : ['farmer_code', 'full_name', 'status', 'member_type', 'barangay', 'gender', 'mobile_number', 'registered_at'];
    }

    private function farmerFullName(Farmer $farmer): string
    {
        $profile = $farmer->profile;

        if (! $profile) {
            return $farmer->farmer_code;
        }

        $fullName = trim(collect([
            $profile->first_name,
            $profile->middle_name,
            $profile->last_name,
            $profile->suffix,
        ])->filter()->implode(' '));

        return $fullName !== '' ? $fullName : $farmer->farmer_code;
    }

    private function registryListRegisteredAt(Farmer $farmer): ?string
    {
        return optional($farmer->registered_at ?? $farmer->created_at)?->format('M d, Y h:i A');
    }

    private function serializeFarmer(Farmer $farmer): array
    {
        $profile = $farmer->profile;

        return [
            'id' => $farmer->id,
            'farmerCode' => $farmer->farmer_code,
            'fullName' => $farmer->full_name,
            'status' => [
                'value' => $farmer->status->value,
                'label' => $farmer->status->label(),
            ],
            'membershipStatusLabel' => $farmer->membership_status?->label(),
            'inactiveReason' => $farmer->inactive_reason,
            'registeredAt' => optional($farmer->registered_at)->format('F d, Y h:i A'),
            'activatedAt' => optional($farmer->activated_at)->format('F d, Y h:i A'),
            'recordOrigin' => $farmer->record_origin,
            'memberType' => $farmer->memberType ? [
                'id' => $farmer->memberType->id,
                'code' => $farmer->memberType->code,
                'name' => $farmer->memberType->name,
            ] : null,
            'barangay' => $farmer->barangay ? [
                'id' => $farmer->barangay->id,
                'name' => $farmer->barangay->name,
            ] : null,
            'association' => $farmer->association ? [
                'id' => $farmer->association->id,
                'name' => $farmer->association->name,
            ] : null,
            'profile' => [
                'firstName' => $profile?->first_name,
                'middleName' => $profile?->middle_name,
                'lastName' => $profile?->last_name,
                'suffix' => $profile?->suffix,
                'birthDate' => $profile?->birth_date?->toDateString(),
                'birthDateLabel' => optional($profile?->birth_date)->format('F d, Y'),
                'sex' => $profile?->sex ? strtolower((string) $profile->sex) : '',
                'sexLabel' => $profile?->sex ? ucfirst(strtolower((string) $profile->sex)) : null,
                'civilStatus' => $profile?->civil_status ? strtolower((string) $profile->civil_status) : '',
                'civilStatusLabel' => $profile?->civil_status ? ucfirst(strtolower((string) $profile->civil_status)) : null,
                'mobileNumber' => $profile?->mobile_number,
                'address' => $profile?->address,
            ],
            'accountability' => $this->accountability($farmer),
        ];
    }

    private function accountability(Farmer $farmer): array
    {
        $latestActivity = AuditLog::query()
            ->where('subject_type', $farmer->getMorphClass())
            ->where('subject_id', $farmer->getKey())
            ->latest('created_at')
            ->first(['actor_name', 'created_at']);

        return [
            'lastUpdatedBy' => $latestActivity?->actor_name ?? 'System',
            'lastUpdatedAt' => optional($latestActivity?->created_at ?? $farmer->updated_at)->format('M d, Y h:i A'),
            'assignedStaff' => null,
            'reviewedBy' => null,
        ];
    }

    private function renewalEligibility(Farmer $farmer, ?int $year = null): array
    {
        $targetYear = $year ?? $this->preferredRenewalYear($farmer);

        if ($targetYear === null) {
            return [
                'year' => null,
                'isAvailable' => false,
                'disabledReason' => 'No unrecorded renewal year is available.',
                'dialogTitle' => 'Renewal Already Recorded',
                'dialogMessage' => 'All available renewal years are already recorded for this farmer.',
            ];
        }

        $targetYearRecorded = $this->hasRenewalRecordForYear($farmer, $targetYear);

        if ($farmer->status === FarmerStatus::DECEASED) {
            return [
                'year' => $targetYear,
                'isAvailable' => false,
                'disabledReason' => 'This farmer is tagged as deceased and cannot be renewed.',
                'dialogTitle' => 'Farmer Not Eligible for Renewal',
                'dialogMessage' => 'This farmer is marked as deceased in the registry. Renewal cannot be processed.',
            ];
        }

        if ($farmer->status === FarmerStatus::INACTIVE) {
            return [
                'year' => $targetYear,
                'isAvailable' => false,
                'disabledReason' => 'This farmer is inactive and cannot be renewed from this screen.',
                'dialogTitle' => 'Farmer Not Eligible for Renewal',
                'dialogMessage' => 'This farmer is inactive in the registry. Reactivate or review the record first before processing renewal.',
            ];
        }

        if ($farmer->status === FarmerStatus::PENDING) {
            return [
                'year' => $targetYear,
                'isAvailable' => false,
                'disabledReason' => 'This farmer has no active registry status yet.',
                'dialogTitle' => 'Farmer Not Eligible for Renewal',
                'dialogMessage' => 'This farmer is still pending in the registry and does not have an active membership to renew yet.',
            ];
        }

        if ($targetYearRecorded) {
            return [
                'year' => $targetYear,
                'isAvailable' => false,
                'disabledReason' => 'Annual due already recorded for ' . $targetYear . '.',
                'dialogTitle' => 'Renewal Already Recorded',
                'dialogMessage' => 'A renewal or annual due for ' . $targetYear . ' is already recorded for this farmer.',
            ];
        }

        return [
            'year' => $targetYear,
            'isAvailable' => true,
            'disabledReason' => null,
            'dialogTitle' => null,
            'dialogMessage' => null,
        ];
    }

    private function preferredRenewalYear(Farmer $farmer): ?int
    {
        $currentYear = now()->year;

        if (! $this->hasRenewalRecordForYear($farmer, $currentYear)) {
            return $currentYear;
        }

        for ($year = $currentYear - 1; $year >= 2000; $year--) {
            if (! $this->hasRenewalRecordForYear($farmer, $year)) {
                return $year;
            }
        }

        return null;
    }

    private function hasRenewalRecordForYear(Farmer $farmer, int $year): bool
    {
        $hasPaidLedger = MembershipLedger::query()
            ->whereHas('membershipTransaction', fn (Builder $query) => $query->where('farmer_id', $farmer->id))
            ->where('year', $year)
            ->where('amount_paid', '>', 0)
            ->exists();

        if ($hasPaidLedger) {
            return true;
        }

        return RenewalRequest::query()
            ->where('farmer_id', $farmer->id)
            ->where('year', $year)
            ->exists();
    }

    private function serializeApplicationSummary(MembershipApplication $application): array
    {
        return [
            'id' => $application->id,
            'applicationNo' => $application->application_no,
            'sourceLabel' => strtoupper(str_replace('_', '-', (string) $application->source)),
            'statusLabel' => $application->status?->label() ?? 'Pending',
            'submittedAt' => optional($application->submitted_at)->format('M d, Y h:i A'),
            'documentsCount' => (int) ($application->documents_count ?? $application->documents()->count()),
            'showUrl' => route('admin.membership-applications.show', $application),
        ];
    }

    private function serializeAssessmentSummary(PaymentAssessment $assessment): array
    {
        $latestPayment = $assessment->relationLoaded('payments')
            ? $assessment->payments->first()
            : $assessment->payments()->latest('paid_at')->first();

        return [
            'id' => $assessment->id,
            'statusLabel' => $assessment->status?->label() ?? 'Pending',
            'totalAmountDue' => (float) $assessment->total_amount_due,
            'latestPayment' => $latestPayment ? [
                'amountPaid' => (float) $latestPayment->amount_paid,
                'paidAt' => optional($latestPayment->paid_at)->format('M d, Y h:i A'),
            ] : null,
        ];
    }

    private function serializePaymentSummary(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'amountPaid' => (float) $payment->amount_paid,
            'paidAt' => optional($payment->paid_at)->format('F d, Y h:i A'),
            'statusLabel' => $payment->status?->label() ?? 'Verified',
            'method' => strtoupper((string) ($payment->payment_method ?? '')),
            'referenceNo' => $payment->reference_no,
        ];
    }

    private function serializeLedgerSummary(MembershipLedger $ledger): array
    {
        return [
            'id' => $ledger->id,
            'year' => $ledger->year,
            'paymentStatus' => (string) $ledger->payment_status,
            'status' => (string) $ledger->status,
            'amountPaid' => (float) $ledger->amount_paid,
            'paidAt' => optional($ledger->paid_at)->format('M d, Y h:i A'),
        ];
    }

    private function timeline(Farmer $farmer): array
    {
        return collect()
            ->merge($this->registrationTimeline($farmer))
            ->merge($this->applicationTimeline($farmer))
            ->merge($this->renewalTimeline($farmer))
            ->merge($this->paymentTimeline($farmer))
            ->merge($this->inquiryTimeline($farmer))
            ->merge($this->notificationTimeline($farmer))
            ->merge($this->documentTimeline($farmer))
            ->merge($this->mortuaryTimeline($farmer))
            ->filter(fn (array $item): bool => filled($item['occurredAtRaw'] ?? null))
            ->sortByDesc('occurredAtRaw')
            ->take(40)
            ->map(function (array $item): array {
                unset($item['occurredAtRaw']);

                return $item;
            })
            ->values()
            ->all();
    }

    private function registrationTimeline(Farmer $farmer): array
    {
        $occurredAt = $farmer->registered_at ?? $farmer->created_at;

        if (! $occurredAt) {
            return [];
        }

        return [[
            'key' => 'registration-' . $farmer->id,
            'type' => 'registration',
            'title' => 'Farmer registered',
            'subtitle' => 'Registry profile created in the system.',
            'status' => $farmer->status->label(),
            'occurredAt' => $occurredAt->format('M d, Y h:i A'),
            'occurredAtRaw' => $occurredAt->toDateTimeString(),
            'href' => route('admin.farmers.show', $farmer),
            'meta' => [
                'Farmer Code' => $farmer->farmer_code,
                'Source' => $farmer->record_origin ?: 'Admin',
            ],
        ]];
    }

    private function applicationTimeline(Farmer $farmer): array
    {
        return MembershipApplication::query()
            ->withCount('documents')
            ->where('farmer_id', $farmer->id)
            ->latest('submitted_at')
            ->latest('id')
            ->get()
            ->map(fn (MembershipApplication $application): array => [
                'key' => 'application-' . $application->id,
                'type' => 'application',
                'title' => 'Membership application',
                'subtitle' => $application->application_no ?: 'Application #' . $application->id,
                'status' => $application->status?->label() ?? 'Pending',
                'occurredAt' => optional($application->submitted_at ?? $application->created_at)?->format('M d, Y h:i A'),
                'occurredAtRaw' => optional($application->submitted_at ?? $application->created_at)?->toDateTimeString(),
                'href' => route('admin.membership-applications.show', $application),
                'meta' => [
                    'Documents' => (string) ($application->documents_count ?? 0),
                    'Source' => strtoupper(str_replace('_', '-', (string) $application->source)),
                ],
            ])
            ->all();
    }

    private function renewalTimeline(Farmer $farmer): array
    {
        return RenewalRequest::query()
            ->where('farmer_id', $farmer->id)
            ->latest('submitted_at')
            ->latest('id')
            ->get()
            ->map(fn (RenewalRequest $renewal): array => [
                'key' => 'renewal-' . $renewal->id,
                'type' => 'renewal',
                'title' => 'Renewal request',
                'subtitle' => ($renewal->application_no ?: 'Renewal #' . $renewal->id) . ' for ' . $renewal->year,
                'status' => $renewal->status?->label() ?? 'Pending',
                'occurredAt' => optional($renewal->submitted_at ?? $renewal->created_at)?->format('M d, Y h:i A'),
                'occurredAtRaw' => optional($renewal->submitted_at ?? $renewal->created_at)?->toDateTimeString(),
                'href' => route('admin.renewals.show', $renewal),
                'meta' => [
                    'Year' => (string) $renewal->year,
                    'Source' => strtoupper(str_replace('_', '-', (string) $renewal->source)),
                ],
            ])
            ->all();
    }

    private function paymentTimeline(Farmer $farmer): array
    {
        return Payment::query()
            ->with(['paymentAssessment.membershipTransaction'])
            ->whereHas('paymentAssessment.membershipTransaction', fn (Builder $query) => $query->where('farmer_id', $farmer->id))
            ->latest('paid_at')
            ->latest('id')
            ->get()
            ->map(function (Payment $payment): array {
                $transaction = $payment->paymentAssessment?->membershipTransaction;
                $href = null;
                $transactionLabel = 'Payment record';

                if ($transaction?->transaction_type === 'Application') {
                    $href = route('admin.membership-applications.show', $transaction);
                    $transactionLabel = 'Application payment';
                } elseif ($transaction?->transaction_type === 'Renewal') {
                    $href = route('admin.renewals.show', $transaction);
                    $transactionLabel = 'Renewal payment';
                }

                return [
                    'key' => 'payment-' . $payment->id,
                    'type' => 'payment',
                    'title' => $transactionLabel,
                    'subtitle' => 'Reference ' . ($payment->reference_no ?: 'No reference number'),
                    'status' => $payment->status?->label() ?? 'Verified',
                    'occurredAt' => optional($payment->paid_at ?? $payment->created_at)?->format('M d, Y h:i A'),
                    'occurredAtRaw' => optional($payment->paid_at ?? $payment->created_at)?->toDateTimeString(),
                    'href' => $href,
                    'meta' => [
                        'Amount' => 'PHP ' . number_format((float) $payment->amount_paid, 2),
                        'Method' => strtoupper((string) ($payment->payment_method ?? 'N/A')),
                    ],
                ];
            })
            ->all();
    }

    private function inquiryTimeline(Farmer $farmer): array
    {
        return Query::query()
            ->withCount('responses')
            ->where('farmer_id', $farmer->id)
            ->latest('created_at')
            ->latest('id')
            ->get()
            ->map(fn (Query $query): array => [
                'key' => 'query-' . $query->id,
                'type' => 'inquiry',
                'title' => 'Farmer inquiry',
                'subtitle' => $query->subject,
                'status' => $query->status,
                'occurredAt' => optional($query->created_at)->format('M d, Y h:i A'),
                'occurredAtRaw' => optional($query->created_at)->toDateTimeString(),
                'href' => route('admin.queries.show', $query),
                'meta' => [
                    'Responses' => (string) ($query->responses_count ?? 0),
                    'Message' => str((string) $query->message)->limit(70)->toString(),
                ],
            ])
            ->all();
    }

    private function notificationTimeline(Farmer $farmer): array
    {
        return DB::table('notification_recipients as recipients')
            ->join('notifications', 'notifications.id', '=', 'recipients.notification_id')
            ->where('recipients.farmer_id', $farmer->id)
            ->orderByDesc('notifications.created_at')
            ->limit(20)
            ->get([
                'recipients.id as recipient_id',
                'recipients.read_at',
                'notifications.id as notification_id',
                'notifications.type',
                'notifications.subject',
                'notifications.message',
                'notifications.created_at',
            ])
            ->map(function (object $notification): array {
                $occurredAt = optional(\Carbon\Carbon::parse($notification->created_at));

                return [
                    'key' => 'notification-' . $notification->recipient_id,
                    'type' => 'notification',
                    'title' => 'Notification sent',
                    'subtitle' => $notification->subject ?: 'System notification',
                    'status' => $notification->read_at ? 'Read' : 'Unread',
                    'occurredAt' => $occurredAt?->format('M d, Y h:i A'),
                    'occurredAtRaw' => $occurredAt?->toDateTimeString(),
                    'href' => null,
                    'meta' => [
                        'Type' => str((string) $notification->type)->replace('_', ' ')->title()->toString(),
                        'Message' => str((string) $notification->message)->limit(70)->toString(),
                    ],
                ];
            })
            ->all();
    }

    private function documentTimeline(Farmer $farmer): array
    {
        return FarmerDocument::query()
            ->with([
                'documentType:id,code,name',
                'membershipTransaction:id,transaction_type,farmer_id',
            ])
            ->whereHas('membershipTransaction', fn (Builder $query) => $query->where('farmer_id', $farmer->id))
            ->latest('uploaded_at')
            ->latest('id')
            ->get()
            ->map(function (FarmerDocument $document): array {
                $transaction = $document->membershipTransaction;
                $href = null;

                if ($transaction?->transaction_type === 'Application') {
                    $href = route('admin.membership-applications.show', $transaction);
                } elseif ($transaction?->transaction_type === 'Renewal') {
                    $href = route('admin.renewals.show', $transaction);
                }

                return [
                    'key' => 'document-' . $document->id,
                    'type' => 'document',
                    'title' => 'Document submitted',
                    'subtitle' => $document->documentType?->name ?? $document->documentType?->code ?? 'Farmer document',
                    'status' => $document->verification_status?->value
                        ? str($document->verification_status->value)->replace('_', ' ')->title()->toString()
                        : 'Pending',
                    'occurredAt' => optional($document->uploaded_at ?? $document->created_at)?->format('M d, Y h:i A'),
                    'occurredAtRaw' => optional($document->uploaded_at ?? $document->created_at)?->toDateTimeString(),
                    'href' => $href,
                    'meta' => [
                        'File' => $document->original_name ?: 'No file name',
                        'Transaction' => $transaction?->transaction_type ?? 'Registry',
                    ],
                ];
            })
            ->all();
    }

    private function mortuaryTimeline(Farmer $farmer): array
    {
        return MortuaryClaim::query()
            ->whereHas('membershipLedger.membershipTransaction', fn (Builder $query) => $query->where('farmer_id', $farmer->id))
            ->latest('claim_date')
            ->latest('id')
            ->get()
            ->map(fn (MortuaryClaim $claim): array => [
                'key' => 'mortuary-' . $claim->id,
                'type' => 'mortuary',
                'title' => 'Mortuary claim',
                'subtitle' => $claim->claim_reference ?: 'Mortuary claim #' . $claim->id,
                'status' => str((string) $claim->status)->replace('_', ' ')->title()->toString(),
                'occurredAt' => optional($claim->claim_date ?? $claim->created_at)?->format('M d, Y h:i A'),
                'occurredAtRaw' => optional($claim->claim_date ?? $claim->created_at)?->toDateTimeString(),
                'href' => route('admin.mortuary-claims.show', $claim),
                'meta' => [
                    'Claim Amount' => 'PHP ' . number_format((float) $claim->claim_amount, 2),
                    'Claimer' => $claim->claimer_name ?: 'No claimer name',
                ],
            ])
            ->all();
    }

    private function applyStatusFilter(Builder $query, FarmerStatus|string $status): Builder
    {
        $normalized = $status instanceof FarmerStatus
            ? $status
            : (FarmerStatus::tryFrom(strtolower((string) $status)) ?? FarmerStatus::PENDING);

        return $query->whereRaw(
            '(' . $this->farmerStatusCaseExpression() . ') = ?',
            [$normalized->value],
        );
    }

    private function farmerStatusCaseExpression(): string
    {
        return sprintf(
            "CASE
                WHEN LOWER(TRIM(COALESCE(inactive_reason, ''))) LIKE '%%deceas%%' THEN '%s'
                WHEN inactive_at IS NOT NULL OR TRIM(COALESCE(inactive_reason, '')) <> '' THEN '%s'
                WHEN LOWER(TRIM(COALESCE(membership_status, ''))) = '%s' THEN '%s'
                ELSE '%s'
            END",
            FarmerStatus::DECEASED->value,
            FarmerStatus::INACTIVE->value,
            MembershipStatus::ACTIVE->value,
            FarmerStatus::ACTIVE->value,
            FarmerStatus::PENDING->value,
        );
    }

    private function registryStatusOptions(): array
    {
        return [
            ['value' => FarmerStatus::ACTIVE->value, 'label' => 'Active'],
            ['value' => FarmerStatus::INACTIVE->value, 'label' => 'Inactive'],
            ['value' => FarmerStatus::DECEASED->value, 'label' => 'Deceased'],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function visibleRegistryStatuses(): array
    {
        return [
            FarmerStatus::ACTIVE->value,
            FarmerStatus::INACTIVE->value,
            FarmerStatus::DECEASED->value,
        ];
    }

    private function formData(?Farmer $farmer = null): array
    {
        return [
            'farmer' => $farmer,
            'statuses' => FarmerStatus::options(),
            'barangays' => Barangay::query()->orderBy('name')->get(['id', 'name']),
            'associations' => Association::query()->orderBy('name')->get(['id', 'barangay_id', 'name']),
            'memberTypes' => MemberType::query()->orderBy('code')->get(['id', 'code', 'name']),
            'nextFarmerCode' => $farmer?->farmer_code ?? $this->registry->previewFarmerCode(),
        ];
    }

    private function ensureRegistryRecord(Farmer $farmer): void
    {
        abort_unless($farmer->exists, 404);
    }

    private function resolveBulkFarmers(array $payload): \Illuminate\Support\Collection
    {
        $scope = $payload['scope'] ?? 'selected';

        if ($scope === 'filtered') {
            $filters = [
                'search' => trim((string) ($payload['search'] ?? '')),
                'status' => filled($payload['status'] ?? null) ? (string) $payload['status'] : null,
                'barangay_id' => filled($payload['barangay_id'] ?? null) ? (string) $payload['barangay_id'] : null,
                'member_type_id' => filled($payload['member_type_id'] ?? null) ? (string) $payload['member_type_id'] : null,
                'quality' => filled($payload['quality'] ?? null) ? (string) $payload['quality'] : null,
            ];

            return $this->applyIndexFilters(
                Farmer::query()->with(['profile', 'barangay', 'association', 'memberType']),
                $filters
            )->get();
        }

        $ids = collect($payload['selected_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Farmer::query()
            ->with(['profile', 'barangay', 'association', 'memberType'])
            ->whereIn('id', $ids)
            ->get();
    }
}
