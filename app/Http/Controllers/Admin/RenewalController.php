<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AssessmentStatus;
use App\Enums\RenewalStatus;
use App\Exports\RenewalMasterlistExport;
use App\Exports\RenewalSummaryExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReviewRenewalRequest;
use App\Http\Requests\Admin\StorePaymentRequest;
use App\Http\Requests\Admin\StoreRenewalRequest;
use App\Http\Requests\Admin\VerifyFarmerDocumentRequest;
use App\Models\AuditLog;
use App\Models\Barangay;
use App\Models\Farmer;
use App\Models\FarmerDocument;
use App\Models\FeeSchedule;
use App\Models\MemberType;
use App\Models\MembershipLedger;
use App\Models\RenewalRequest;
use App\Models\User;
use App\Services\Analytics\AnalyticsService;
use App\Services\Documents\FarmerDocumentService;
use App\Services\Farmers\FarmerRegistryService;
use App\Services\Membership\FeeCalculatorService;
use App\Services\Membership\RenewalRequestService;
use App\Services\Membership\ApplicationAnnualCoverageService;
use App\Services\Notifications\RenewalReminderService;
use App\Services\Notifications\NotificationDispatchService;
use App\Services\Payments\PaymentAssessmentService;
use App\Services\Reports\Pdf\RenewalSummaryPdfService;
use App\Services\Reports\Pdf\StoredPdfExportService;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RenewalController extends Controller
{
    public function __construct(
        private readonly RenewalRequestService $renewalRequestService,
        private readonly FarmerDocumentService $farmerDocumentService,
        private readonly FarmerRegistryService $farmerRegistryService,
        private readonly FeeCalculatorService $feeCalculatorService,
        private readonly RenewalReminderService $renewalReminderService,
        private readonly NotificationDispatchService $notificationDispatchService,
        private readonly PaymentAssessmentService $paymentAssessmentService,
        private readonly RenewalSummaryPdfService $renewalSummaryPdfService,
        private readonly StoredPdfExportService $storedPdfExportService,
        private readonly AnalyticsService $analyticsService,
    ) {
    }

    public function index(Request $request): InertiaResponse
    {
        $activeSection = $request->query('section') === 'records' ? 'records' : 'queue';
        $recordFilters = $this->recordFilters($request);
        $queueFilters = $this->queueFilters($request);
        $queueYear = (int) $queueFilters['queue_year'];

        if ($queueYear === now()->year) {
            $this->renewalReminderService->syncInactiveLapsedFarmers($queueYear);
        }

        $renewalsQuery = $this->renewalQueueQuery($queueYear, $queueFilters);
        $renewals = null;
        $availableYears = collect();
        $availableBarangays = collect();
        $sourceOptions = [];
        $statusOptions = [];

        if ($activeSection === 'queue') {
            $renewals = (clone $renewalsQuery)
                ->with([
                    'profile:farmer_id,first_name,middle_name,last_name,suffix',
                    'memberType:id,code,name',
                    'users:id,farmer_id,email',
                ])
                ->orderByDesc('registered_at')
                ->orderByDesc('id')
                ->paginate(12, ['*'], 'queue_page')
                ->withQueryString();

            $reminderMap = $this->renewalReminderMap($renewals->getCollection(), $queueYear);
            $renewals->through(fn (Farmer $farmer): array => $this->serializeEligibleFarmerRow(
                $farmer,
                $queueYear,
                $reminderMap[$farmer->id] ?? null
            ));
        }

        $renewalRecords = null;

        if ($activeSection === 'records') {
            $recordsQuery = $this->renewalFarmersQuery($recordFilters);
            $renewalRecords = (clone $recordsQuery)
                ->latest('id')
                ->paginate(12, ['*'], 'records_page')
                ->withQueryString();

            $renewalRecords->through(fn (Farmer $farmer): array => $this->serializeRenewalFarmerRow($farmer));

            $availableYears = RenewalRequest::query()
                ->select('year')
                ->distinct()
                ->orderByDesc('year')
                ->pluck('year');
            $availableYears = $availableYears->merge(app(ApplicationAnnualCoverageService::class)->query()->pluck('year'))->unique()->sortDesc()->values();
            $availableBarangays = Barangay::query()->orderBy('name')->get(['id', 'name']);
            $sourceOptions = [
                'walk_in' => 'Walk-In',
                'mobile' => 'Mobile',
            ];
            $statusOptions = collect(RenewalStatus::cases())
                ->mapWithKeys(fn (RenewalStatus $status): array => [$status->value => $status->label()])
                ->all();
        }

        return Inertia::render('Admin/Renewals/Index', [
            'activeSection' => $activeSection,
            'renewals' => $renewals,
            'renewalRecords' => $renewalRecords,
            'queueFilters' => $queueFilters,
            'recordFilters' => [
                'record_search' => (string) ($recordFilters['record_search'] ?? ''),
                'record_year' => (string) ($recordFilters['record_year'] ?? ''),
                'record_barangay_id' => (string) ($recordFilters['record_barangay_id'] ?? ''),
                'record_source' => (string) ($recordFilters['record_source'] ?? ''),
                'record_status' => (string) ($recordFilters['record_status'] ?? ''),
            ],
            'queueFilterOptions' => [
                'years' => FeeSchedule::query()
                    ->select('year')
                    ->distinct()
                    ->pluck('year')
                    ->push($queueYear)
                    ->push(now()->year)
                    ->filter()
                    ->unique()
                    ->sortDesc()
                    ->map(fn ($year): array => ['value' => (string) $year, 'label' => (string) $year])
                    ->values()
                    ->all(),
                'barangays' => Barangay::query()
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn (Barangay $barangay): array => ['id' => $barangay->id, 'name' => $barangay->name])
                    ->values()
                    ->all(),
                'memberTypes' => MemberType::query()
                    ->orderBy('code')
                    ->get(['id', 'code', 'name'])
                    ->map(fn (MemberType $memberType): array => [
                        'id' => $memberType->id,
                        'label' => $memberType->code . ' - ' . $memberType->name,
                    ])
                    ->values()
                    ->all(),
            ],
            'filterOptions' => [
                'years' => $availableYears
                    ->map(fn ($year): array => ['value' => (string) $year, 'label' => (string) $year])
                    ->values()
                    ->all(),
                'barangays' => $availableBarangays
                    ->map(fn (Barangay $barangay): array => ['id' => $barangay->id, 'name' => $barangay->name])
                    ->values()
                    ->all(),
                'sources' => collect($sourceOptions)
                    ->map(fn (string $label, string $value): array => ['value' => $value, 'label' => $label])
                    ->values()
                    ->all(),
                'statuses' => collect(['pending' => 'Pending'] + $statusOptions)
                    ->map(fn (string $label, string $value): array => ['value' => $value, 'label' => $label])
                    ->values()
                    ->all(),
            ],
            'summary' => [
                'queueCount' => (clone $renewalsQuery)->count(),
                'recordsCount' => (clone $this->renewalFarmersQuery($recordFilters))->count(),
            ],
            'urls' => [
                'queue' => route('admin.renewals.index'),
                'records' => route('admin.renewals.index', ['section' => 'records']),
                'report' => route('admin.renewals.summary-report'),
                'farmers' => route('admin.farmers.index'),
                'quickAction' => route('admin.tasks.quick-action'),
            ],
            'pageTitle' => $activeSection === 'records' ? 'Renewal Records' : 'Renewal Processing',
            'pageSubtitle' => $activeSection === 'records'
                ? 'Browse and filter renewal history across all sources.'
                : 'List of active farmers who still need renewal for the current year.',
        ]);
    }

    public function sendReminderEmail(Request $request, Farmer $farmer): RedirectResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);
        $year = (int) $validated['year'];

        if ($this->hasRecordedAnnualDue($farmer, $year)) {
            return back()->with('error', "This farmer already has a recorded renewal for {$year}.");
        }

        $farmer->loadMissing(['profile', 'users:id,farmer_id,email']);
        $user = $farmer->users->first(fn (User $account): bool => filled($account->email));
        $email = trim((string) $user?->email);

        if ($email === '') {
            return back()->with('error', 'This farmer has no email address on record.');
        }

        $alreadySent = DB::table('notification_recipients as recipients')
            ->join('notifications', 'notifications.id', '=', 'recipients.notification_id')
            ->where('notifications.type', \App\Enums\NotificationType::RENEWAL_REMINDER->value)
            ->where('notifications.channel', 'email')
            ->where('notifications.payload->target_year', $year)
            ->where('recipients.farmer_id', $farmer->id)
            ->where('recipients.status', 'delivered')
            ->exists();

        if ($alreadySent) {
            return back()->with('error', "A renewal email has already been sent for {$year}.");
        }

        $deadline = FeeSchedule::query()
            ->where('year', $year)
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->value('renewal_deadline');
        $deadlineLabel = $deadline
            ? \Carbon\Carbon::parse($deadline)->format('F d, Y')
            : "the {$year} renewal period";
        $subject = "Annual Membership Renewal Reminder - {$year}";
        $message = "Hello {$farmer->full_name},\n\n"
            . "This is a reminder from the City Agriculture Office that your annual farmer membership renewal for {$year} is still due. "
            . "Please complete your renewal on or before {$deadlineLabel}.\n\n"
            . "Please visit the City Agriculture Office if you need assistance.\n\nThank you.";

        try {
            Mail::raw($message, fn ($mail) => $mail->to($email)->subject($subject));
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', 'The email could not be sent. Check the mail configuration and try again.');
        }

        $now = now()->toDateTimeString();
        $this->notificationDispatchService->persistQueued([
            'type' => \App\Enums\NotificationType::RENEWAL_REMINDER->value,
            'channel' => 'email',
            'subject' => $subject,
            'message' => $message,
            'payload' => ['target_year' => $year, 'deadline' => $deadline],
            'status' => 'sent',
            'queued_at' => $now,
            'sent_at' => $now,
            'created_by' => Auth::id(),
            'recipients' => [[
                'user_id' => $user?->id,
                'farmer_id' => $farmer->id,
                'recipient_address' => $email,
                'status' => 'delivered',
                'delivered_at' => $now,
            ]],
        ]);

        return back()->with('success', "Renewal reminder emailed to {$email}.");
    }

    public function summaryReport(Request $request): View|BinaryFileResponse|StreamedResponse
    {
        $recordFilters = $this->recordFilters($request);
        $reportYear = (int) ($recordFilters['record_year'] ?: now()->year);
        $recordFilters['record_year'] = (string) $reportYear;
        $format = strtolower((string) $request->query('format', 'html'));

        $feeSchedule = FeeSchedule::query()
            ->where('year', $reportYear)
            ->first()
            ?? FeeSchedule::query()->where('is_active', true)->orderByDesc('year')->first();

        $renewalRecords = $this->renewalRecordsQuery($recordFilters, $this->renewalReportRelations())
            ->latest('year')
            ->latest('id')
            ->get();

        $selectedBarangay = filled($recordFilters['record_barangay_id'])
            ? Barangay::query()->with('association:id,barangay_id,name,president_name')->find($recordFilters['record_barangay_id'], ['id', 'name'])
            : null;

        if ($selectedBarangay) {
            $selectedColumns = $this->selectedMasterlistColumns($request);
            $masterlistRecords = $this->masterlistRecordsQuery($recordFilters)
                ->latest('year')
                ->latest('id')
                ->get();
            $masterlistRows = $this->buildMasterlistRows($masterlistRecords);
            $totals = $this->masterlistTotals($masterlistRows);

            if ($format === 'xlsx') {
                $fileName = 'Barangay-Masterlist-' . now()->format('m-d-Y') . '.xlsx';

                return Excel::download(new RenewalMasterlistExport($masterlistRows, $totals, $selectedColumns), $fileName);
            }

            if ($format === 'pdf') {
                $fileName = 'Barangay-Masterlist-' . now()->format('m-d-Y') . '.pdf';
                $pdfContent = $this->renewalSummaryPdfService->buildMasterlist(
                    $masterlistRows,
                    $totals,
                    (string) ($selectedBarangay->name ?? 'N/A'),
                    (string) ($selectedBarangay->association?->name ?? 'No association recorded'),
                    (string) ($selectedBarangay->association?->president_name ?? 'No president recorded'),
                    now()->format('F d, Y h:i A'),
                    $reportYear,
                    $selectedColumns,
                );
                $export = $this->storedPdfExportService->store(
                    'renewal_barangay_masterlist',
                    $fileName,
                    $pdfContent,
                    $recordFilters,
                    $request->user()?->id
                );

                return Storage::disk($export->disk)->download($export->path, $export->file_name);
            }

            return view('admin.renewals.barangay-masterlist-report', [
                'generatedAt' => now(),
                'reportYear' => $reportYear,
                'recordFilters' => $recordFilters,
                'selectedBarangay' => $selectedBarangay,
                'selectedAssociation' => $selectedBarangay?->association,
                'masterlistRows' => $masterlistRows,
                'totals' => $totals,
                'selectedColumns' => collect($selectedColumns)
                    ->mapWithKeys(fn (string $column): array => [$column => RenewalMasterlistExport::availableColumns()[$column]])
                    ->all(),
            ]);
        }

        $selectedColumns = $this->selectedSummaryColumns($request);
        $summaryRows = $this->buildSummaryRows($renewalRecords, $feeSchedule);
        $totals = [
            'farmers' => $summaryRows->sum('farmer_count'),
            'annual_due' => $summaryRows->sum('annual_due'),
            'mortuary_fee' => $summaryRows->sum('mortuary_fee'),
            'membership_fee' => $summaryRows->sum('membership_fee'),
            'total_amount' => $summaryRows->sum('total_amount'),
            'membership_count' => $summaryRows->sum('membership_count'),
            'without_mortuary_count' => $summaryRows->sum('without_mortuary_count'),
            'female_count' => $summaryRows->sum('female_count'),
            'male_count' => $summaryRows->sum('male_count'),
        ];

        if ($format === 'xlsx') {
            $fileName = 'Renewal-Summary-' . now()->format('m-d-Y') . '.xlsx';

            return Excel::download(new RenewalSummaryExport($summaryRows, $totals, $selectedColumns), $fileName);
        }

        if ($format === 'pdf') {
            $fileName = 'Renewal-Summary-' . now()->format('m-d-Y') . '.pdf';
            $pdfContent = $this->renewalSummaryPdfService->buildSummary(
                $summaryRows,
                $totals,
                $selectedColumns,
                'Renewal Summary CY ' . $reportYear,
                now()->format('F d, Y h:i A'),
                $reportYear
            );
            $export = $this->storedPdfExportService->store(
                'renewal_summary',
                $fileName,
                $pdfContent,
                $recordFilters,
                $request->user()?->id
            );

            return Storage::disk($export->disk)->download($export->path, $export->file_name);
        }

        return view('admin.renewals.summary-report', [
            'generatedAt' => now(),
            'reportYear' => $reportYear,
            'recordFilters' => $recordFilters,
            'summaryRows' => $summaryRows,
            'totals' => $totals,
            'selectedColumns' => collect($selectedColumns)
                ->mapWithKeys(fn (string $column): array => [$column => RenewalSummaryExport::availableColumns()[$column]])
                ->all(),
        ]);
    }

    public function create(Request $request): InertiaResponse
    {
        $farmerId = $request->integer('farmer_id');
        abort_unless($farmerId, 404);
        $year = (int) $request->integer('year', now()->year);

        $farmer = Farmer::query()
            ->with([
                'profile',
                'memberType:id,code,name',
                'association:id,name',
            ])
            ->findOrFail($farmerId);

        if ($this->hasRecordedAnnualDue($farmer, $year)) {
            throw ValidationException::withMessages([
                'renewal' => 'Annual due is already recorded for ' . $year . '. Renewal cannot be created again.',
            ]);
        }

        $paymentPreview = $this->feeCalculatorService->calculateRenewal([
            'member_type' => $farmer->memberType?->code,
        ]);

        return Inertia::render('Admin/Renewals/Create', [
            'farmer' => [
                'id' => $farmer->id,
                'fullName' => $farmer->full_name,
                'farmerCode' => $farmer->farmer_code,
                'memberType' => $farmer->memberType ? [
                    'code' => $farmer->memberType->code,
                    'name' => $farmer->memberType->name,
                ] : null,
                'association' => $farmer->association?->name,
            ],
            'defaultYear' => $year,
            'amountDue' => (float) ($paymentPreview['total'] ?? 0),
            'paymentBreakdown' => [
                'annualDue' => (float) ($paymentPreview['annual_due'] ?? 0),
                'mortuaryFee' => (float) ($paymentPreview['mortuary_fee'] ?? 0),
                'total' => (float) ($paymentPreview['total'] ?? 0),
            ],
            'storeUrl' => route('admin.renewals.store'),
            'indexUrl' => route('admin.farmers.index'),
            'showFarmerUrl' => route('admin.farmers.show', $farmer),
        ]);
    }

    public function store(StoreRenewalRequest $request): RedirectResponse
    {
        $renewal = null;
        $farmer = Farmer::query()->findOrFail($request->validated('farmer_id'));
        $year = (int) $request->validated('year');

        if ($this->hasRecordedAnnualDue($farmer, $year)) {
            throw ValidationException::withMessages([
                'renewal' => 'Annual due is already recorded for ' . $year . '. Renewal cannot be created again.',
            ]);
        }

        $paymentPreview = $this->feeCalculatorService->calculateRenewal([
            'member_type' => $farmer->memberType?->code,
        ]);
        $amountDue = (float) ($paymentPreview['total'] ?? 0);

        if ((float) $request->validated('amount_paid') < $amountDue) {
            throw ValidationException::withMessages([
                'amount_paid' => 'Full payment of PHP ' . number_format($amountDue, 2) . ' is required to complete the renewal.',
            ]);
        }

        try {
            $renewal = DB::transaction(function () use ($request, $farmer, $year): RenewalRequest {
                $createdRenewal = $this->renewalRequestService->create([
                    'farmer_id' => $farmer->id,
                    'year' => $year,
                    'source' => 'walk_in',
                    'remarks' => $request->validated('remarks'),
                ]);

                $this->renewalRequestService->recordPayment($createdRenewal, [
                    'payment_method' => $request->validated('payment_method'),
                    'reference_no' => $request->validated('reference_no'),
                    'amount_paid' => $request->validated('amount_paid'),
                    'paid_at' => $request->validated('paid_at'),
                    'receipt_no' => null,
                ], Auth::id());

                return $createdRenewal;
            });
        } catch (DomainException $exception) {
            throw ValidationException::withMessages([
                'renewal' => $exception->getMessage(),
            ]);
        }

        $this->analyticsService->track('renewal_created', [
            'module' => 'renewals',
            'properties' => [
                'renewal_id' => $renewal->id,
                'source' => $renewal->source,
                'year' => $renewal->year,
            ],
        ]);

        return redirect()
            ->route('admin.farmers.show', $farmer)
            ->with('success', 'Renewal created, payment recorded, and membership updated.');
    }

    public function show(Request $request, RenewalRequest $renewal): InertiaResponse
    {
        $renewal->load([
            'farmer.profile',
            'farmer.barangay:id,name',
            'farmer.association:id,name',
            'farmer.memberType:id,code,name',
            'documents.documentType:id,code,name',
            'documents.verifier:id,name',
            'paymentAssessments.payments',
            'reviewer:id,name',
            'internalNotes.creator:id,name',
        ]);
        $renewalHistory = collect();
        $renewalPaymentHistory = collect();

        if ($renewal->farmer) {
            $renewalHistory = RenewalRequest::query()
                ->with([
                    'paymentAssessments:id,membership_transaction_id,status,total_amount_due',
                    'paymentAssessments.payments:id,payment_assessment_id,reference_no,amount_paid,paid_at',
                ])
                ->where('farmer_id', $renewal->farmer->id)
                ->whereHas('paymentAssessments', function (Builder $assessmentQuery): void {
                    $assessmentQuery->whereIn('status', $this->settledAssessmentStatuses());
                })
                ->latest('year')
                ->latest('id')
                ->get();

            $this->applyQueueAmounts($renewalHistory);

            $renewalPaymentHistory = RenewalRequest::query()
                ->with([
                    'paymentAssessments:id,membership_transaction_id,status,total_amount_due,annual_due,mortuary_fee',
                    'paymentAssessments.payments',
                ])
                ->where('farmer_id', $renewal->farmer->id)
                ->latest('year')
                ->latest('id')
                ->get()
                ->flatMap(function (RenewalRequest $paymentRenewal): Collection {
                    return $paymentRenewal->paymentAssessments->flatMap(function ($assessment) use ($paymentRenewal): Collection {
                        return collect($assessment->payments ?? [])->map(function ($payment) use ($paymentRenewal): array {
                            return [
                                'id' => $payment->id,
                                'renewalYear' => $paymentRenewal->year,
                                'paidAt' => optional($payment->paid_at)->format('M d, Y h:i A'),
                                'paidAtRaw' => optional($payment->paid_at)->timestamp ?? 0,
                                'method' => strtoupper((string) ($payment->payment_method ?? $payment->paymentMethod?->code ?? '')),
                                'referenceNo' => $payment->reference_no,
                                'statusLabel' => $payment->status?->label() ?? 'Verified',
                                'amountPaid' => (float) $payment->amount_paid,
                                'breakdown' => [
                                    'membershipFee' => 0.0,
                                    'annualDue' => (float) ($payment->annual_due ?? 0),
                                    'mortuaryFee' => (float) ($payment->mortuary_fee ?? 0),
                                ],
                            ];
                        });
                    });
                })
                ->sortByDesc('paidAtRaw')
                ->values();
        }

        $documents = ($renewal->source === 'legacy' ? $renewal->documents : $this->farmerDocumentService->ensureRenewalChecklist($renewal))
            ->map(function (FarmerDocument $document): FarmerDocument {
                $document->setAttribute('ready_for_verification', $this->farmerDocumentService->readyForVerification($document));
                $document->setAttribute('upload_present', $this->farmerDocumentService->uploadPresent($document));
                $document->setAttribute('is_expired', $this->farmerDocumentService->isExpired($document));
                $document->setAttribute('expires_at', optional($this->farmerDocumentService->expiresAt($document))?->format('M d, Y'));
                $document->setAttribute('needs_resubmission', $this->farmerDocumentService->requiresResubmission($document));
                $document->setAttribute('validation_notes', $this->farmerDocumentService->validationNotes($document));

                return $document;
            })->values();

        $assessment = $renewal->paymentAssessments->sortByDesc('id')->first();

        if (
            $renewal->farmer?->memberType?->code
            && ! in_array($assessment?->status, [
                AssessmentStatus::PAID,
                AssessmentStatus::OVERPAID,
                AssessmentStatus::WAIVED,
            ], true)
        ) {
            $assessment = $this->paymentAssessmentService->createForRenewal(
                $renewal->refresh()->load(['farmer.memberType', 'paymentAssessments.payments'])
            );
            $renewal->load('paymentAssessments.payments');
        }

        $paymentPreview = null;

        if (! $assessment && $renewal->farmer?->memberType?->code) {
            $paymentPreview = $this->feeCalculatorService->calculateRenewal([
                'member_type' => $renewal->farmer->memberType->code,
            ]);
        }

        $payments = $assessment?->payments?->sortByDesc('paid_at') ?? collect();
        $paymentHistoryPage = max(1, (int) $request->integer('payments_page', 1));
        $paymentHistoryPerPage = 8;
        $paymentSettled = in_array($assessment?->status, [
            AssessmentStatus::PAID,
            AssessmentStatus::OVERPAID,
            AssessmentStatus::WAIVED,
        ], true);
        $requiredDocuments = $documents->where('is_required', true)->values();
        $verifiedRequiredCount = $requiredDocuments->filter(fn (FarmerDocument $document) => $document->verification_status?->value === 'verified')->count();
        $missingDocumentCount = $requiredDocuments->filter(fn (FarmerDocument $document) => ! (bool) $document->getAttribute('upload_present'))->count();
        $expiredDocumentCount = $requiredDocuments->filter(fn (FarmerDocument $document) => (bool) $document->getAttribute('is_expired'))->count();
        $resubmissionCount = $requiredDocuments->filter(fn (FarmerDocument $document) => (bool) $document->getAttribute('needs_resubmission'))->count();
        $documentsComplete = $requiredDocuments->isEmpty() || $verifiedRequiredCount === $requiredDocuments->count();
        $profile = $renewal->farmer?->profile;
        $duplicateRisk = $renewal->farmer
            ? $this->farmerRegistryService->findPotentialDuplicates([
                'first_name' => $profile?->first_name,
                'last_name' => $profile?->last_name,
                'birth_date' => optional($profile?->birth_date)?->toDateString(),
                'mobile_number' => $profile?->mobile_number,
                'barangay_id' => $renewal->farmer?->barangay_id,
            ], $renewal->farmer, 1)->isNotEmpty()
            : false;
        $invalidMobileNumber = $this->farmerRegistryService->hasInvalidMobileNumber($profile?->mobile_number);
        $recordWarnings = collect();

        if ($missingDocumentCount > 0 || $expiredDocumentCount > 0 || $resubmissionCount > 0) {
            $recordWarnings->push([
                'key' => 'missing_documents',
                'type' => 'danger',
                'label' => 'Missing Documents',
                'message' => trim(collect([
                    $missingDocumentCount > 0 ? $missingDocumentCount . ' required document(s) still missing.' : null,
                    $expiredDocumentCount > 0 ? $expiredDocumentCount . ' document(s) expired.' : null,
                    $resubmissionCount > 0 ? $resubmissionCount . ' document(s) need resubmission.' : null,
                ])->filter()->implode(' ')),
            ]);
        }

        if ($duplicateRisk) {
            $recordWarnings->push([
                'key' => 'duplicate_risk',
                'type' => 'warning',
                'label' => 'Duplicate Risk',
                'message' => 'This farmer record matches an existing duplicate pattern. Review the registry before completing renewal approval.',
            ]);
        }

        if (! $paymentSettled && (float) ($assessment?->total_amount_due ?? ($paymentPreview['total'] ?? 0)) > 0) {
            $recordWarnings->push([
                'key' => 'unpaid_assessment',
                'type' => 'danger',
                'label' => 'Unpaid Assessment',
                'message' => 'Renewal dues are not yet settled. Payment must be recorded before completion.',
            ]);
        }

        if ($invalidMobileNumber) {
            $recordWarnings->push([
                'key' => 'invalid_mobile',
                'type' => 'warning',
                'label' => 'Invalid Mobile Number',
                'message' => 'The farmer mobile number format is invalid and should be corrected before approval.',
            ]);
        }

        $paymentHistoryItems = ($renewalPaymentHistory->isNotEmpty()
            ? $renewalPaymentHistory->map(fn (array $payment): array => Arr::except($payment, ['paidAtRaw']))->values()
            : $payments->map(fn ($payment): array => [
                'id' => $payment->id,
                'renewalYear' => $renewal->year,
                'paidAt' => optional($payment->paid_at)->format('M d, Y h:i A'),
                'method' => strtoupper((string) ($payment->payment_method ?? $payment->paymentMethod?->code ?? '')),
                'referenceNo' => $payment->reference_no,
                'statusLabel' => $payment->status?->label() ?? 'Verified',
                'amountPaid' => (float) $payment->amount_paid,
                'breakdown' => [
                    'membershipFee' => 0.0,
                    'annualDue' => (float) ($payment->annual_due ?? 0),
                    'mortuaryFee' => (float) ($payment->mortuary_fee ?? 0),
                ],
            ])->values());

        $paymentHistoryPaginator = new LengthAwarePaginator(
            $paymentHistoryItems->forPage($paymentHistoryPage, $paymentHistoryPerPage)->values(),
            $paymentHistoryItems->count(),
            $paymentHistoryPerPage,
            $paymentHistoryPage,
            [
                'path' => url()->current(),
                'pageName' => 'payments_page',
                'query' => request()->query(),
            ]
        );

        $recordMemberType = $renewal->source === 'legacy'
            ? ($assessment?->feeSchedule?->memberType ?? $renewal->farmer?->memberType)
            : $renewal->farmer?->memberType;

        return Inertia::render('Admin/Renewals/Show', [
            'renewal' => [
                'id' => $renewal->id,
                'applicationNo' => $renewal->application_no,
                'year' => $renewal->year,
                'status' => [
                    'value' => $renewal->status?->value,
                    'label' => $renewal->status?->label() ?? 'Pending',
                ],
                'source' => $renewal->source,
                'submittedAt' => optional($renewal->submitted_at)->format('F d, Y h:i A'),
                'reviewedAt' => optional($renewal->reviewed_at)->format('F d, Y h:i A'),
                'rejectionReason' => $renewal->rejection_reason,
                'accountability' => $this->accountability($renewal),
            ],
            'farmer' => [
                'id' => $renewal->farmer?->id,
                'fullName' => $renewal->farmer?->full_name,
                'farmerCode' => $renewal->farmer?->farmer_code,
                'memberType' => $recordMemberType ? [
                    'code' => $recordMemberType->code,
                    'name' => $recordMemberType->name,
                ] : null,
                'barangay' => $renewal->farmer?->barangay?->name,
                'association' => $renewal->farmer?->association?->name,
                'mobileNumber' => $renewal->farmer?->profile?->mobile_number,
                'address' => $renewal->farmer?->profile?->address,
            ],
            'assessment' => [
                'id' => $assessment?->id,
                'status' => [
                    'value' => $assessment?->status?->value,
                    'label' => $assessment?->status?->label() ?? 'Pending',
                ],
                'totalAmountDue' => (float) ($assessment?->total_amount_due ?? ($paymentPreview['total'] ?? 0)),
                'membershipFee' => 0.0,
                'annualDue' => (float) ($assessment?->annual_due ?? ($paymentPreview['annual_due'] ?? 0)),
                'mortuaryFee' => (float) ($assessment?->mortuary_fee ?? ($paymentPreview['mortuary_fee'] ?? 0)),
            ],
            'payments' => [
                'data' => $paymentHistoryPaginator->items(),
                'current_page' => $paymentHistoryPaginator->currentPage(),
                'last_page' => $paymentHistoryPaginator->lastPage(),
                'per_page' => $paymentHistoryPaginator->perPage(),
                'total' => $paymentHistoryPaginator->total(),
                'links' => $paymentHistoryPaginator->linkCollection()->toArray(),
            ],
            'renewalHistory' => $renewalHistory->map(function (RenewalRequest $historyRenewal) use ($renewal): array {
                $latestAssessment = $historyRenewal->paymentAssessments->sortByDesc('id')->first();
                $latestPayment = $latestAssessment?->payments?->sortByDesc('paid_at')->first();
                $recordDate = $latestPayment?->paid_at ?? $historyRenewal->submitted_at;

                return [
                    'id' => $historyRenewal->id,
                    'year' => $historyRenewal->year,
                    'statusLabel' => $this->renewalStatusLabel($historyRenewal),
                    'sourceLabel' => strtoupper(str_replace('_', ' ', (string) $historyRenewal->source)),
                    'amountPaid' => (float) ($historyRenewal->getAttribute('queue_amount_paid') ?? 0),
                    'amountToPay' => (float) ($historyRenewal->getAttribute('queue_amount_to_pay') ?? 0),
                    'paymentReference' => $latestPayment?->reference_no,
                    'settledAt' => optional($recordDate)->format('M d, Y h:i A'),
                    'showUrl' => route('admin.renewals.show', $historyRenewal),
                    'isCurrent' => (int) $historyRenewal->id === (int) $renewal->id,
                ];
            })->values()->all(),
            'recordWarnings' => $recordWarnings->values()->all(),
            'internalNotes' => $renewal->internalNotes
                ->map(fn ($note): array => [
                    'id' => $note->id,
                    'body' => $note->body,
                    'createdBy' => $note->creator?->name ?? 'Staff',
                    'createdAt' => optional($note->created_at)->format('M d, Y h:i A'),
                ])
                ->values()
                ->all(),
            'documents' => $documents->map(fn (FarmerDocument $document): array => [
                'id' => $document->id,
                'label' => $document->document_type?->label() ?? 'Document',
                'isRequired' => (bool) $document->is_required,
                'uploadPresent' => (bool) $document->getAttribute('upload_present'),
                'readyForVerification' => (bool) $document->getAttribute('ready_for_verification'),
                'isReceived' => (bool) $document->is_received,
                'verificationStatus' => [
                    'value' => $document->verification_status?->value,
                    'label' => $document->verification_status?->label() ?? 'Pending',
                ],
                'remarks' => $document->remarks,
                'previewMimeType' => $document->getAttribute('upload_present')
                    ? $this->previewMimeTypeForDocument($document)
                    : null,
                'originalName' => $document->original_name ?: basename((string) $document->file_path),
                'isExpired' => (bool) $document->getAttribute('is_expired'),
                'expiresAtLabel' => $document->getAttribute('expires_at'),
                'needsResubmission' => (bool) $document->getAttribute('needs_resubmission'),
                'validationNotes' => $document->getAttribute('validation_notes') ?? [],
                'actions' => [
                    'reviewUrl' => route('admin.renewals.documents.review', [$renewal, $document]),
                    'viewUrl' => $document->getAttribute('upload_present')
                        ? route('admin.renewals.documents.view', [$renewal, $document])
                        : null,
                ],
            ])->values()->all(),
            'flow' => [
                'isHistorical' => $renewal->source === 'legacy',
                'documentsComplete' => $documentsComplete,
                'requiredCount' => $requiredDocuments->count(),
                'verifiedCount' => $verifiedRequiredCount,
                'missingCount' => $missingDocumentCount,
                'expiredCount' => $expiredDocumentCount,
                'resubmissionCount' => $resubmissionCount,
                'paymentSettled' => $paymentSettled,
                'step' => $paymentSettled ? 4 : ($documentsComplete ? 3 : 2),
            ],
            'urls' => [
                'index' => route('admin.farmers.index'),
                'recordPayment' => route('admin.renewals.payment.store', $renewal),
                'farmerShow' => route('admin.farmers.show', $renewal->farmer()->firstOrFail()),
                'review' => route('admin.renewals.review', $renewal),
                'storeInternalNote' => route('admin.renewals.internal-notes.store', $renewal),
            ],
        ]);
    }

    public function review(ReviewRenewalRequest $request, RenewalRequest $renewal): RedirectResponse
    {
        try {
            if ($request->validated('action') === 'approve') {
                $this->renewalRequestService->approve($renewal, Auth::id());
                $message = 'Renewal approved. Payment can now be completed in admin or the Farmer PWA.';
                $this->analyticsService->track('renewal_approved', [
                    'module' => 'renewals',
                    'properties' => [
                        'renewal_id' => $renewal->id,
                        'source' => $renewal->source,
                        'year' => $renewal->year,
                    ],
                ]);
            } else {
                $this->renewalRequestService->reject($renewal, (string) $request->validated('remarks'), Auth::id());
                $message = 'Renewal rejected.';
                $this->analyticsService->track('renewal_rejected', [
                    'module' => 'renewals',
                    'properties' => [
                        'renewal_id' => $renewal->id,
                        'source' => $renewal->source,
                        'year' => $renewal->year,
                    ],
                ]);
            }
        } catch (DomainException $exception) {
            throw ValidationException::withMessages([
                'renewal' => $exception->getMessage(),
            ]);
        }

        return redirect()->route('admin.renewals.show', $renewal)->with('success', $message);
    }

    public function reviewDocument(VerifyFarmerDocumentRequest $request, RenewalRequest $renewal, FarmerDocument $document): RedirectResponse
    {
        if ((int) $document->membership_transaction_id !== (int) $renewal->id) {
            abort(404);
        }

        try {
            $action = $request->validated('action');
            $remarks = $request->validated('remarks');

            match ($action) {
                'receive' => $this->renewalRequestService->markDocumentReceived($renewal, $document->id, true, Auth::id()),
                'unreceive' => $this->renewalRequestService->markDocumentReceived($renewal, $document->id, false, Auth::id()),
                'verify' => $this->renewalRequestService->verifyDocument($renewal, $document->id, Auth::id(), $remarks),
                'reject' => $this->renewalRequestService->rejectDocument($renewal, $document->id, Auth::id(), $remarks),
            };
        } catch (DomainException $exception) {
            throw ValidationException::withMessages([
                'document' => $exception->getMessage(),
            ]);
        }

        return redirect()->route('admin.renewals.show', $renewal)->with('success', 'Renewal document checklist updated.');
    }

    public function recordPayment(StorePaymentRequest $request, RenewalRequest $renewal): RedirectResponse
    {
        try {
            $result = $this->renewalRequestService->recordPayment($renewal, $request->validated(), Auth::id());
        } catch (DomainException $exception) {
            throw ValidationException::withMessages([
                'payment' => $exception->getMessage(),
            ]);
        }

        $this->analyticsService->track('renewal_payment_recorded', [
            'module' => 'renewals',
            'properties' => [
                'renewal_id' => $renewal->id,
                'source' => $renewal->source,
                'year' => $renewal->year,
                'assessment_status' => $result['summary']['assessment_status']->value,
            ],
        ]);

        if (in_array($result['summary']['assessment_status'], [AssessmentStatus::PAID, AssessmentStatus::OVERPAID, AssessmentStatus::WAIVED], true)) {
            return redirect()
                ->route('admin.farmers.show', $renewal->farmer()->firstOrFail())
                ->with('success', 'Renewal payment recorded and membership updated.');
        }

        return redirect()
            ->route('admin.renewals.show', $renewal)
            ->with('success', 'Payment recorded. The renewal still has an outstanding balance.');
    }

    public function viewDocument(RenewalRequest $renewal, FarmerDocument $document): StreamedResponse
    {
        if ((int) $document->membership_transaction_id !== (int) $renewal->id) {
            abort(404);
        }

        if (! $this->farmerDocumentService->uploadPresent($document)) {
            abort(404);
        }

        $disk = $document->disk ?: 'public';
        $filename = $document->original_name ?: basename((string) $document->path);

        return Storage::disk($disk)->response((string) $document->path, $filename, [
            'Content-Type' => $this->previewMimeTypeForDocument($document),
        ], 'inline');
    }

    private function previewMimeTypeForDocument(FarmerDocument $document): string
    {
        $mimeType = strtolower(trim((string) ($document->mime_type ?? '')));

        if ($mimeType !== '' && $mimeType !== 'application/octet-stream') {
            return $mimeType;
        }

        $disk = $document->disk ?: 'public';
        $path = (string) $document->path;
        $storageMimeType = strtolower((string) (Storage::disk($disk)->mimeType($path) ?: ''));

        if ($storageMimeType !== '' && $storageMimeType !== 'application/octet-stream') {
            return $storageMimeType;
        }

        return match (strtolower(pathinfo($document->original_name ?: $path, PATHINFO_EXTENSION))) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'heic' => 'image/heic',
            'heif' => 'image/heif',
            default => 'application/octet-stream',
        };
    }

    private function renewalListRelations(): array
    {
        return [
            'farmer:id,farmer_code,member_type_id',
            'farmer.profile:farmer_id,first_name,middle_name,last_name,suffix',
            'farmer.memberType:id,code',
            'paymentAssessments:id,membership_transaction_id,status,total_amount_due',
            'paymentAssessments.payments:id,payment_assessment_id,reference_no,amount_paid,paid_at',
            'reviewer:id,name',
        ];
    }

    private function renewalReportRelations(): array
    {
        return [
            'farmer:id,farmer_code,member_type_id,barangay_id',
            'farmer.profile:farmer_id,first_name,middle_name,last_name,suffix,sex',
            'farmer.barangay:id,name',
            'farmer.memberType:id,code,requires_membership_fee',
            'paymentAssessments:id,membership_transaction_id,status,total_amount_due',
        ];
    }

    private function masterlistReportRelations(): array
    {
        return [
            'membershipTransaction:id,farmer_id,transaction_type,source,submitted_at',
            'membershipTransaction.farmer:id,farmer_code,member_type_id,barangay_id,association_id',
            'membershipTransaction.farmer.profile:farmer_id,first_name,middle_name,last_name,suffix,sex',
            'membershipTransaction.farmer.memberType:id,code,requires_membership_fee',
            'membershipTransaction.farmer.barangay:id,name',
            'membershipTransaction.farmer.association:id,name,president_name',
            'membershipTransaction.paymentAssessments:id,membership_transaction_id,status,total_amount_due,membership_fee,annual_due,mortuary_fee',
        ];
    }

    private function recordFilters(Request $request): array
    {
        return [
            'record_search' => (string) $request->string('record_search'),
            'record_year' => $request->filled('record_year') ? (string) $request->input('record_year') : '',
            'record_barangay_id' => $request->filled('record_barangay_id') ? (string) $request->input('record_barangay_id') : '',
            'record_source' => $request->filled('record_source') ? (string) $request->input('record_source') : '',
            'record_status' => $request->filled('record_status') ? (string) $request->input('record_status') : '',
        ];
    }

    private function queueFilters(Request $request): array
    {
        $queueYear = $request->filled('queue_year')
            ? (int) $request->input('queue_year')
            : now()->year;

        return [
            'queue_search' => (string) $request->string('queue_search'),
            'queue_year' => (string) ($queueYear > 0 ? $queueYear : now()->year),
            'queue_barangay_id' => $request->filled('queue_barangay_id')
                ? (string) $request->input('queue_barangay_id')
                : '',
            'queue_member_type_id' => $request->filled('queue_member_type_id')
                ? (string) $request->input('queue_member_type_id')
                : '',
        ];
    }

    private function renewalQueueQuery(int $queueYear, array $queueFilters): Builder
    {
        return $this->renewalReminderService
            ->eligibleFarmerQuery($queueYear)
            ->when($queueFilters['queue_search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $farmerQuery) use ($search): void {
                    $farmerQuery
                        ->where('farmer_code', 'like', "%{$search}%")
                        ->orWhereHas('profile', function (Builder $profileQuery) use ($search): void {
                            $profileQuery
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('middle_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                });
            })
            ->when(
                $queueFilters['queue_barangay_id'] ?? null,
                fn (Builder $query, string $barangayId) => $query->where('barangay_id', $barangayId)
            )
            ->when(
                $queueFilters['queue_member_type_id'] ?? null,
                fn (Builder $query, string $memberTypeId) => $query->where('member_type_id', $memberTypeId)
            );
    }

    private function renewalRecordsQuery(array $recordFilters, array $relations): Builder
    {
        return RenewalRequest::query()
            ->with($relations)
            ->whereHas('paymentAssessments', function (Builder $assessmentQuery): void {
                $assessmentQuery->whereIn('status', $this->settledAssessmentStatuses());
            })
            ->when($recordFilters['record_search'] ?? null, function (Builder $query, string $search): void {
                $query->whereHas('farmer', function (Builder $farmerQuery) use ($search): void {
                    $farmerQuery
                        ->where('farmer_code', 'like', "%{$search}%")
                        ->orWhereHas('profile', function (Builder $profileQuery) use ($search): void {
                            $profileQuery
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('middle_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($recordFilters['record_year'] ?? null, fn (Builder $query, string $year) => $query->where('year', (int) $year))
            ->when($recordFilters['record_barangay_id'] ?? null, function (Builder $query, string $barangayId): void {
                $query->whereHas('farmer', fn (Builder $farmerQuery) => $farmerQuery->where('barangay_id', $barangayId));
            })
            ->when($recordFilters['record_source'] ?? null, fn (Builder $query, string $source) => $query->where('source', $source))
            ->when(
                $recordFilters['record_status'] ?? null,
                function (Builder $query, string $status): void {
                    if ($status === 'pending') {
                        $query->whereIn('status', [
                            RenewalStatus::SUBMITTED->value,
                            RenewalStatus::UNDER_REVIEW->value,
                        ]);

                        return;
                    }

                    $query->where('status', $status);
                }
            );
    }

    private function masterlistRecordsQuery(array $recordFilters): Builder
    {
        return MembershipLedger::query()
            ->with($this->masterlistReportRelations())
            ->where('amount_paid', '>', 0)
            ->when($recordFilters['record_search'] ?? null, function (Builder $query, string $search): void {
                $query->whereHas('membershipTransaction.farmer', function (Builder $farmerQuery) use ($search): void {
                    $farmerQuery
                        ->where('farmer_code', 'like', "%{$search}%")
                        ->orWhereHas('profile', function (Builder $profileQuery) use ($search): void {
                            $profileQuery
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('middle_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($recordFilters['record_year'] ?? null, fn (Builder $query, string $year) => $query->where('year', (int) $year))
            ->when($recordFilters['record_barangay_id'] ?? null, fn (Builder $query, string $barangayId) => $query->whereHas('membershipTransaction.farmer', fn (Builder $farmerQuery) => $farmerQuery->where('barangay_id', $barangayId)))
            ->when($recordFilters['record_source'] ?? null, fn (Builder $query, string $source) => $query->whereHas('membershipTransaction', fn (Builder $transactionQuery) => $transactionQuery->where('source', $source)))
            ->when(
                $recordFilters['record_status'] ?? null,
                function (Builder $query, string $status): void {
                    $query->whereHas('membershipTransaction', function (Builder $transactionQuery) use ($status): void {
                        if ($status === 'pending') {
                            $transactionQuery->whereIn('status', [
                                RenewalStatus::SUBMITTED->value,
                                RenewalStatus::UNDER_REVIEW->value,
                            ]);

                            return;
                        }

                        $transactionQuery->where('status', $status);
                    });
                }
            );
    }

    private function renewalFarmersQuery(array $recordFilters): Builder
    {
        return Farmer::query()
            ->with([
                'profile:farmer_id,first_name,middle_name,last_name,suffix',
                'memberType:id,code,name',
                'renewalRequests' => function ($query): void {
                    $query
                        ->with([
                            'paymentAssessments:id,membership_transaction_id,status,total_amount_due',
                            'paymentAssessments.payments:id,payment_assessment_id,reference_no,amount_paid,paid_at',
                            'reviewer:id,name',
                        ])
                        ->whereHas('paymentAssessments', function (Builder $assessmentQuery): void {
                            $assessmentQuery->whereIn('status', $this->settledAssessmentStatuses());
                        })
                        ->latest('year')
                        ->latest('id');
                },
                'membershipLedgers' => function ($query): void {
                    $query->where(fn (Builder $ledger) => app(ApplicationAnnualCoverageService::class)->constrain($ledger))
                        ->with(['membershipTransaction.paymentAssessments.payments', 'membershipTransaction.reviewer']);
                },
            ])
            ->where(function (Builder $records) use ($recordFilters): void {
                $records->whereHas('renewalRequests', function (Builder $query) use ($recordFilters): void {
                    $query
                        ->whereHas('paymentAssessments', function (Builder $assessmentQuery): void {
                            $assessmentQuery->whereIn('status', $this->settledAssessmentStatuses());
                        })
                        ->when($recordFilters['record_year'] ?? null, fn (Builder $query, string $year) => $query->where('year', (int) $year))
                        ->when($recordFilters['record_source'] ?? null, fn (Builder $query, string $source) => $query->where('source', $source))
                        ->when(
                            $recordFilters['record_status'] ?? null,
                            function (Builder $query, string $status): void {
                                if ($status === 'pending') {
                                    $query->whereIn('status', [
                                        RenewalStatus::SUBMITTED->value,
                                        RenewalStatus::UNDER_REVIEW->value,
                                    ]);

                                    return;
                                }

                                $query->where('status', $status);
                            }
                        );
                })->orWhereHas('membershipLedgers', function (Builder $ledger) use ($recordFilters): void {
                    app(ApplicationAnnualCoverageService::class)->constrain($ledger)
                        ->when($recordFilters['record_year'] ?? null, fn (Builder $query, string $year) => $query->where('membership_ledgers.year', (int) $year))
                        ->whereHas('membershipTransaction', function (Builder $transaction) use ($recordFilters): void {
                            $transaction->when($recordFilters['record_source'] ?? null, fn (Builder $query, string $source) => $query->where('source', $source));
                        });
                    if (filled($recordFilters['record_status'] ?? null) && $recordFilters['record_status'] !== 'approved') {
                        $ledger->whereRaw('1 = 0');
                    }
                });
            })
            ->when($recordFilters['record_search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $farmerQuery) use ($search): void {
                    $farmerQuery
                        ->where('farmer_code', 'like', "%{$search}%")
                        ->orWhereHas('profile', function (Builder $profileQuery) use ($search): void {
                            $profileQuery
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('middle_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($recordFilters['record_barangay_id'] ?? null, fn (Builder $query, string $barangayId) => $query->where('barangay_id', $barangayId));
    }

    private function settledAssessmentStatuses(): array
    {
        return [
            AssessmentStatus::PAID->value,
            AssessmentStatus::OVERPAID->value,
            AssessmentStatus::WAIVED->value,
        ];
    }

    private function selectedSummaryColumns(Request $request): array
    {
        $available = array_keys(RenewalSummaryExport::availableColumns());
        $requested = collect($request->input('columns', []))
            ->map(fn ($value): string => (string) $value)
            ->filter(fn (string $value): bool => in_array($value, $available, true))
            ->unique()
            ->values()
            ->all();

        return $requested !== []
            ? collect($available)->filter(fn (string $column): bool => in_array($column, $requested, true))->values()->all()
            : $available;
    }

    private function selectedMasterlistColumns(Request $request): array
    {
        $available = array_keys(RenewalMasterlistExport::availableColumns());
        $requested = collect($request->input('columns', []))
            ->map(fn ($value): string => (string) $value)
            ->filter(fn (string $value): bool => in_array($value, $available, true))
            ->values()
            ->all();

        return $requested !== [] ? $requested : array_keys(RenewalMasterlistExport::availableColumns());
    }

    private function buildSummaryRows(iterable $renewalRecords, ?FeeSchedule $feeSchedule): Collection
    {
        return $this->distinctRenewalsByFarmer($renewalRecords)
            ->filter(fn ($renewal) => $renewal instanceof RenewalRequest && $renewal->farmer?->barangay?->name)
            ->map(function (RenewalRequest $renewal) use ($feeSchedule): array {
                $fees = $this->renewalFeeBreakdown($renewal, $feeSchedule);

                return [
                    'barangay' => (string) $renewal->farmer->barangay->name,
                    'farmer_count' => 1,
                    'annual_due' => $fees['annual_due'],
                    'mortuary_fee' => $fees['mortuary_fee'],
                    'membership_fee' => $fees['membership_fee'],
                    'total_amount' => $fees['total_amount'],
                    'membership_count' => $fees['membership_fee'] > 0 ? 1 : 0,
                    'without_mortuary_count' => $fees['mortuary_fee'] <= 0 ? 1 : 0,
                    'female_count' => strtolower((string) ($renewal->farmer?->profile?->sex ?? '')) === 'female' ? 1 : 0,
                    'male_count' => strtolower((string) ($renewal->farmer?->profile?->sex ?? '')) === 'male' ? 1 : 0,
                ];
            })
            ->groupBy('barangay')
            ->map(function (Collection $rows, string $barangay): array {
                return [
                    'barangay' => $barangay,
                    'farmer_count' => $rows->sum('farmer_count'),
                    'annual_due' => round((float) $rows->sum('annual_due'), 2),
                    'mortuary_fee' => round((float) $rows->sum('mortuary_fee'), 2),
                    'membership_fee' => round((float) $rows->sum('membership_fee'), 2),
                    'total_amount' => round((float) $rows->sum('total_amount'), 2),
                    'membership_count' => $rows->sum('membership_count'),
                    'without_mortuary_count' => $rows->sum('without_mortuary_count'),
                    'female_count' => $rows->sum('female_count'),
                    'male_count' => $rows->sum('male_count'),
                ];
            })
            ->sortBy('barangay', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    private function buildMasterlistRows(iterable $masterlistRecords): Collection
    {
        return collect($masterlistRecords)
            ->filter(fn ($ledger): bool => $ledger instanceof MembershipLedger && $ledger->farmer !== null)
            ->sortByDesc(fn (MembershipLedger $ledger): string => sprintf(
                '%04d-%010d',
                (int) $ledger->year,
                (int) $ledger->id
            ))
            ->unique(fn (MembershipLedger $ledger): int => (int) $ledger->farmer->id)
            ->values()
            ->map(function (MembershipLedger $ledger): array {
                $farmer = $ledger->farmer;
                $profile = $farmer?->profile;
                $assessment = $ledger->membershipTransaction?->paymentAssessments?->sortByDesc('id')->first();
                $memberTypeCode = strtoupper((string) ($farmer?->memberType?->code ?? ''));
                $isNewMember = in_array($memberTypeCode, ['NM', 'NSC'], true);
                $annualDue = round((float) ($assessment?->annual_due ?? 0), 2);
                $mortuaryFee = round((float) ($assessment?->mortuary_fee ?? 0), 2);
                $membershipFee = round((float) ($assessment?->membership_fee ?? 0), 2);

                if ($assessment === null && ! $isNewMember) {
                    $membershipFee = 0.0;
                }

                $givenNames = collect([
                    $profile?->first_name,
                    $profile?->middle_name,
                    $profile?->suffix,
                ])->filter()->implode(' ');
                $reportName = collect([
                    $profile?->last_name,
                    $givenNames,
                ])->filter()->implode(', ');

                return [
                    'name' => $reportName !== '' ? $reportName : ($farmer?->full_name ?? 'Unknown Farmer'),
                    'sort_name' => trim(collect([
                        $profile?->last_name,
                        $profile?->first_name,
                        $profile?->middle_name,
                        $profile?->suffix,
                        $farmer?->farmer_code,
                    ])->filter()->implode(' ')),
                    'annual_due' => $annualDue,
                    'mortuary_fee' => $mortuaryFee,
                    'membership_fee' => $membershipFee,
                    'total_amount' => round($annualDue + $mortuaryFee + $membershipFee, 2),
                    'remarks' => $memberTypeCode,
                    'is_new_member' => $isNewMember,
                    'has_mortuary' => $mortuaryFee > 0,
                ];
            })
            ->sortBy('sort_name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->map(function (array $row): array {
                unset($row['sort_name']);

                return $row;
            });
    }

    private function masterlistTotals(Collection $rows): array
    {
        $memberTypeCounts = collect(['OSC', 'NM', 'OM', 'NSC'])
            ->mapWithKeys(fn (string $code): array => [
                $code => $rows->where('remarks', $code)->count(),
            ])
            ->all();

        return [
            'annual_due' => $rows->sum('annual_due'),
            'mortuary_fee' => $rows->sum('mortuary_fee'),
            'membership_fee' => $rows->sum('membership_fee'),
            'total_amount' => $rows->sum('total_amount'),
            'new_member_count' => $rows->sum('is_new_member'),
            'old_member_count' => $rows->sum(fn (array $row): int => $row['is_new_member'] ? 0 : 1),
            'with_mortuary_count' => $rows->sum(fn (array $row): int => $row['has_mortuary'] ? 1 : 0),
            'without_mortuary_count' => $rows->sum(fn (array $row): int => $row['has_mortuary'] ? 0 : 1),
            'total_member_count' => $rows->count(),
            'member_type_counts' => $memberTypeCounts,
        ];
    }

    private function distinctRenewalsByFarmer(iterable $renewalRecords): Collection
    {
        return collect($renewalRecords)
            ->filter(fn ($renewal): bool => $renewal instanceof RenewalRequest && $renewal->farmer !== null)
            ->sortByDesc(fn (RenewalRequest $renewal): string => sprintf(
                '%04d-%010d',
                (int) $renewal->year,
                (int) $renewal->id
            ))
            ->unique(fn (RenewalRequest $renewal): int => (int) $renewal->farmer->id)
            ->values();
    }

    private function renewalFeeBreakdown(RenewalRequest $renewal, ?FeeSchedule $feeSchedule): array
    {
        $assessment = $renewal->paymentAssessments->sortByDesc('id')->first();
        $memberType = $renewal->farmer?->memberType;
        $feePreview = null;
        if ($memberType?->code) {
            $context = ['member_type' => $memberType->code];

            if ($feeSchedule) {
                $context['fee_schedule'] = $feeSchedule->toArray();
            }

            $feePreview = $this->feeCalculatorService->calculateRenewal($context);
        }

        $annualDue = round((float) ($feePreview['annual_due'] ?? 0), 2);
        $mortuaryFee = round((float) ($feePreview['mortuary_fee'] ?? 0), 2);
        $membershipFee = 0.0;

        return [
            'annual_due' => $annualDue,
            'mortuary_fee' => $mortuaryFee,
            'membership_fee' => $membershipFee,
            'total_amount' => round($annualDue + $mortuaryFee + $membershipFee, 2),
        ];
    }

    private function applyQueueAmounts(iterable $renewals): void
    {
        foreach ($renewals as $renewal) {
            if (! $renewal instanceof RenewalRequest) {
                continue;
            }

            $assessment = $renewal->paymentAssessments->sortByDesc('id')->first();
            $amountPaid = (float) ($assessment?->payments?->sum('amount_paid') ?? 0);
            $amountDue = (float) ($assessment?->total_amount_due ?? 0);

            if ($amountDue <= 0 && $renewal->farmer?->memberType?->code) {
                $amountDue = (float) ($this->feeCalculatorService->calculateRenewal([
                    'member_type' => $renewal->farmer->memberType->code,
                ])['total'] ?? 0);
            }

            $renewal->setAttribute('queue_amount_to_pay', max(0, round($amountDue - $amountPaid, 2)));
            $renewal->setAttribute('queue_amount_paid', round($amountPaid, 2));
        }
    }

    private function hasRecordedAnnualDue(Farmer $farmer, int $year): bool
    {
        return MembershipLedger::query()
            ->whereHas('membershipTransaction', fn (Builder $query) => $query->where('farmer_id', $farmer->id))
            ->where('year', $year)
            ->where('amount_paid', '>', 0)
            ->exists();
    }

    private function serializeRenewalRow(RenewalRequest $renewal, bool $includeRecordFields = false): array
    {
        $latestAssessment = $renewal->paymentAssessments->sortByDesc('id')->first();
        $latestPayment = $latestAssessment?->payments?->sortByDesc('paid_at')->first();
        $recordDate = $latestPayment?->paid_at ?? $renewal->submitted_at;

        return [
            'id' => $renewal->id,
            'recordKey' => (string) $renewal->getRouteKey(),
            'farmer' => [
                'fullName' => $renewal->farmer?->full_name ?? 'Unknown Farmer',
                'farmerCode' => $renewal->farmer?->farmer_code ?? 'No code',
                'memberType' => $renewal->farmer?->memberType ? [
                    'code' => $renewal->farmer->memberType->code,
                    'name' => $renewal->farmer->memberType->name ?? $renewal->farmer->memberType->code,
                ] : null,
            ],
            'year' => $renewal->year,
            'source' => $renewal->source,
            'sourceLabel' => strtoupper(str_replace('_', ' ', (string) $renewal->source)),
            'status' => [
                'value' => $renewal->status?->value ?? 'submitted',
                'label' => $this->renewalStatusLabel($renewal),
            ],
            'amountToPay' => (float) ($renewal->getAttribute('queue_amount_to_pay') ?? 0),
            'amountPaid' => (float) ($renewal->getAttribute('queue_amount_paid') ?? 0),
            'submittedAt' => optional($recordDate)->format('M d, Y h:i A'),
            'paymentReference' => $latestPayment?->reference_no,
            'actions' => [
                'showUrl' => route('admin.renewals.show', $renewal),
            ],
            'accountability' => $this->accountability($renewal),
            'quickActions' => [
                'canReview' => true,
                'canMarkComplete' => (Auth::user()?->hasRole(User::ROLE_ADMIN) ?? false) && $renewal->source !== 'walk_in',
                'canRequestCorrection' => Auth::user()?->hasRole(User::ROLE_ADMIN) ?? false,
                'canForwardToAdmin' => ! (Auth::user()?->hasRole(User::ROLE_ADMIN) ?? false),
            ],
            'renewalYearsCount' => 1,
            'record' => $includeRecordFields ? [
                'submittedAt' => optional($recordDate)->format('M d, Y h:i A'),
                'paymentReference' => $latestPayment?->reference_no,
                'renewalYearsCount' => 1,
            ] : null,
        ];
    }

    private function serializeRenewalFarmerRow(Farmer $farmer): array
    {
        $renewals = $farmer->renewalRequests instanceof Collection
            ? $farmer->renewalRequests->values()
            : collect();

        $this->applyQueueAmounts($renewals);

        /** @var RenewalRequest|null $latestRenewal */
        $latestRenewal = $renewals->sortByDesc(fn (RenewalRequest $renewal) => sprintf(
            '%04d-%010d',
            (int) $renewal->year,
            (int) $renewal->id
        ))->first();

        $coverage = $farmer->membershipLedgers->sortByDesc('year')->unique('year')->values();
        $latestCoverage = $coverage->first();
        $years = $renewals->pluck('year')->merge($coverage->pluck('year'))->map(fn ($year): int => (int) $year)->unique()->sortDesc()->values();
        $coverageAmount = (float) $coverage->sum(fn (MembershipLedger $ledger): float => (float) ($ledger->membershipTransaction->paymentAssessments->sortByDesc('id')->first()?->annual_due ?? 0));

        if ($latestCoverage && (! $latestRenewal || $latestCoverage->year > $latestRenewal->year)) {
            $transaction = $latestCoverage->membershipTransaction;
            $assessment = $transaction->paymentAssessments->sortByDesc('id')->first();
            $payment = $assessment?->payments->sortByDesc('id')->first();
            return [
                'id' => $farmer->id,
                'recordKey' => '',
                'farmer' => [
                    'fullName' => $farmer->full_name,
                    'farmerCode' => $farmer->farmer_code,
                    'memberType' => $farmer->memberType ? ['code' => $farmer->memberType->code, 'name' => $farmer->memberType->name] : null,
                ],
                'years' => $years->all(),
                'yearRangeLabel' => $years->implode(', '),
                'renewalYearsCount' => $years->count(),
                'sourceLabel' => strtoupper(str_replace('_', ' ', (string) $transaction->source)) . ' · Application annual dues',
                'status' => ['value' => 'approved', 'label' => 'Completed'],
                'amountToPay' => 0.0,
                'amountPaid' => round($coverageAmount + (float) $renewals->sum(fn ($renewal) => $renewal->getAttribute('queue_amount_paid') ?? 0), 2),
                'submittedAt' => optional($latestCoverage->paid_at ?? $transaction->submitted_at)?->format('M d, Y h:i A'),
                'paymentReference' => $payment?->reference_no,
                'actions' => ['showUrl' => route('admin.membership-applications.show', $transaction->application_no)],
                'accountability' => ['reviewedBy' => $transaction->reviewer?->name ?? 'Not recorded', 'lastUpdatedBy' => $transaction->reviewer?->name ?? 'Not recorded'],
                'quickActions' => [],
                'record' => ['renewalCount' => $renewals->count() + $coverage->count(), 'renewalYearsCount' => $years->count()],
            ];
        }

        if (! $latestRenewal) {
            return [
                'id' => $farmer->id,
                'recordKey' => '',
                'farmer' => [
                    'fullName' => $farmer->full_name ?? 'Unknown Farmer',
                    'farmerCode' => $farmer->farmer_code ?? 'No code',
                    'memberType' => $farmer->memberType ? [
                        'code' => $farmer->memberType->code,
                        'name' => $farmer->memberType->name ?? $farmer->memberType->code,
                    ] : null,
                ],
                'years' => [],
                'yearRangeLabel' => 'No settled renewals',
                'sourceLabel' => 'N/A',
                'status' => ['value' => 'pending', 'label' => 'Pending'],
                'amountToPay' => 0.0,
                'amountPaid' => 0.0,
                'submittedAt' => null,
                'paymentReference' => null,
                'actions' => ['showUrl' => null],
                'accountability' => null,
                'quickActions' => [],
                'record' => null,
            ];
        }

        $baseRow = $this->serializeRenewalRow($latestRenewal, true);

        return array_merge($baseRow, [
            'id' => $farmer->id,
            'recordKey' => $baseRow['recordKey'],
            'years' => $years->all(),
            'yearRangeLabel' => $years->implode(', '),
            'renewalYearsCount' => $years->count(),
            'sourceLabel' => $renewals
                ->pluck('source')
                ->merge($coverage->map(fn (MembershipLedger $ledger) => $ledger->membershipTransaction->source))
                ->filter()
                ->map(fn (string $source): string => strtoupper(str_replace('_', ' ', $source)))
                ->unique()
                ->values()
                ->implode(', '),
            'amountPaid' => round($coverageAmount + (float) $renewals->sum(fn (RenewalRequest $renewal): float => (float) ($renewal->getAttribute('queue_amount_paid') ?? 0)), 2),
            'paymentReference' => $baseRow['paymentReference'],
            'actions' => [
                'showUrl' => route('admin.renewals.show', $latestRenewal),
            ],
            'accountability' => $this->accountability($latestRenewal),
            'record' => [
                'submittedAt' => $baseRow['submittedAt'],
                'paymentReference' => $baseRow['paymentReference'],
                'renewalCount' => $renewals->count() + $coverage->count(),
                'renewalYearsCount' => $years->count(),
            ],
        ]);
    }

    private function renewalStatusLabel(RenewalRequest $renewal): string
    {
        return match ($renewal->status?->value) {
            RenewalStatus::SUBMITTED->value,
            RenewalStatus::UNDER_REVIEW->value => 'Pending',
            default => $renewal->status?->label() ?? 'Pending',
        };
    }

    private function serializeEligibleFarmerRow(Farmer $farmer, int $year, ?string $remindedAt = null): array
    {
        $feePreview = $farmer->memberType?->code
            ? $this->feeCalculatorService->calculateRenewal([
                'member_type' => $farmer->memberType->code,
            ])
            : null;

        return [
            'id' => $farmer->id,
            'farmer' => [
                'fullName' => $farmer->full_name ?: 'Unknown Farmer',
                'farmerCode' => $farmer->farmer_code ?: 'No code',
                'memberType' => $farmer->memberType ? [
                    'code' => $farmer->memberType->code,
                    'name' => $farmer->memberType->name,
                ] : null,
            ],
            'year' => $year,
            'status' => [
                'value' => 'pending',
                'label' => 'Needs Renewal',
            ],
            'amountToPay' => (float) ($feePreview['total'] ?? 0),
            'reminder' => [
                'sentAt' => $remindedAt,
                'hasSent' => $remindedAt !== null,
                'emailAvailable' => $farmer->users->contains(fn (User $account): bool => filled($account->email)),
            ],
            'actions' => [
                'createUrl' => route('admin.renewals.create', ['farmer_id' => $farmer->id, 'year' => $year]),
                'sendReminderEmailUrl' => route('admin.renewals.reminders.email', $farmer),
            ],
        ];
    }

    private function accountability(RenewalRequest $renewal): array
    {
        $reviewerName = $renewal->reviewer?->name;
        $latestActivity = AuditLog::query()
            ->with('actor:id,name')
            ->where('subject_type', $renewal->getMorphClass())
            ->where('subject_id', $renewal->getKey())
            ->whereNotNull('actor_user_id')
            ->latest('created_at')
            ->first(['actor_user_id', 'actor_name', 'created_at']);

        return [
            'lastUpdatedBy' => $latestActivity?->actor?->name ?? $latestActivity?->actor_name ?? $reviewerName,
            'lastUpdatedAt' => optional($latestActivity?->created_at ?? $renewal->updated_at)->format('M d, Y h:i A'),
            'assignedStaff' => $reviewerName,
            'reviewedBy' => $reviewerName,
        ];
    }

    private function renewalReminderMap(iterable $farmers, int $targetYear): array
    {
        $farmerIds = collect($farmers)->pluck('id')->map(fn ($id): int => (int) $id)->values()->all();

        if ($farmerIds === []) {
            return [];
        }

        return DB::table('notification_recipients as recipients')
            ->join('notifications', 'notifications.id', '=', 'recipients.notification_id')
            ->where('notifications.type', \App\Enums\NotificationType::RENEWAL_REMINDER->value)
            ->where('notifications.channel', 'email')
            ->where('notifications.payload->target_year', $targetYear)
            ->whereIn('recipients.farmer_id', $farmerIds)
            ->where('recipients.status', 'delivered')
            ->orderByDesc('notifications.created_at')
            ->get([
                'recipients.farmer_id',
                'recipients.delivered_at',
            ])
            ->unique('farmer_id')
            ->mapWithKeys(fn (object $row): array => [
                (int) $row->farmer_id => optional(\Carbon\Carbon::parse($row->delivered_at))->format('M d, Y h:i A'),
            ])
            ->all();
    }
}
