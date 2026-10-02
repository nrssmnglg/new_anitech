<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\AssessmentStatus;
use App\Enums\DocumentVerificationStatus;
use App\Enums\FarmerStatus;
use App\Enums\MemberTypeCode;
use App\Enums\MembershipApplicationRejectionReason;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AttachFarmerDocumentScanRequest;
use App\Http\Requests\Admin\ReviewMembershipApplicationRequest;
use App\Http\Requests\Admin\StoreMembershipApplicationRequest;
use App\Http\Requests\Admin\StorePaymentRequest;
use App\Http\Requests\Admin\VerifyFarmerDocumentRequest;
use App\Models\Association;
use App\Models\AuditLog;
use App\Models\Barangay;
use App\Models\FarmerDocument;
use App\Models\FeeSchedule;
use App\Models\MemberType;
use App\Models\MembershipApplication;
use App\Models\PayMongoWebhookEvent;
use App\Models\User;
use App\Services\Analytics\AnalyticsService;
use App\Services\Documents\DocumentRequirementService;
use App\Services\Documents\FarmerDocumentService;
use App\Services\Farmers\FarmerRegistryService;
use App\Services\Farmers\FarmerStatusWorkflowService;
use App\Services\Membership\FeeCalculatorService;
use App\Services\Membership\MemberTypeResolverService;
use App\Services\Membership\MembershipApplicationService;
use App\Services\Payments\PaymentAssessmentService;
use App\Services\Payments\PayMongoPaidAt;
use App\Services\Routing\PublicRouteKeyService;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MembershipApplicationController extends Controller
{
    public function __construct(
        private readonly MembershipApplicationService $membershipApplicationService,
        private readonly FarmerDocumentService $farmerDocumentService,
        private readonly FarmerRegistryService $farmerRegistryService,
        private readonly FarmerStatusWorkflowService $farmerStatusWorkflowService,
        private readonly MemberTypeResolverService $memberTypeResolverService,
        private readonly DocumentRequirementService $documentRequirementService,
        private readonly FeeCalculatorService $feeCalculatorService,
        private readonly PaymentAssessmentService $paymentAssessmentService,
        private readonly AnalyticsService $analyticsService,
    ) {
    }

    public function index(Request $request): InertiaResponse
    {
        $requestedStatus = $request->query->has('status') ? $request->query('status') : 'pending';

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'source' => $request->query('source'),
            'status' => $requestedStatus === 'submitted' ? 'pending' : $requestedStatus,
            'year' => $request->integer('year') ?: null,
            'barangay_id' => $this->decodeQueryRouteKey((string) $request->query('barangay_id', '')),
        ];
        $summaryFilters = $filters;
        $summaryFilters['status'] = null;

        $applicationsQuery = $this->requestQueueQuery($filters);
        $summaryQuery = $this->requestQueueQuery($summaryFilters, includeArchivedRejected: true);

            $applications = (clone $applicationsQuery)
            ->with([
                'farmer:id,farmer_code,membership_status,barangay_id,association_id,member_type_id',
                'farmer.profile:farmer_id,first_name,middle_name,last_name,suffix',
                'farmer.barangay:id,name',
                'farmer.association:id,name',
                'farmer.memberType:id,code,name',
                'documents:id,membership_transaction_id,verification_status',
                'paymentAssessments:id,membership_transaction_id,status,total_amount_due',
                'reviewer:id,name',
            ])
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $applications->through(function (MembershipApplication $application): array {
            $automaticallyClosed = $application->rejection_reason === MembershipApplicationRejectionReason::UNABLE_TO_FOLLOW_UP->value;
            $verifiedCount = $application->documents
                ->filter(fn (FarmerDocument $document) => $document->verification_status === DocumentVerificationStatus::VERIFIED)
                ->count();
            $documentCount = $application->documents->count();
            $latestAssessment = $application->paymentAssessments->sortByDesc('id')->first();
            $paymentSettled = in_array($latestAssessment?->status, [
                AssessmentStatus::PAID,
                AssessmentStatus::OVERPAID,
                AssessmentStatus::WAIVED,
            ], true);

            return [
                'id' => $application->id,
                'applicationNo' => $application->application_no,
                'recordKey' => (string) $application->getRouteKey(),
                'submittedAt' => optional($application->submitted_at)->format('M d, Y h:i A'),
                'createdAt' => optional($application->created_at)->format('M d, Y h:i A'),
                'source' => $application->source,
                'sourceLabel' => strtoupper(str_replace('_', '-', (string) $application->source)),
                'status' => [
                    'value' => $application->status?->value ?? 'submitted',
                    'label' => in_array($application->status?->value, [
                        ApplicationStatus::SUBMITTED->value,
                        ApplicationStatus::UNDER_REVIEW->value,
                    ], true)
                        ? 'Pending'
                        : ($application->status?->label() ?? 'Pending'),
                ],
                'farmer' => [
                    'fullName' => $application->farmer?->full_name ?? 'Unknown farmer',
                    'farmerCode' => $application->farmer?->farmer_code ?? 'No code',
                    'barangay' => $application->farmer?->barangay?->name,
                    'association' => $application->farmer?->association?->name,
                    'memberType' => $application->farmer?->memberType ? [
                        'code' => $application->farmer->memberType->code,
                        'name' => $application->farmer->memberType->name,
                    ] : null,
                    'membershipStatusLabel' => $application->farmer?->membership_status?->label(),
                ],
                'documents' => [
                    'verifiedCount' => $verifiedCount,
                    'totalCount' => $documentCount,
                    'completionLabel' => $documentCount > 0
                        ? sprintf('%d / %d verified', $verifiedCount, $documentCount)
                        : 'No checklist yet',
                ],
                'payment' => [
                    'isSettled' => $paymentSettled,
                    'statusLabel' => $paymentSettled ? 'Payment recorded' : 'Awaiting payment',
                    'amountDue' => (float) ($latestAssessment?->total_amount_due ?? 0),
                ],
                'actions' => [
                    'showUrl' => route('admin.membership-applications.show', $application),
                ],
                'accountability' => $this->accountability($application),
                'quickActions' => [
                    'canReview' => ! $automaticallyClosed,
                    'canMarkComplete' => ! $automaticallyClosed && $application->source !== 'walk_in',
                    'canRequestCorrection' => ! $automaticallyClosed,
                    'canForwardToAdmin' => ! $automaticallyClosed && ! (Auth::user()?->hasRole(User::ROLE_ADMIN) ?? false),
                ],
            ];
        });

        return Inertia::render('Admin/MembershipApplications/Index', [
            'applications' => $applications,
            'filters' => [
                'search' => (string) ($filters['search'] ?? ''),
                'source' => $filters['source'] ? (string) $filters['source'] : '',
                'status' => $filters['status'] ? (string) $filters['status'] : '',
                'year' => $filters['year'] ? (string) $filters['year'] : '',
                'barangay_id' => $request->filled('barangay_id') ? (string) $request->query('barangay_id') : '',
            ],
            'filterOptions' => [
                'sources' => [
                    ['value' => 'walk_in', 'label' => 'Walk-in'],
                    ['value' => 'mobile', 'label' => 'Mobile'],
                ],
                'statuses' => [
                    ['value' => 'pending', 'label' => 'Pending'],
                    ['value' => 'approved', 'label' => 'Approved'],
                    ['value' => 'rejected', 'label' => 'Rejected'],
                ],
                'years' => MembershipApplication::query()
                    ->selectRaw('COALESCE(year(submitted_at), year(created_at)) as queue_year')
                    ->distinct()
                    ->orderByDesc('queue_year')
                    ->pluck('queue_year')
                    ->filter()
                    ->map(fn ($year) => ['value' => (string) $year, 'label' => (string) $year])
                    ->values()
                    ->all(),
                'barangays' => Barangay::query()
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn (Barangay $barangay) => [
                        'id' => $this->queryRouteKey($barangay->id),
                        'name' => $barangay->name,
                    ])
                    ->all(),
            ],
            'pageTitle' => ($filters['source'] ?? null) === 'mobile' ? 'Membership Application Requests' : 'Membership Applications',
            'pageSubtitle' => ($filters['source'] ?? null) === 'mobile'
                ? 'Review queued mobile submissions using the revised membership transaction schema.'
                : 'Handle walk-in applications, checklist completion, payment readiness, and approval from one queue.',
            'createUrl' => route('admin.membership-applications.create'),
            'resetUrl' => route('admin.membership-applications.index'),
            'quickActionUrl' => route('admin.tasks.quick-action'),
            'summary' => [
                'total' => (clone $summaryQuery)->count(),
                'pending' => (clone $summaryQuery)->whereIn('status', $this->databaseStatusesForPendingQueue())->count(),
                'approved' => (clone $summaryQuery)->where('status', $this->databaseStatusValue(ApplicationStatus::APPROVED))->count(),
                'rejected' => (clone $summaryQuery)->where('status', $this->databaseStatusValue(ApplicationStatus::REJECTED))->count(),
            ],
        ]);
    }

    public function create(Request $request): InertiaResponse
    {
        $reapplyApplication = null;
        $entryMode = $request->string('mode')->toString() === 'old-record' ? 'old-record' : 'application';

        if ($request->filled('reapply_from_application')) {
            $reapplyReference = (string) $request->string('reapply_from_application');

            $reapplyApplication = MembershipApplication::query()
                ->with(['farmer.profile', 'farmer.barangay:id,name', 'farmer.association:id,name', 'farmer.memberType:id,code,name'])
                ->where(function ($query) use ($reapplyReference): void {
                    $query->where('application_no', $reapplyReference);

                    $decodedId = null;
                    try {
                        $decodedId = (new MembershipApplication())->decodePublicRouteKey($reapplyReference);
                    } catch (\Throwable) {
                    }

                    if ($decodedId) {
                        $query->orWhere('id', (int) $decodedId);
                    } elseif (is_numeric($reapplyReference)) {
                        $query->orWhere('id', (int) $reapplyReference);
                    }
                })
                ->whereIn('status', [ApplicationStatus::REJECTED->value, 'Rejected'])
                ->first();

            if ($reapplyApplication === null) {
                throw new NotFoundHttpException('The requested membership application could not be found.');
            }
        }

        $prefillFarmer = $reapplyApplication?->farmer;
        $prefillProfile = $prefillFarmer?->profile;
        $canSelectManualStatus = $this->canSelectManualStatus();
        $nextFarmerCode = $this->farmerRegistryService->previewFarmerCode();
        $memberTypes = collect(MemberTypeCode::cases())
            ->map(function (MemberTypeCode $type): array {
                $memberType = MemberType::query()->firstOrCreate(
                    ['code' => $type->value],
                    [
                        'name' => $type->label(),
                        'requires_membership_fee' => $type->isNewMember(),
                        'mortuary_eligible' => ! $type->isSenior(),
                        'status' => 'Active',
                    ],
                );

                return [
                    'id' => $this->queryRouteKey($memberType->id),
                    'code' => $memberType->code,
                    'name' => $memberType->name,
                ];
            })
            ->values()
            ->all();

        return Inertia::render('Admin/MembershipApplications/Create', [
            'entryMode' => $entryMode,
            'pageSubtitle' => $reapplyApplication ? 'Create a replacement membership application.' : null,
            'statuses' => FarmerStatus::options(),
            'canSelectManualStatus' => $canSelectManualStatus,
            'barangays' => Barangay::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Barangay $barangay): array => [
                    'id' => $this->queryRouteKey($barangay->id),
                    'name' => $barangay->name,
                ])
                ->values()
                ->all(),
            'associations' => Association::query()
                ->orderBy('name')
                ->get(['id', 'barangay_id', 'name'])
                ->map(fn (Association $association): array => [
                    'id' => $this->queryRouteKey($association->id),
                    'barangay_id' => $this->queryRouteKey($association->barangay_id),
                    'name' => $association->name,
                ])
                ->values()
                ->all(),
            'nextFarmerCode' => $nextFarmerCode,
            'requiredDocuments' => collect($this->documentRequirementService->requiredFor('application', ['source' => 'walk_in']))
                ->map(fn ($document) => [
                    'value' => $document->value,
                    'label' => $document->label(),
                ])->values()->all(),
            'reapplyApplication' => $reapplyApplication ? [
                'id' => $reapplyApplication->id,
                'applicationNo' => $reapplyApplication->application_no,
                'rejectionReasonLabel' => $reapplyApplication->rejection_reason_label,
                'rejectionDetails' => $reapplyApplication->effective_rejection_details,
                'farmer' => $prefillFarmer ? [
                    'fullName' => $prefillFarmer->full_name,
                ] : null,
            ] : null,
            'memberTypes' => $memberTypes,
            'memberTypePreview' => [
                'default_code' => MemberTypeCode::NM->value,
                'default_label' => MemberTypeCode::NM->label(),
                'senior_code' => MemberTypeCode::NSC->value,
                'senior_label' => MemberTypeCode::NSC->label(),
                'senior_age' => 60,
            ],
            'formDefaults' => [
                'source' => 'walk_in',
                'reapply_from_application_id' => old('reapply_from_application_id', $reapplyApplication?->id),
                'registered_at' => old('registered_at', now()->format('Y-m-d')),
                'status' => $canSelectManualStatus ? old('status', FarmerStatus::PENDING->value) : FarmerStatus::PENDING->value,
                'application_remarks' => old('application_remarks', ''),
                'first_name' => old('first_name', $prefillProfile?->first_name ?? $prefillFarmer?->first_name),
                'middle_name' => old('middle_name', $prefillProfile?->middle_name ?? $prefillFarmer?->middle_name),
                'last_name' => old('last_name', $prefillProfile?->last_name ?? $prefillFarmer?->last_name),
                'suffix' => old('suffix', $prefillProfile?->suffix ?? $prefillFarmer?->suffix),
                'birth_date' => old('birth_date', $prefillProfile?->birth_date?->toDateString() ?? optional($prefillFarmer?->birth_date)->toDateString()),
                'sex' => old('sex', $prefillProfile?->sex ? strtolower((string) $prefillProfile->sex) : ($prefillFarmer?->sex ? strtolower((string) $prefillFarmer->sex) : null)),
                'civil_status' => old('civil_status', $prefillProfile?->civil_status ? strtolower((string) $prefillProfile->civil_status) : ($prefillFarmer?->civil_status ? strtolower((string) $prefillFarmer->civil_status) : null)),
                'mobile_number' => old('mobile_number', $prefillProfile?->mobile_number ?? $prefillFarmer?->mobile_number),
                'member_type_id' => $this->queryRouteKey(old('member_type_id', $prefillFarmer?->member_type_id ?? '')),
                'barangay_id' => $this->queryRouteKey(old('barangay_id', $prefillFarmer?->barangay_id ?? '')),
                'association_id' => $this->queryRouteKey(old('association_id', $prefillFarmer?->association_id ?? '')),
                'address' => old('address', $prefillProfile?->address ?? $prefillFarmer?->address),
                'remarks' => old('remarks', $prefillFarmer?->remarks),
                'documents' => collect($this->documentRequirementService->requiredFor('application', ['source' => 'walk_in']))
                    ->mapWithKeys(fn ($document) => [
                        $document->value => [
                            'is_received' => (bool) old('documents.' . $document->value . '.is_received', false),
                        ],
                    ])->all(),
            ],
            'storeUrl' => route('admin.membership-applications.store'),
            'indexUrl' => route('admin.membership-applications.index'),
        ]);
    }

    public function store(StoreMembershipApplicationRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if (! $this->canSelectManualStatus()) {
            $validated['status'] = FarmerStatus::PENDING->value;
        }

        $reapplyApplication = $request->filled('reapply_from_application_id')
            ? MembershipApplication::query()
                ->with('farmer')
                ->whereKey($request->integer('reapply_from_application_id'))
                ->whereIn('status', [ApplicationStatus::REJECTED->value, 'Rejected'])
                ->first()
            : null;
        $duplicates = $this->farmerRegistryService->findPotentialDuplicates($validated, $reapplyApplication?->farmer);

        if ($duplicates->isNotEmpty()) {
            return back()
                ->withInput()
                ->withErrors(['duplicate_check' => 'A matching farmer record already exists. Please check Farmer Management first before creating a new membership.']);
        }

        $application = DB::transaction(function () use ($validated, $reapplyApplication): MembershipApplication {
            $memberType = MemberType::query()->findOrFail((int) $validated['member_type_id']);

            $farmerPayload = [
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name' => $validated['last_name'],
                'suffix' => $validated['suffix'] ?? null,
                'sex' => $validated['sex'] ?? null,
                'birth_date' => $validated['birth_date'] ?? null,
                'civil_status' => $validated['civil_status'] ?? null,
                'mobile_number' => $validated['mobile_number'] ?? null,
                'address' => $validated['address'] ?? null,
                'barangay_id' => $validated['barangay_id'],
                'association_id' => $validated['association_id'] ?? null,
                'member_type_id' => $memberType->id,
                'record_origin' => 'application',
                'status' => $validated['status'],
                'registered_at' => $validated['registered_at'] ?? null,
                'email' => $validated['email'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
            ];

            $farmer = $reapplyApplication?->farmer
                ? $this->farmerRegistryService->update($reapplyApplication->farmer, $farmerPayload)
                : $this->farmerRegistryService->create($farmerPayload);

            $application = $this->membershipApplicationService->create([
                'farmer_id' => $farmer->id,
                'source' => 'walk_in',
                'remarks' => $validated['application_remarks'] ?? null,
            ]);

            $application = $this->membershipApplicationService->initializeChecklist($application, Auth::id());

            foreach (($validated['documents'] ?? []) as $documentType => $documentData) {
                if (! ($documentData['is_received'] ?? false)) {
                    continue;
                }

                $document = $application->documents->firstWhere('document_type.value', $documentType)
                    ?? $application->documents->firstWhere('document_type', $documentType);

                if ($document) {
                    $this->membershipApplicationService->markDocumentReceived($application, $document->id, true, Auth::id());
                }
            }

            if (($validated['status'] ?? null) === FarmerStatus::ACTIVE->value) {
                foreach ($application->documents as $document) {
                    $this->membershipApplicationService->markDocumentReceived($application, $document->id, true, Auth::id());
                }
                $application = $application->fresh(['farmer', 'documents']);
                $feeSchedule = FeeSchedule::query()->where('is_active', true)->first();
                $totalDue = $feeSchedule
                    ? ((float) $feeSchedule->membership_fee + (float) $feeSchedule->annual_due + (float) $feeSchedule->mortuary_fee)
                    : 350.0;

                $this->membershipApplicationService->recordPayment($application, [
                    'payment_method' => 'cash',
                    'amount_paid' => $totalDue,
                    'paid_at' => now()->toDateTimeString(),
                    'reference_no' => 'INITIAL-ACTIVE',
                ], Auth::id());
            }

            return $application->fresh(['farmer', 'documents']);
        });

        $this->analyticsService->track('membership_application_created', [
            'module' => 'membership_applications',
            'properties' => [
                'application_id' => $application->id,
                'source' => $application->source,
                'is_reapplication' => $reapplyApplication !== null,
            ],
        ]);

        return redirect()
            ->route('admin.membership-applications.show', $application)
            ->with('success', $reapplyApplication
                ? 'Replacement membership application saved. Continue with document review and payment.'
                : 'Walk-in membership application saved. Continue with document review and payment.');
    }

    public function show(MembershipApplication $membershipApplication): InertiaResponse
    {
        $membershipApplication->load([
            'farmer.profile',
            'farmer.barangay:id,name',
            'farmer.association:id,name',
            'farmer.memberType:id,code,name',
            'paymentAssessments.feeSchedule:id,year',
            'paymentAssessments.payments.paymentMethod:id,code,name',
            'documents.verifier:id,name',
            'paymentAssessments.payments.verifier:id,name',
            'reviewer:id,name',
            'internalNotes.creator:id,name',
        ]);

        if ($membershipApplication->source !== 'legacy'
            && ($membershipApplication->source !== 'walk_in' || $membershipApplication->documents->isNotEmpty())) {
            $this->farmerDocumentService->ensureApplicationChecklist($membershipApplication);
            $membershipApplication->load('documents.verifier:id,name');
        }

        $documents = $membershipApplication->documents->map(function (FarmerDocument $document): FarmerDocument {
            $document->setAttribute('ready_for_verification', $this->farmerDocumentService->readyForVerification($document));
            $document->setAttribute('upload_present', $this->farmerDocumentService->uploadPresent($document));
            $document->setAttribute('is_expired', $this->farmerDocumentService->isExpired($document));
            $document->setAttribute('expires_at', optional($this->farmerDocumentService->expiresAt($document))?->format('M d, Y'));
            $document->setAttribute('needs_resubmission', $this->farmerDocumentService->requiresResubmission($document));
            $document->setAttribute('validation_notes', $this->farmerDocumentService->validationNotes($document));

            return $document;
        });
        $checklistInitialized = $membershipApplication->source !== 'walk_in' || $documents->isNotEmpty();
        $verifierAttribution = app(\App\Services\Documents\DocumentVerifierAttributionService::class)->forTransaction($membershipApplication);

        $assessment = $membershipApplication->paymentAssessments->sortByDesc('id')->first();
        $paymentPreview = null;

        if ($assessment) {
            $paymentPreview = [
                'membership_fee' => (float) $assessment->membership_fee,
                'annual_due' => (float) $assessment->annual_due,
                'mortuary_fee' => (float) $assessment->mortuary_fee,
                'total' => (float) $assessment->total_amount_due,
            ];
        } elseif ($membershipApplication->farmer?->memberType?->code) {
            // Use the same active fee schedule that will be used when the
            // assessment is created, rather than the calculator's fallback fees.
            $feeSchedule = $this->paymentAssessmentService->resolveFeeSchedule(
                $membershipApplication->farmer->member_type_id,
            );
            $paymentPreview = $this->feeCalculatorService->calculateApplication([
                'member_type' => $membershipApplication->farmer->memberType->code,
                'fee_schedule' => $feeSchedule,
            ]);
        }
        $requiredDocuments = $checklistInitialized
            ? $documents->where('is_required', true)->values()
            : collect();
        $verifiedRequiredCount = $requiredDocuments->filter(fn (FarmerDocument $document) => $document->verification_status === DocumentVerificationStatus::VERIFIED)->count();
        $documentsComplete = $checklistInitialized
            && ($requiredDocuments->isEmpty() || $verifiedRequiredCount === $requiredDocuments->count());
        $missingDocumentCount = $requiredDocuments->filter(
            fn (FarmerDocument $document) => ! (bool) $document->getAttribute('ready_for_verification')
        )->count();
        $expiredDocumentCount = $requiredDocuments->filter(fn (FarmerDocument $document) => (bool) $document->getAttribute('is_expired'))->count();
        $resubmissionCount = $requiredDocuments->filter(fn (FarmerDocument $document) => (bool) $document->getAttribute('needs_resubmission'))->count();
        $payments = $assessment?->payments?->sortByDesc('paid_at') ?? collect();
        $paymentEvents = PayMongoWebhookEvent::query()
            ->whereIn('payment_id', $payments->pluck('id'))
            ->where('status', 'processed')
            ->orderBy('id')
            ->get()
            ->keyBy('payment_id');
        $settledStatuses = [AssessmentStatus::PAID, AssessmentStatus::OVERPAID, AssessmentStatus::WAIVED];
        $paymentSettled = in_array($assessment?->status, $settledStatuses, true);
        $paymentRecorded = $payments->isNotEmpty();
        $automaticallyClosed = $membershipApplication->rejection_reason === MembershipApplicationRejectionReason::UNABLE_TO_FOLLOW_UP->value;
        $paymentReady = $checklistInitialized
            && $documentsComplete
            && $membershipApplication->status !== ApplicationStatus::REJECTED
            && ! $paymentSettled;
        $profile = $membershipApplication->farmer?->profile;
        $duplicateRisk = $membershipApplication->farmer
            ? $this->farmerRegistryService->findPotentialDuplicates([
                'first_name' => $profile?->first_name,
                'last_name' => $profile?->last_name,
                'birth_date' => optional($profile?->birth_date)?->toDateString(),
                'mobile_number' => $profile?->mobile_number,
                'barangay_id' => $membershipApplication->farmer?->barangay_id,
            ], $membershipApplication->farmer, 1)->isNotEmpty()
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
                'message' => 'This applicant matches an existing farmer record pattern. Review the farmer registry before approval.',
            ]);
        }

        if (! $paymentSettled && (float) ($assessment?->total_amount_due ?? ($paymentPreview['total'] ?? 0)) > 0) {
            $recordWarnings->push([
                'key' => 'unpaid_assessment',
                'type' => 'danger',
                'label' => 'Unpaid Assessment',
                'message' => 'Assessment payment is still pending. Approval should not be finalized until payment is recorded.',
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

        return Inertia::render('Admin/MembershipApplications/Show', [
            'application' => [
                'id' => $membershipApplication->id,
                'applicationNo' => $membershipApplication->application_no,
                'source' => $membershipApplication->source,
                'sourceLabel' => strtoupper(str_replace('_', '-', (string) $membershipApplication->source)),
                'status' => [
                    'value' => $membershipApplication->status?->value,
                    'label' => $membershipApplication->status?->label() ?? 'Pending',
                ],
                'submittedAt' => optional($membershipApplication->submitted_at)->format('F d, Y h:i A'),
                'createdAt' => optional($membershipApplication->created_at)->format('F d, Y h:i A'),
                'reviewedAt' => optional($membershipApplication->reviewed_at)->format('F d, Y h:i A'),
                'reviewerName' => $membershipApplication->reviewer?->name,
                'rejectionReasonLabel' => $membershipApplication->rejection_reason_label,
                'rejectionDetails' => $membershipApplication->effective_rejection_details,
                'accountability' => $this->accountability($membershipApplication),
            ],
            'farmer' => [
                'id' => $membershipApplication->farmer?->id,
                'showUrl' => $membershipApplication->farmer ? route('admin.farmers.show', $membershipApplication->farmer) : null,
                'fullName' => $membershipApplication->farmer?->full_name,
                'farmerCode' => $membershipApplication->farmer?->farmer_code,
                'memberType' => $membershipApplication->farmer?->memberType ? [
                    'code' => $membershipApplication->farmer->memberType->code,
                    'name' => $membershipApplication->farmer->memberType->name,
                ] : null,
                'membershipStatusLabel' => $membershipApplication->farmer?->membership_status?->label(),
                'barangay' => $membershipApplication->farmer?->barangay?->name,
                'association' => $membershipApplication->farmer?->association?->name,
                'recordOrigin' => $membershipApplication->farmer?->record_origin,
                'birthDate' => optional($profile?->birth_date)->format('F d, Y'),
                'sex' => $profile?->sex ? ucfirst(strtolower((string) $profile->sex)) : null,
                'civilStatus' => $profile?->civil_status ? ucfirst(strtolower((string) $profile->civil_status)) : null,
                'mobileNumber' => $profile?->mobile_number,
                'address' => $profile?->address,
            ],
            'flow' => [
                'isHistorical' => $membershipApplication->source === 'legacy',
                'isWalkIn' => $membershipApplication->source === 'walk_in',
                'isMobile' => $membershipApplication->source === 'mobile',
                'checklistInitialized' => $checklistInitialized,
                'canInitializeChecklist' => ! $automaticallyClosed && $membershipApplication->source === 'walk_in' && ! $checklistInitialized,
                'documentsComplete' => $documentsComplete,
                'requiredCount' => $requiredDocuments->count(),
                'verifiedCount' => $verifiedRequiredCount,
                'missingCount' => $missingDocumentCount,
                'expiredCount' => $expiredDocumentCount,
                'resubmissionCount' => $resubmissionCount,
                'paymentReady' => $paymentReady,
                'paymentSettled' => $paymentSettled,
                'paymentRecorded' => $paymentRecorded,
                'canRecordPaymentInAdmin' => $membershipApplication->source === 'walk_in' && $paymentReady && ! $paymentSettled,
                'step' => match (true) {
                    $paymentSettled => 4,
                    $paymentReady || $paymentRecorded => 3,
                    $documentsComplete => 3,
                    default => 2,
                },
            ],
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
                'verifierName' => $document->verifier?->name ?? $verifierAttribution->get($document->id)['name'] ?? null,
                'verifiedAt' => optional($document->verified_at ?? $verifierAttribution->get($document->id)['verified_at'] ?? null)->format('M d, Y h:i A'),
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
                    'reviewUrl' => $automaticallyClosed ? null : route('admin.membership-applications.documents.review', [$membershipApplication, $document]),
                    'attachScanUrl' => $automaticallyClosed ? null : route('admin.membership-applications.documents.attach-scan', [$membershipApplication, $document]),
                    'viewUrl' => $document->getAttribute('upload_present')
                        ? route('admin.membership-applications.documents.view', [$membershipApplication, $document])
                        : null,
                ],
            ])->values()->all(),
            'assessment' => [
                'id' => $assessment?->id,
                'status' => [
                    'value' => $assessment?->status?->value,
                    'label' => $assessment?->status?->label() ?? 'Pending',
                ],
                'totalAmountDue' => (float) ($assessment?->total_amount_due ?? ($paymentPreview['total'] ?? 0)),
                'membershipFee' => (float) ($assessment?->membership_fee ?? ($paymentPreview['membership_fee'] ?? 0)),
                'annualDue' => (float) ($assessment?->annual_due ?? ($paymentPreview['annual_due'] ?? 0)),
                'mortuaryFee' => (float) ($assessment?->mortuary_fee ?? ($paymentPreview['mortuary_fee'] ?? 0)),
                'feeScheduleYear' => $assessment?->feeSchedule?->year,
            ],
            'payments' => $payments->map(fn ($payment): array => [
                'id' => $payment->id,
                'paidAt' => optional(
                    ($paymentEvents->get($payment->id)?->payload
                        ? app(PayMongoPaidAt::class)->fromPayload($paymentEvents->get($payment->id)->payload)
                        : null) ?? $payment->paid_at
                )->format('M d, Y h:i A'),
                'method' => strtoupper((string) ($payment->payment_method ?? $payment->paymentMethod?->code ?? '')),
                'referenceNo' => $payment->reference_no,
                'statusLabel' => $payment->status?->label() ?? 'Verified',
                'amountPaid' => (float) $payment->amount_paid,
            ])->values()->all(),
            'recordWarnings' => $recordWarnings->values()->all(),
            'internalNotes' => $membershipApplication->internalNotes
                ->map(fn ($note): array => [
                    'id' => $note->id,
                    'body' => $note->body,
                    'createdBy' => $note->creator?->name ?? 'Staff',
                    'createdAt' => optional($note->created_at)->format('M d, Y h:i A'),
                ])
                ->values()
                ->all(),
            'permissions' => [
                'canApproveDecision' => ! $automaticallyClosed && ((Auth::user()?->hasRole(User::ROLE_ADMIN) ?? false) || (Auth::user()?->hasRole(User::ROLE_STAFF) ?? false)),
                'canRejectDecision' => ! $automaticallyClosed && (Auth::user()?->hasRole(User::ROLE_ADMIN) ?? false),
            ],
            'features' => [
                'walkInAttachScanEnabled' => false,
            ],
            'rejectionReasonOptions' => collect(MembershipApplicationRejectionReason::options())
                ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
                ->values()
                ->all(),
            'urls' => [
                'index' => route('admin.membership-applications.index'),
                'reapply' => $membershipApplication->status === ApplicationStatus::REJECTED
                    ? route('admin.membership-applications.create', ['reapply_from_application' => $membershipApplication->application_no])
                    : null,
                'review' => route('admin.membership-applications.review', $membershipApplication),
                'initializeChecklist' => route('admin.membership-applications.initialize-checklist', $membershipApplication),
                'recordPayment' => route('admin.membership-applications.payment.store', $membershipApplication),
                'storeInternalNote' => route('admin.membership-applications.internal-notes.store', $membershipApplication),
            ],
        ]);
    }

    public function initializeChecklist(MembershipApplication $membershipApplication): RedirectResponse
    {
        $this->membershipApplicationService->initializeChecklist($membershipApplication, Auth::id());

        return redirect()
            ->route('admin.membership-applications.show', $membershipApplication)
            ->with('success', 'Document checklist started. Continue with intake confirmation and review.');
    }

    public function review(ReviewMembershipApplicationRequest $request, MembershipApplication $membershipApplication): RedirectResponse
    {
        try {
            if ($request->validated('action') === 'approve') {
                $this->membershipApplicationService->approve($membershipApplication, Auth::id());
                $message = 'Application approved. Payment may now be recorded.';
                $this->analyticsService->track('membership_application_approved', [
                    'module' => 'membership_applications',
                    'properties' => [
                        'application_id' => $membershipApplication->id,
                        'source' => $membershipApplication->source,
                    ],
                ]);
            } else {
                $this->membershipApplicationService->reject(
                    $membershipApplication,
                    (string) $request->validated('rejection_reason'),
                    $request->validated('rejection_details') ?? $request->validated('remarks'),
                    Auth::id(),
                );
                $message = 'Application rejected and membership status reset to pending application.';
                $this->analyticsService->track('membership_application_rejected', [
                    'module' => 'membership_applications',
                    'properties' => [
                        'application_id' => $membershipApplication->id,
                        'source' => $membershipApplication->source,
                    ],
                ]);
            }
        } catch (DomainException $exception) {
            throw ValidationException::withMessages([
                'application' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('admin.membership-applications.show', $membershipApplication)
            ->with('success', $message);
    }

    public function reviewDocument(
        VerifyFarmerDocumentRequest $request,
        MembershipApplication $membershipApplication,
        FarmerDocument $document,
    ): RedirectResponse {
        if ((int) $document->membership_transaction_id !== (int) $membershipApplication->id) {
            return $this->documentMismatchRedirect($membershipApplication, $document);
        }

        try {
            $action = $request->validated('action');
            $remarks = $request->validated('remarks');

            match ($action) {
                'receive' => $this->membershipApplicationService->markDocumentReceived($membershipApplication, $document->id, true, Auth::id()),
                'unreceive' => $this->membershipApplicationService->markDocumentReceived($membershipApplication, $document->id, false, Auth::id()),
                'verify' => $this->membershipApplicationService->verifyDocument($membershipApplication, $document->id, Auth::id(), $remarks),
                'reject' => $this->membershipApplicationService->rejectDocument($membershipApplication, $document->id, Auth::id(), $remarks),
            };
        } catch (DomainException $exception) {
            throw ValidationException::withMessages([
                'document' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('admin.membership-applications.show', $membershipApplication)
            ->with('success', 'Document checklist updated.');
    }

    public function attachDocumentScan(
        AttachFarmerDocumentScanRequest $request,
        MembershipApplication $membershipApplication,
        FarmerDocument $document,
    ): RedirectResponse {
        if ((int) $document->membership_transaction_id !== (int) $membershipApplication->id) {
            return $this->documentMismatchRedirect($membershipApplication, $document);
        }

        try {
            $this->membershipApplicationService->attachOfficeDocumentScan(
                $membershipApplication,
                $document->id,
                $request->file('document'),
            );
        } catch (DomainException $exception) {
            throw ValidationException::withMessages([
                'document' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('admin.membership-applications.show', $membershipApplication)
            ->with('success', 'Document scan attached successfully.');
    }

    public function showDocumentReview(
        MembershipApplication $membershipApplication,
        FarmerDocument $document,
    ): RedirectResponse {
        if ((int) $document->membership_transaction_id !== (int) $membershipApplication->id) {
            return $this->documentMismatchRedirect($membershipApplication, $document);
        }

        return redirect()->to(
            route('admin.membership-applications.show', $membershipApplication) . '#document-' . $document->id
        );
    }

    public function recordPayment(StorePaymentRequest $request, MembershipApplication $membershipApplication): RedirectResponse
    {
        if ($membershipApplication->source !== 'walk_in') {
            throw ValidationException::withMessages([
                'payment' => 'Mobile application payments are recorded from the Farmer PWA after approval.',
            ]);
        }

        try {
            $result = $this->membershipApplicationService->recordPayment($membershipApplication, $request->validated(), Auth::id());
        } catch (DomainException $exception) {
            throw ValidationException::withMessages([
                'payment' => $exception->getMessage(),
            ]);
        }

        $this->analyticsService->track('membership_application_payment_recorded', [
            'module' => 'membership_applications',
            'properties' => [
                'application_id' => $membershipApplication->id,
                'source' => $membershipApplication->source,
                'assessment_status' => $result['summary']['assessment_status']->value,
            ],
        ]);

        if (in_array($result['summary']['assessment_status'], [AssessmentStatus::PAID, AssessmentStatus::OVERPAID], true)) {
            return redirect()
                ->route('admin.membership-applications.show', $membershipApplication)
                ->with('success', 'Payment recorded and membership completed.');
        }

        return redirect()
            ->route('admin.membership-applications.show', $membershipApplication)
            ->with('success', 'Payment recorded. The application is still awaiting full payment before completion.');
    }

    public function viewDocument(MembershipApplication $membershipApplication, FarmerDocument $document): StreamedResponse
    {
        abort_unless($document->membership_transaction_id == $membershipApplication->id, 404);
        abort_unless($this->farmerDocumentService->uploadPresent($document), 404);

        $disk = $document->disk ?: 'public';
        $path = (string) $document->file_path;
        $mimeType = $this->previewMimeTypeForDocument($document);
        $filename = $document->original_name ?: basename((string) $document->file_path);

        return Storage::disk($disk)->response(
            $path,
            $filename,
            ['Content-Type' => $mimeType],
            'inline',
        );
    }

    private function previewMimeTypeForDocument(FarmerDocument $document): string
    {
        $mimeType = strtolower(trim((string) ($document->mime_type ?? '')));

        if ($mimeType !== '' && $mimeType !== 'application/octet-stream') {
            return $mimeType;
        }

        $disk = $document->disk ?: 'public';
        $path = (string) $document->file_path;
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

    private function requestQueueQuery(array $filters, bool $includeArchivedRejected = false): Builder
    {
        $queueStatuses = [
            ...$this->databaseStatusesForPendingQueue(),
            $this->databaseStatusValue(ApplicationStatus::APPROVED),
            $this->databaseStatusValue(ApplicationStatus::REJECTED),
        ];
        $settledAssessmentStatuses = [
            AssessmentStatus::PAID->value,
            AssessmentStatus::OVERPAID->value,
            AssessmentStatus::WAIVED->value,
        ];

        return MembershipApplication::query()
            ->where(fn (Builder $query) => $query->whereNull('source')->orWhere('source', '!=', 'legacy'))
            ->when(! $includeArchivedRejected && ! ($filters['status'] ?? null), function (Builder $query): void {
                $query->where(function (Builder $activeQueue): void {
                    $activeQueue->where('status', '!=', $this->databaseStatusValue(ApplicationStatus::REJECTED))
                        ->orWhereNull('rejection_reason')
                        ->orWhere('rejection_reason', '!=', MembershipApplicationRejectionReason::UNABLE_TO_FOLLOW_UP->value);
                });
            })
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery->where('application_no', 'like', "%{$search}%")
                        ->orWhereHas('farmer', function (Builder $farmerQuery) use ($search): void {
                            $farmerQuery->where('farmer_code', 'like', "%{$search}%")
                                ->orWhereHas('profile', function (Builder $profileQuery) use ($search): void {
                                    $profileQuery->where('first_name', 'like', "%{$search}%")
                                        ->orWhere('last_name', 'like', "%{$search}%")
                                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                                });
                        });
                });
            })
            ->when($filters['source'] ?? null, fn (Builder $query, string $sourceValue) => $query->where('source', $sourceValue))
            ->when($filters['status'] ?? null, function (Builder $query, string $status): void {
                if ($status === 'pending') {
                    $query->whereIn('status', $this->databaseStatusesForPendingQueue());

                    return;
                }

                $normalizedStatus = ApplicationStatus::tryFrom(strtolower($status));

                $query->where('status', $this->databaseStatusValue($normalizedStatus ?? ApplicationStatus::SUBMITTED));
            })
            ->when($filters['year'] ?? null, function (Builder $query, int $year): void {
                $query->where(function (Builder $yearQuery) use ($year): void {
                    $yearQuery->whereYear('submitted_at', $year)
                        ->orWhere(function (Builder $fallbackQuery) use ($year): void {
                            $fallbackQuery->whereNull('submitted_at')
                                ->whereYear('created_at', $year);
                        });
                });
            })
            ->when($filters['barangay_id'] ?? null, function (Builder $query, int $barangayId): void {
                $query->whereHas('farmer', fn (Builder $farmerQuery) => $farmerQuery->where('barangay_id', $barangayId));
            })
            ->whereIn('status', $queueStatuses)
            ->where(function (Builder $query) use ($settledAssessmentStatuses): void {
                $query->where('status', $this->databaseStatusValue(ApplicationStatus::APPROVED))
                    ->orWhereDoesntHave('paymentAssessments', function (Builder $assessmentQuery) use ($settledAssessmentStatuses): void {
                        $assessmentQuery->whereIn('status', $settledAssessmentStatuses);
                    });
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

    private function accountability(MembershipApplication $application): array
    {
        $latestActivity = AuditLog::query()
            ->with('actor:id,name')
            ->where('subject_type', $application->getMorphClass())
            ->where('subject_id', $application->getKey())
            ->latest('created_at')
            ->first(['actor_user_id', 'actor_name', 'created_at']);

        return [
            'lastUpdatedBy' => $latestActivity?->actor?->name ?? $latestActivity?->actor_name ?? $application->reviewer?->name,
            'lastUpdatedAt' => optional($latestActivity?->created_at ?? $application->updated_at)->format('M d, Y h:i A'),
            'assignedStaff' => $application->reviewer?->name,
            'reviewedBy' => $application->reviewer?->name,
        ];
    }

    private function documentsComplete(MembershipApplication $membershipApplication): bool
    {
        $requiredDocuments = $membershipApplication->documents->where('is_required', true);

        if ($requiredDocuments->isEmpty()) {
            return true;
        }

        return $requiredDocuments->every(fn (FarmerDocument $document) => $document->verification_status === DocumentVerificationStatus::VERIFIED);
    }

    private function documentMismatchRedirect(
        MembershipApplication $membershipApplication,
        FarmerDocument $document,
    ): RedirectResponse {
        return redirect()
            ->route('admin.membership-applications.show', $membershipApplication)
            ->withErrors([
                'document' => sprintf(
                    'Document #%d is not linked to membership application #%d. Linked application ID: %s.',
                    $document->id,
                    $membershipApplication->id,
                    $document->membership_transaction_id ?? 'none',
                ),
            ]);
    }

    private function canSelectManualStatus(): bool
    {
        return Auth::user()?->hasRole(User::ROLE_ADMIN) ?? false;
    }

    private function queryRouteKey(mixed $id): string
    {
        if ($id === null || $id === '') {
            return '';
        }

        if (! is_numeric($id)) {
            return (string) $id;
        }

        return app(PublicRouteKeyService::class)->encode((int) $id);
    }

    private function decodeQueryRouteKey(string $key): ?int
    {
        if ($key === '') {
            return null;
        }

        if (is_numeric($key)) {
            return (int) $key;
        }

        return app(PublicRouteKeyService::class)->decode($key);
    }
}
