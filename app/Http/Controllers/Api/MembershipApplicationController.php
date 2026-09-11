<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApplicationStatus;
use App\Enums\AssessmentStatus;
use App\Enums\FarmerStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StorePaymentRequest;
use App\Http\Requests\Api\SubmitMembershipApplicationRequest;
use App\Http\Requests\Api\TrackMembershipApplicationRequest;
use App\Models\MembershipApplication;
use App\Models\MemberType;
use App\Services\Documents\DocumentVerificationService;
use App\Services\Documents\DocumentRequirementService;
use App\Services\Farmers\FarmerRegistryService;
use App\Services\Membership\FeeCalculatorService;
use App\Services\Membership\MemberTypeResolverService;
use App\Services\Membership\MembershipApplicationService;
use App\Services\Payments\PaymentAssessmentService;
use App\Services\Payments\PaymentGatewayService;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MembershipApplicationController extends Controller
{
    private const MOBILE_UPLOAD_FILE_RULES = ['file', 'mimes:jpg,jpeg,png,pdf,webp,heic,heif', 'max:10240'];

    public function __construct(
        private readonly MembershipApplicationService $membershipApplicationService,
        private readonly FarmerRegistryService $farmerRegistryService,
        private readonly MemberTypeResolverService $memberTypeResolverService,
        private readonly DocumentRequirementService $documentRequirementService,
        private readonly DocumentVerificationService $documentVerificationService,
        private readonly FeeCalculatorService $feeCalculatorService,
        private readonly PaymentAssessmentService $paymentAssessmentService,
        private readonly PaymentGatewayService $paymentGatewayService,
    ) {
    }

    public function store(SubmitMembershipApplicationRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $reapplyApplication = $request->filled('reapply_from_application_id')
            ? MembershipApplication::query()
                ->with('farmer')
                ->whereKey($request->integer('reapply_from_application_id'))
                ->where('status', ApplicationStatus::REJECTED->value)
                ->where('source', 'mobile')
                ->first()
            : null;
        $duplicates = $this->farmerRegistryService->findPotentialDuplicates($validated, $reapplyApplication?->farmer);

        if ($duplicates->isNotEmpty()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'application' => [
                        'A matching farmer record already exists. Please contact the office to continue your membership application.',
                    ],
                ],
            ], 422);
        }

        $application = DB::transaction(function () use ($validated, $reapplyApplication): MembershipApplication {
            $memberTypeCode = $this->memberTypeResolverService->resolveEnum([
                'birth_date' => $validated['birth_date'],
                'has_existing_membership' => false,
            ]);

            $memberType = MemberType::query()->firstOrCreate(
                ['code' => $memberTypeCode->value],
                [
                    'name' => $memberTypeCode->label(),
                    'is_new_member' => $memberTypeCode->isNewMember(),
                    'is_senior' => $memberTypeCode->isSenior(),
                    'requires_membership_fee' => $memberTypeCode->isNewMember(),
                    'mortuary_eligible' => ! $memberTypeCode->isSenior(),
                ],
            );

            $farmerPayload = [
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name' => $validated['last_name'],
                'suffix' => $validated['suffix'] ?? null,
                'sex' => $validated['sex'] ?? null,
                'birth_date' => $validated['birth_date'],
                'civil_status' => $validated['civil_status'] ?? null,
                'mobile_number' => $validated['mobile_number'] ?? null,
                'email' => $validated['email'] ?? null,
                'address' => $validated['address'] ?? null,
                'barangay_id' => $validated['barangay_id'],
                'association_id' => $validated['association_id'] ?? null,
                'member_type_id' => $memberType->id,
                'is_registry_record' => false,
                'record_origin' => 'application',
                'status' => FarmerStatus::PENDING->value,
                'remarks' => $validated['remarks'] ?? null,
            ];

            $farmer = $reapplyApplication?->farmer
                ? $this->farmerRegistryService->update($reapplyApplication->farmer, $farmerPayload)
                : $this->farmerRegistryService->create($farmerPayload);

            return $this->membershipApplicationService->create([
                'farmer_id' => $farmer->id,
                'source' => 'mobile',
                'remarks' => $validated['remarks'] ?? null,
            ])->load(['farmer.profile', 'farmer.memberType', 'documents', 'paymentAssessments']);
        });

        return response()->json([
            'message' => $reapplyApplication
                ? 'Membership application resubmitted successfully.'
                : 'Membership application submitted successfully.',
            'data' => $this->trackingPayload($application),
        ], 201);
    }

    public function track(TrackMembershipApplicationRequest $request): JsonResponse
    {
        $application = $this->resolveTrackedApplication(
            (string) $request->validated('application_no'),
        );

        return response()->json([
            'data' => $this->trackingPayload($application),
        ]);
    }

    public function uploadDocument(Request $request, string $applicationNo): JsonResponse
    {
        try {
            if (! $request->filled('birth_date') && $request->query('birth_date')) {
                $request->merge([
                    'birth_date' => (string) $request->query('birth_date'),
                ]);
            }

            if (! $request->filled('document_type') && $request->query('document_type')) {
                $request->merge([
                    'document_type' => (string) $request->query('document_type'),
                ]);
            }

            $validated = $request->validate([
                'birth_date' => ['required', 'date'],
            ]);

            $application = $this->resolveTrackedApplication(
                $applicationNo,
                (string) $validated['birth_date'],
            );

            $batchedDocuments = array_filter((array) $request->file('documents', []));

            if ($batchedDocuments !== []) {
                return $this->uploadDocumentsBatch($request, $application);
            }

            $validated = $request->validate([
                'birth_date' => ['required', 'date'],
                'document_type' => ['required', 'string'],
                'document' => array_merge(['required'], self::MOBILE_UPLOAD_FILE_RULES),
            ]);

            $document = $this->membershipApplicationService->uploadMobileDocument(
                $application,
                (string) $validated['document_type'],
                $request->file('document'),
            );

            $application->refresh()->load(['farmer.profile', 'farmer.memberType', 'documents', 'paymentAssessments']);

            return response()->json([
                'message' => 'Document uploaded successfully.',
                'data' => [
                    'document' => [
                        'type' => $document->document_type?->value ?? (string) $document->document_type,
                        'label' => $document->document_type?->label() ?? ucfirst(str_replace('_', ' ', (string) $document->document_type)),
                        'status' => $document->verification_status?->value ?? (string) $document->verification_status,
                        'status_label' => $document->verification_status?->label() ?? ucfirst((string) $document->verification_status),
                        'uploaded' => true,
                        'original_name' => $document->original_name,
                    ],
                    'application' => $this->trackingPayload($application),
                ],
            ]);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error('Farmer PWA document upload failed.', [
                'application_no' => $applicationNo,
                'birth_date' => $request->input('birth_date', $request->query('birth_date')),
                'document_type' => $request->input('document_type', $request->query('document_type')),
                'has_document' => $request->hasFile('document'),
                'document_name' => $request->file('document')?->getClientOriginalName(),
                'document_mime' => $request->file('document')?->getClientMimeType(),
                'document_size' => $request->file('document')?->getSize(),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return response()->json([
                'message' => 'Upload failed: ' . $exception->getMessage(),
                'errors' => [
                    'server' => [
                        class_basename($exception) . ' in ' . basename($exception->getFile()) . ':' . $exception->getLine(),
                    ],
                ],
            ], 500);
        }
    }

    public function qrPage(Request $request, string $applicationNo): View|RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'birth_date' => ['required', 'date'],
        ]);

        $application = $this->resolveTrackedApplication(
            $applicationNo,
            (string) $validated['birth_date'],
        );

        $assessment = $application->paymentAssessments->sortByDesc('id')->first();

        if ($assessment && in_array($assessment->status?->value, [AssessmentStatus::PAID->value, AssessmentStatus::OVERPAID->value, AssessmentStatus::WAIVED->value], true)) {
            $request->session()->forget($this->applicationQrSessionKey($applicationNo, (string) $validated['birth_date']));

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Payment recorded successfully.',
                    'data' => [
                        'redirect_url' => $this->trackStatusAppUrl($applicationNo, (string) $validated['birth_date']),
                    ],
                ]);
            }

            return redirect()->route('farmer.pwa.track.status', [
                'application_no' => $applicationNo,
                'birth_date' => $validated['birth_date'],
            ])->with('success', 'Payment recorded successfully.');
        }

        $qrPayment = $request->session()->get($this->applicationQrSessionKey($applicationNo, (string) $validated['birth_date']));

        if (! is_array($qrPayment) || ! filled($qrPayment['qr_image_url'] ?? null)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'QR payment data was not found. Generate a new code from the tracking page.',
                ], 404);
            }

            abort(404);
        }

        if (! $this->qrAmountMatchesAssessment($qrPayment, $assessment?->total_amount_due)) {
            $request->session()->forget($this->applicationQrSessionKey($applicationNo, (string) $validated['birth_date']));

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'The previous QR code no longer matches the current payment amount. Generate a new QR code before paying.',
                    'data' => [
                        'redirect_url' => $this->trackStatusAppUrl($applicationNo, (string) $validated['birth_date']),
                    ],
                ], 409);
            }

            return redirect()->route('farmer.pwa.track.status', [
                'application_no' => $applicationNo,
                'birth_date' => $validated['birth_date'],
            ])->withErrors([
                'payment' => 'The previous QR code no longer matches the current payment amount. Generate a new QR code before paying.',
            ]);
        }

        $expiresAt = filled($qrPayment['expires_at'] ?? null)
            ? CarbonImmutable::parse((string) $qrPayment['expires_at'])
            : null;

        if ($request->expectsJson()) {
            return response()->json([
                'data' => [
                    'application_no' => $application->application_no,
                    'assessment_status' => $assessment?->status?->value ?? 'pending',
                    'assessment_status_label' => $assessment?->status?->label() ?? 'Pending',
                    'amount_due' => (float) ($assessment?->total_amount_due ?? ($qrPayment['amount'] ?? 0)),
                    'reference_no' => $qrPayment['reference_no'] ?? $application->application_no,
                    'expires_at' => $expiresAt?->toIso8601String(),
                    'is_expired' => $expiresAt?->isPast() ?? false,
                    'qr_image_url' => $qrPayment['qr_image_url'],
                    'breakdown' => [
                        'membership_fee' => $assessment?->membership_fee !== null ? (float) $assessment->membership_fee : null,
                        'annual_due' => $assessment?->annual_due !== null ? (float) $assessment->annual_due : null,
                        'mortuary_fee' => $assessment?->mortuary_fee !== null ? (float) $assessment->mortuary_fee : null,
                    ],
                    'track_status_url' => $this->trackStatusAppUrl($applicationNo, (string) $validated['birth_date']),
                ],
            ]);
        }

        return redirect($this->applicationQrAppUrl($applicationNo, (string) $validated['birth_date']));

    }

    public function pay(StorePaymentRequest $request, string $applicationNo): JsonResponse
    {
        $application = $this->resolveTrackedApplication(
            $applicationNo,
            (string) $request->validated('birth_date'),
        );

        if ($application->source !== 'mobile') {
            throw ValidationException::withMessages([
                'payment' => 'Only mobile applications can be paid from the Farmer PWA.',
            ]);
        }

        if (! $this->documentVerificationService->allRequiredVerified($application)) {
            throw ValidationException::withMessages([
                'payment' => 'Payment is available only after all required documents are verified.',
            ]);
        }

        $assessment = $this->usesQrPhTestAmount()
            ? $this->paymentAssessmentService->createForApplication($application)
            : ($application->paymentAssessments->sortByDesc('id')->first()
                ?? $this->paymentAssessmentService->createForApplication($application));

        if (in_array($assessment->status?->value, [AssessmentStatus::PAID->value, AssessmentStatus::OVERPAID->value, AssessmentStatus::WAIVED->value], true)) {
            throw ValidationException::withMessages([
                'payment' => 'Payment is already recorded for this application.',
            ]);
        }

        if ($this->paymentGatewayService->usesHostedCheckout()) {
            try {
                $birthDate = (string) $request->validated('birth_date');
                $billingEmail = trim((string) (($application->farmer?->users()->value('email')) ?: ('applicant-' . $application->id . '@anitech.local')));

                $qrPayment = $this->paymentGatewayService->createQrPhPayment([
                    'payment_method' => $request->validated('payment_method'),
                    'reference_no' => $request->validated('reference_no') ?: $application->application_no,
                    'amount' => $this->gatewayChargeAmount($assessment?->total_amount_due),
                    'currency' => 'PHP',
                    'description' => 'Membership application payment for ' . $application->application_no,
                    'line_item_name' => 'AniTech Membership Application',
                    'line_item_description' => 'Membership payment for application ' . $application->application_no,
                    'expiry_seconds' => 1800,
                    'metadata' => [
                        'assessment_id' => $assessment->id,
                        'payment_method' => 'qrph',
                        'membership_application_id' => $application->id,
                        'source_type' => 'membership_application',
                        'source_id' => $application->id,
                    ],
                    'billing' => [
                        'name' => trim((string) ($application->farmer?->full_name ?: 'AniTech Applicant')),
                        'email' => $billingEmail,
                        'phone' => trim((string) ($application->farmer?->profile?->mobile_number ?? '')),
                    ],
                ]);
            } catch (DomainException $exception) {
                throw ValidationException::withMessages([
                    'payment' => $exception->getMessage(),
                ]);
            }

            $request->session()->put($this->applicationQrSessionKey($applicationNo, $birthDate), $qrPayment);

            return response()->json([
                'message' => 'QR Ph code generated successfully.',
                'data' => [
                    'application' => $this->trackingPayload($application->refresh()->load(['farmer.profile', 'farmer.memberType', 'documents', 'paymentAssessments'])),
                    'provider' => $qrPayment['provider'],
                    'qr_page_url' => route('farmer.pwa.application.payment.qr', [
                        'applicationNo' => $applicationNo,
                        'birth_date' => $birthDate,
                    ]),
                    'qr_image_url' => $qrPayment['qr_image_url'],
                    'payment_reference' => $qrPayment['reference_no'],
                ],
            ]);
        }

        try {
            $this->membershipApplicationService->recordPayment($application, [
                'payment_method' => $request->validated('payment_method'),
                'reference_no' => $request->validated('reference_no'),
                'amount_paid' => (float) $assessment->total_amount_due,
                'paid_at' => now()->toDateTimeString(),
                'receipt_no' => 'PWA-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4)),
            ]);
        } catch (DomainException $exception) {
            throw ValidationException::withMessages([
                'payment' => $exception->getMessage(),
            ]);
        }

        $application->refresh()->load(['farmer.profile', 'farmer.memberType', 'documents', 'paymentAssessments']);

        return response()->json([
            'message' => 'Payment recorded successfully. Your membership is now active.',
            'data' => [
                'application' => $this->trackingPayload($application),
            ],
        ]);
    }

    private function resolveTrackedApplication(string $applicationNo, ?string $birthDate = null): MembershipApplication
    {
        $application = MembershipApplication::query()
            ->with(['farmer.profile', 'farmer.memberType', 'documents', 'paymentAssessments'])
            ->where('application_no', $applicationNo)
            ->when($birthDate !== null, fn ($query) => $query->whereHas('farmer.profile', fn ($profile) => $profile->whereDate('birth_date', $birthDate)))
            ->first();

        if (! $application) {
            throw ValidationException::withMessages([
                'application_no' => $birthDate === null
                    ? 'No membership application matched the application number you entered.'
                    : 'No membership application matched the application number and birth date you entered.',
            ]);
        }

        return $application;
    }

    private function trackingPayload(MembershipApplication $application): array
    {
        $application->loadMissing(['farmer.profile', 'farmer.memberType', 'documents', 'paymentAssessments']);
        $assessment = $application->paymentAssessments->sortByDesc('id')->first();
        $profile = $application->farmer?->profile;
        $allRequiredVerified = $this->documentVerificationService->allRequiredVerified($application);
        $farmer = $application->farmer;
        $accountUser = $farmer?->users()->orderByDesc('id')->first();
        $farmerAccountActive = $accountUser
            && $accountUser->status === \App\Models\User::STATUS_ACTIVE
            && $farmer?->membership_status?->value === 'active'
            && $farmer?->status === FarmerStatus::ACTIVE;

        if (
            $this->usesQrPhTestAmount()
            && $allRequiredVerified
            && ! in_array($assessment?->status?->value, [AssessmentStatus::PAID->value, AssessmentStatus::OVERPAID->value, AssessmentStatus::WAIVED->value], true)
        ) {
            $assessment = $this->paymentAssessmentService->createForApplication($application);
        }

        $canPay = $application->source === 'mobile'
            && $allRequiredVerified
            && ! in_array($assessment?->status?->value, [AssessmentStatus::PAID->value, AssessmentStatus::OVERPAID->value, AssessmentStatus::WAIVED->value], true);

        return [
            'application_no' => $application->application_no,
            'source' => $application->source,
            'status' => $application->status?->value ?? (string) $application->status,
            'status_label' => $application->status?->label() ?? ucfirst((string) $application->status),
            'submitted_at' => optional($application->submitted_at)->toIso8601String(),
            'approved_at' => optional($application->approved_at)->toIso8601String(),
            'remarks' => $application->remarks,
            'rejection' => [
                'reason' => $application->rejection_reason?->value,
                'reason_label' => $application->rejection_reason_label,
                'details' => $application->effective_rejection_details,
            ],
            'farmer' => [
                'name' => $application->farmer?->full_name,
                'first_name' => $profile?->first_name,
                'middle_name' => $profile?->middle_name,
                'last_name' => $profile?->last_name,
                'suffix' => $profile?->suffix,
                'birth_date' => optional($profile?->birth_date)?->toDateString(),
                'sex' => $profile?->sex,
                'civil_status' => $profile?->civil_status,
                'mobile_number' => $profile?->mobile_number,
                'email' => $application->farmer?->users()->value('email'),
                'address' => $profile?->address,
                'barangay_id' => $application->farmer?->barangay_id,
                'association_id' => $application->farmer?->association_id,
                'farmer_code' => $application->farmer?->farmer_code,
                'membership_status' => $application->farmer?->membership_status?->value ?? null,
                'membership_status_label' => $application->farmer?->membership_status?->label() ?? null,
                'member_type' => $application->farmer?->memberType?->code,
                'member_type_label' => $application->farmer?->memberType?->name,
            ],
            'documents' => $application->documents
                ->sortBy('document_type')
                ->map(fn ($document) => [
                    'type' => $document->document_type?->value ?? (string) $document->document_type,
                    'label' => $document->document_type?->label() ?? ucfirst(str_replace('_', ' ', (string) $document->document_type)),
                    'uploaded' => $this->documentUploaded($document),
                    'verification_status' => $document->verification_status?->value ?? (string) $document->verification_status,
                    'verification_status_label' => $document->verification_status?->label() ?? ucfirst((string) $document->verification_status),
                    'remarks' => $document->remarks,
                    'needs_correction' => ($document->verification_status?->value ?? (string) $document->verification_status) === 'rejected',
                    'original_name' => $document->original_name,
                ])->values(),
            'payment' => [
                'status' => $assessment?->status?->value ?? null,
                'status_label' => $assessment?->status?->label() ?? 'Pending Approval',
                'total_amount_due' => $assessment?->total_amount_due !== null ? (float) $assessment->total_amount_due : null,
                'can_pay' => $canPay,
                'reference_no' => $application->application_no,
                'is_verified' => in_array($assessment?->status?->value, [AssessmentStatus::PAID->value, AssessmentStatus::OVERPAID->value, AssessmentStatus::WAIVED->value], true),
                'instructions' => [
                    'Review the exact fee breakdown before paying.',
                    'Use the generated QR Ph code in your bank or e-wallet app.',
                    'Keep the payment reference for office verification.',
                    'Return to the tracking page to confirm that payment has been verified.',
                ],
                'breakdown' => [
                    'membership_fee' => $assessment?->membership_fee !== null ? (float) $assessment->membership_fee : null,
                    'annual_due' => $assessment?->annual_due !== null ? (float) $assessment->annual_due : null,
                    'mortuary_fee' => $assessment?->mortuary_fee !== null ? (float) $assessment->mortuary_fee : null,
                ],
            ],
            'account' => [
                'can_setup' => $application->status?->value === ApplicationStatus::APPROVED->value
                    && (! $farmerAccountActive),
                'has_account' => (bool) $farmerAccountActive,
                'requires_resetup' => (bool) ($accountUser && ! $farmerAccountActive),
                'email' => $accountUser?->email,
            ],
            'required_documents' => collect($this->documentRequirementService->requiredFor('application', $application))
                ->map(fn ($type) => [
                    'type' => $type->value,
                    'label' => $type->label(),
                ])->values(),
            'reapply' => [
                'can_reapply' => $application->source === 'mobile' && $application->status?->value === ApplicationStatus::REJECTED->value,
                'application_id' => $application->source === 'mobile' && $application->status?->value === ApplicationStatus::REJECTED->value
                    ? $application->id
                    : null,
            ],
        ];
    }

    private function uploadDocumentsBatch(Request $request, MembershipApplication $application): JsonResponse
    {
        $validated = $request->validate([
            'documents' => ['required', 'array', 'min:1'],
            'documents.*' => self::MOBILE_UPLOAD_FILE_RULES,
        ]);

        $documents = collect($request->file('documents', []))
            ->mapWithKeys(fn ($file, $documentType) => [$this->normalizeDocumentTypeKey((string) $documentType) => $file])
            ->all();
        $documentMap = $application->documents->mapWithKeys(function ($document) {
            $documentType = $this->normalizeDocumentTypeKey($document->document_type?->value ?? (string) $document->document_type);

            return [$documentType => $document];
        });

        $invalidDocumentTypes = array_diff(array_keys($documents), $documentMap->keys()->all());
        if ($invalidDocumentTypes !== []) {
            throw ValidationException::withMessages([
                'documents' => 'One or more selected files do not match the required checklist for this application.',
            ]);
        }

        foreach ($documents as $documentType => $file) {
            $this->membershipApplicationService->uploadMobileDocument($application, (string) $documentType, $file);
        }

        $application->refresh()->load(['farmer.profile', 'farmer.memberType', 'documents', 'paymentAssessments']);

        return response()->json([
            'message' => 'Documents uploaded successfully.',
            'data' => [
                'application' => $this->trackingPayload($application),
            ],
        ]);
    }

    private function documentUploaded(object $document): bool
    {
        $path = trim((string) ($document->file_path ?? $document->path ?? ''));

        if ($path === '') {
            return false;
        }

        return ! str_starts_with($path, 'pending-upload/')
            && ! str_starts_with($path, 'office-checklist/');
    }

    private function applicationQrSessionKey(string $applicationNo, string $birthDate): string
    {
        return 'farmer_pwa.application_qr_payment.' . md5($applicationNo . '|' . $birthDate);
    }

    private function normalizeDocumentTypeKey(string $value): string
    {
        $normalized = strtolower(trim($value));

        return match ($normalized) {
            'birthcertificate' => 'birth_certificate',
            '2x2picture', 'twobytwopicture' => 'two_by_two_picture',
            default => $normalized,
        };
    }

    private function usesQrPhTestAmount(): bool
    {
        return $this->feeCalculatorService->usesTestingChargeAmount();
    }

    private function gatewayChargeAmount(mixed $amountDue): float
    {
        return $this->feeCalculatorService->resolveChargeAmount($amountDue);
    }

    private function qrAmountMatchesAssessment(array $qrPayment, mixed $amountDue): bool
    {
        if (! is_numeric($qrPayment['amount'] ?? null) || ! is_numeric($amountDue)) {
            return true;
        }

        return round((float) $qrPayment['amount'], 2) === round($this->gatewayChargeAmount($amountDue), 2);
    }

    private function applicationQrAppUrl(string $applicationNo, string $birthDate): string
    {
        return url('/farmer/app/payment/qr?' . http_build_query([
            'application_no' => $applicationNo,
            'birth_date' => $birthDate,
        ]));
    }

    private function trackStatusAppUrl(string $applicationNo, string $birthDate): string
    {
        return url('/farmer/app/track-status?' . http_build_query([
            'application_no' => $applicationNo,
            'birth_date' => $birthDate,
        ]));
    }
}
