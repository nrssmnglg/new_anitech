<?php

namespace App\Services\Membership;

use App\Enums\ApplicationStatus;
use App\Enums\AssessmentStatus;
use App\Enums\DocumentVerificationStatus;
use App\Enums\MembershipStatus;
use App\Enums\NotificationType;
use App\Models\MembershipApplication;
use App\Models\MembershipLedger;
use App\Models\User;
use App\Services\Audit\AuditTrailService;
use App\Services\Documents\DocumentVerificationService;
use App\Services\Documents\FarmerDocumentService;
use App\Services\Notifications\NotificationDispatchService;
use App\Services\Payments\PaymentAssessmentService;
use App\Services\Payments\PaymentPostingService;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MembershipApplicationService
{
    private ?array $farmerDocumentColumns = null;

    public function __construct(
        private readonly ApplicationWorkflowService $workflow,
        private readonly FarmerDocumentService $farmerDocumentService,
        private readonly DocumentVerificationService $documentVerificationService,
        private readonly PaymentAssessmentService $paymentAssessmentService,
        private readonly PaymentPostingService $paymentPostingService,
        private readonly MembershipLedgerService $ledgerService,
        private readonly MembershipStatusService $membershipStatusService,
        private readonly NotificationDispatchService $notificationDispatchService,
        private readonly AuditTrailService $auditTrailService,
    ) {
    }

    public function create(array $attributes): MembershipApplication
    {
        return DB::transaction(function () use ($attributes): MembershipApplication {
            $isMobile = ($attributes['source'] ?? null) === 'mobile';
            $application = MembershipApplication::query()->create([
                'farmer_id' => $attributes['farmer_id'],
                'application_no' => $this->generateApplicationNumber(),
                'source' => $attributes['source'],
                'status' => $isMobile ? ApplicationStatus::DRAFT : ApplicationStatus::SUBMITTED,
                'submitted_at' => $isMobile ? null : CarbonImmutable::now(),
            ]);

            if ($application->source !== 'walk_in') {
                $this->farmerDocumentService->ensureApplicationChecklist($application);
            }
            $application->load('farmer');
            $application->farmer?->forceFill([
                'membership_status' => MembershipStatus::PENDING_APPLICATION->value,
            ])->save();
            $this->auditTrailService->recordById(
                'membership_applications',
                'application_created',
                'Created a membership application record.',
                null,
                $application,
                [
                    'application_no' => $application->application_no,
                    'source' => $application->source,
                    'farmer_id' => $application->farmer_id,
                ]
            );

            return $application->refresh()->load(['farmer', 'documents']);
        });
    }

    public function initializeChecklist(MembershipApplication $application, ?int $userId = null): MembershipApplication
    {
        return DB::transaction(function () use ($application, $userId): MembershipApplication {
            $application->loadMissing('documents');

            if ($application->documents->isNotEmpty()) {
                return $application->refresh()->load(['farmer', 'documents']);
            }

            $this->farmerDocumentService->ensureApplicationChecklist($application);
            $initialized = $application->refresh()->load(['farmer', 'documents']);

            $this->auditTrailService->recordById(
                'membership_applications',
                'checklist_initialized',
                'Initialized the membership application document checklist.',
                $userId,
                $initialized,
                [
                    'application_no' => $initialized->application_no,
                    'source' => $initialized->source,
                ]
            );

            return $initialized;
        });
    }

    public function markDocumentReceived(MembershipApplication $application, int $documentId, bool $received, ?int $userId = null)
    {
        $application->loadMissing('documents', 'farmer');
        $document = $application->documents->firstWhere('id', $documentId)
            ?? throw new DomainException('Document not found for this application.');

        $document = $this->farmerDocumentService->markReceived($document, $received, $userId);

        if ($application->source === 'walk_in' && $received) {
            $document = $this->documentVerificationService->verifyDocument($document, $userId, 'Verified during walk-in intake.');
        }

        $this->updateMembershipStatusFromDocuments($application->refresh()->load(['documents', 'farmer']));
        $this->auditTrailService->recordById(
            'membership_applications',
            $received ? 'document_received' : 'document_unreceived',
            $received ? 'Marked a membership application document as received.' : 'Marked a membership application document as not received.',
            $userId,
            $application,
            [
                'document_id' => $document->id,
                'document_type' => $document->document_type?->value ?? $document->document_type,
            ]
        );

        return $document;
    }

    public function verifyDocument(MembershipApplication $application, int $documentId, ?int $userId = null, ?string $remarks = null)
    {
        $application->loadMissing('documents', 'farmer');
        $document = $application->documents->firstWhere('id', $documentId)
            ?? throw new DomainException('Document not found for this application.');

        $document = $this->documentVerificationService->verifyDocument($document, $userId, $remarks);
        $this->updateMembershipStatusFromDocuments($application->refresh()->load(['documents', 'farmer']));
        $this->auditTrailService->recordById(
            'membership_applications',
            'document_verified',
            'Verified a membership application document.',
            $userId,
            $application,
            [
                'document_id' => $document->id,
                'document_type' => $document->document_type?->value ?? $document->document_type,
                'remarks' => $remarks,
            ]
        );

        return $document;
    }

    public function rejectDocument(MembershipApplication $application, int $documentId, ?int $userId = null, ?string $remarks = null)
    {
        $application->loadMissing('documents', 'farmer');
        $document = $application->documents->firstWhere('id', $documentId)
            ?? throw new DomainException('Document not found for this application.');

        $document = $this->documentVerificationService->rejectDocument($document, $userId, $remarks);
        $this->updateMembershipStatusFromDocuments($application->refresh()->load(['documents', 'farmer']));
        $this->auditTrailService->recordById(
            'membership_applications',
            'document_rejected',
            'Rejected a membership application document.',
            $userId,
            $application,
            [
                'document_id' => $document->id,
                'document_type' => $document->document_type?->value ?? $document->document_type,
                'remarks' => $remarks,
            ]
        );

        return $document;
    }

    public function approve(MembershipApplication $application, ?int $reviewerId = null): MembershipApplication
    {
        if ($application->source === 'walk_in') {
            throw new DomainException('Walk-in applications are approved automatically when payment is recorded.');
        }

        return DB::transaction(fn (): MembershipApplication => $this->finalizeApproval($application, $reviewerId));
    }

    public function reject(
        MembershipApplication $application,
        string $reason,
        ?string $details = null,
        ?int $reviewerId = null,
    ): MembershipApplication {
        if (blank($reason)) {
            throw new DomainException('Rejection reason is required.');
        }

        return DB::transaction(function () use ($application, $reason, $details, $reviewerId): MembershipApplication {
            $application->loadMissing(['farmer', 'documents']);
            $currentStatus = $application->status ?? ApplicationStatus::SUBMITTED;
            if ($currentStatus === ApplicationStatus::SUBMITTED) {
                $currentStatus = $this->workflow->startReview($currentStatus);
            }

            $application->forceFill([
                'status' => $this->workflow->reject($currentStatus),
                'reviewed_by' => $reviewerId,
                'reviewed_at' => CarbonImmutable::now(),
                'rejected_at' => CarbonImmutable::now(),
                'rejection_reason' => $reason,
            ])->save();

            $application->documents
                ->filter(fn ($document) => $document->verification_status === DocumentVerificationStatus::VERIFIED)
                ->each(function ($document) use ($reviewerId, $details): void {
                    $document->forceFill([
                        'verification_status' => DocumentVerificationStatus::REJECTED,
                        'verified_by' => $reviewerId,
                        'verified_at' => CarbonImmutable::now(),
                        'remarks' => blank($details) ? $document->remarks : $details,
                    ])->save();
                });

            $this->membershipStatusService->markPendingApplication($application->farmer);
            $this->queueRejectedNotification($application, $reviewerId);
            $this->auditTrailService->recordById(
                'membership_applications',
                'application_rejected',
                'Rejected a membership application.',
                $reviewerId,
                $application,
                $this->auditTrailService->activityMetadata(
                    AuditTrailService::ACTION_APPROVED,
                    'Approval decision',
                    [
                    'application_no' => $application->application_no,
                    'reason' => $reason,
                    'details' => $details,
                    ]
                )
            );

            return $application->refresh()->load('farmer');
        });
    }

    public function recordPayment(MembershipApplication $application, array $attributes, ?int $userId = null): array
    {
        return DB::transaction(function () use ($application, $attributes, $userId): array {
            $application->loadMissing(['farmer.memberType', 'documents']);

            if ($application->status === ApplicationStatus::REJECTED) {
                throw new DomainException('Rejected applications cannot accept payment.');
            }

            if (! $this->documentVerificationService->allRequiredVerified($application)) {
                throw new DomainException('Payment is not allowed until all required documents are verified.');
            }

            if ($application->status !== ApplicationStatus::APPROVED) {
                if ($application->source === 'walk_in') {
                    $application = $this->finalizeApproval($application, $userId);
                } elseif ($application->source === 'mobile') {
                    $application = $this->finalizeApproval($application, $userId);
                }
            }

            if ($application->status !== ApplicationStatus::APPROVED) {
                throw new DomainException('Only approved applications can accept payment.');
            }

            $assessment = $this->paymentAssessmentService->createForApplication($application);
            $paymentResult = $this->paymentPostingService->record($assessment, $attributes, $userId);
            $summary = $paymentResult['summary'];

            $ledger = MembershipLedger::query()->firstOrNew([
                'membership_transaction_id' => $application->id,
                'year' => now()->year,
            ]);

            $ledger->fill($this->ledgerService->buildFromSource(
                'application',
                [
                    'id' => $application->id,
                    'status' => in_array($summary['assessment_status'], [AssessmentStatus::PAID, AssessmentStatus::OVERPAID, AssessmentStatus::WAIVED], true)
                        ? 'Active'
                        : 'Inactive',
                    'member_type' => $application->farmer->memberType?->code,
                    'year' => now()->year,
                ],
                [
                    'fee_schedule_id' => $assessment->fee_schedule_id,
                    'member_type' => $application->farmer->memberType?->code,
                    'mortuary_eligible' => ! in_array($application->farmer->memberType?->code, ['NSC', 'OSC'], true),
                ],
                $summary,
            ));
            $ledger->save();

            if (in_array($summary['assessment_status'], [AssessmentStatus::PAID, AssessmentStatus::OVERPAID], true)) {
                $this->membershipStatusService->activateMembership($application->farmer);
                $this->promoteToRegistryRecord($application);
            } else {
                $this->membershipStatusService->markPendingPayment($application->farmer);
            }

            $this->queuePaymentRecordedNotification(
                $application->refresh()->load(['farmer', 'paymentAssessments']),
                round((float) ($attributes['amount_paid'] ?? 0), 2),
                $userId,
            );
            $this->auditTrailService->recordById(
                'membership_payments',
                'payment_recorded',
                'Recorded a membership application payment.',
                $userId,
                $application,
                $this->auditTrailService->activityMetadata(
                    AuditTrailService::ACTION_PROCESSED,
                    'Payment processing',
                    [
                    'application_no' => $application->application_no,
                    'payment_id' => $paymentResult['payment']->id,
                    'assessment_id' => $assessment->id,
                    'amount_paid' => round((float) ($attributes['amount_paid'] ?? 0), 2),
                    'assessment_status' => $summary['assessment_status'] ?? null,
                    ]
                )
            );

            return [
                'application' => $application->refresh()->load('farmer'),
                'assessment' => $assessment->refresh(),
                'payment' => $paymentResult['payment'],
                'ledger' => $ledger->refresh(),
                'summary' => $summary,
            ];
        });
    }

    public function uploadMobileDocument(MembershipApplication $application, string $documentType, UploadedFile $file)
    {
        return DB::transaction(function () use ($application, $documentType, $file) {
            $application->loadMissing(['documents', 'farmer']);

            if ($application->source !== 'mobile') {
                throw new DomainException('Only mobile applications can upload documents through this endpoint.');
            }

            $document = $application->documents->firstWhere('document_type.value', $documentType)
                ?? $application->documents->firstWhere('document_type', $documentType)
                ?? throw new DomainException('Document type is not required for this application.');

            $filename = now()->format('YmdHis') . '-' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs(
                'membership-applications/' . $application->application_no . '/' . $documentType,
                $filename,
                'public',
            );

            $payload = [
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'uploaded_at' => now(),
                'verification_status' => DocumentVerificationStatus::PENDING,
                'verified_by' => null,
                'verified_at' => null,
                'remarks' => null,
            ];

            if ($this->farmerDocumentHasColumn('mime_type')) {
                $payload['mime_type'] = $file->getClientMimeType();
            }

            if ($this->farmerDocumentHasColumn('file_size')) {
                $payload['file_size'] = $file->getSize();
            }

            $document->forceFill($payload)->save();

            if (($application->status?->value ?? (string) $application->status) === ApplicationStatus::DRAFT->value) {
                $application->forceFill([
                    'status' => $this->workflow->submit(ApplicationStatus::DRAFT),
                    'submitted_at' => $application->submitted_at ?? CarbonImmutable::now(),
                ])->save();
            }

            $application = $application->refresh()->load(['documents', 'farmer']);
            $this->updateMembershipStatusFromDocuments($application);
            $this->queueMobileUpdateNotification($application, $document->refresh());

            return $document->refresh();
        });
    }

    public function attachOfficeDocumentScan(MembershipApplication $application, int $documentId, UploadedFile $file)
    {
        return DB::transaction(function () use ($application, $documentId, $file) {
            $application->loadMissing(['documents', 'farmer']);

            if ($application->source !== 'walk_in') {
                throw new DomainException('Office document scans can only be attached to walk-in applications.');
            }

            $document = $application->documents->firstWhere('id', $documentId)
                ?? throw new DomainException('Document not found for this application.');

            $documentType = $document->document_type?->value ?? 'document';
            $filename = now()->format('YmdHis') . '-' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs(
                'membership-applications/' . $application->application_no . '/office/' . $documentType,
                $filename,
                'public',
            );

            $payload = [
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'uploaded_at' => now(),
            ];

            if ($this->farmerDocumentHasColumn('mime_type')) {
                $payload['mime_type'] = $file->getClientMimeType();
            }

            if ($this->farmerDocumentHasColumn('file_size')) {
                $payload['file_size'] = $file->getSize();
            }

            $document->forceFill($payload)->save();

            return $document->refresh()->load('documentType');
        });
    }

    private function farmerDocumentHasColumn(string $column): bool
    {
        if ($this->farmerDocumentColumns === null) {
            $this->farmerDocumentColumns = Schema::getColumnListing('farmer_documents');
        }

        return in_array($column, $this->farmerDocumentColumns, true);
    }

    private function finalizeApproval(MembershipApplication $application, ?int $reviewerId = null): MembershipApplication
    {
        $application->loadMissing(['documents', 'farmer.memberType']);

        if (! $this->documentVerificationService->allRequiredVerified($application)) {
            throw new DomainException('All required documents must be verified before approval.');
        }

        $currentStatus = $application->status ?? ApplicationStatus::SUBMITTED;
        if ($currentStatus === ApplicationStatus::SUBMITTED) {
            $currentStatus = $this->workflow->startReview($currentStatus);
        }

        $application->forceFill([
            'status' => $this->workflow->approve($currentStatus),
            'reviewed_by' => $reviewerId,
            'reviewed_at' => CarbonImmutable::now(),
            'rejection_reason' => null,
            'rejected_at' => null,
        ])->save();

        $assessment = $this->paymentAssessmentService->createForApplication($application);
        $this->membershipStatusService->markPendingPayment($application->farmer);
        $approvedApplication = $application->refresh()->load(['farmer', 'documents', 'paymentAssessments']);

        $this->queueApprovedNotification($approvedApplication, $assessment->total_amount_due, $reviewerId);
        $this->auditTrailService->recordById(
            'membership_applications',
            'application_approved',
            'Approved a membership application.',
            $reviewerId,
            $approvedApplication,
            $this->auditTrailService->activityMetadata(
                AuditTrailService::ACTION_APPROVED,
                'Approval decision',
                [
                'application_no' => $approvedApplication->application_no,
                'assessment_id' => $assessment->id,
                'amount_due' => $assessment->total_amount_due,
                ]
            )
        );

        return $approvedApplication;
    }

    private function updateMembershipStatusFromDocuments(MembershipApplication $application): void
    {
        $farmer = $application->farmer;

        if ($this->documentVerificationService->allRequiredVerified($application)) {
            if (strtolower((string) $application->source) === 'walk_in') {
                $this->membershipStatusService->markPendingPayment($farmer);
                return;
            }

            $this->membershipStatusService->markPendingVerification($farmer);
            return;
        }

        $allSubmitted = $application->documents
            ->where('is_required', true)
            ->every(fn ($document) => $this->farmerDocumentService->readyForVerification($document));

        if ($allSubmitted) {
            $this->membershipStatusService->markPendingVerification($farmer);
            return;
        }

        $this->membershipStatusService->markPendingDocuments($farmer);
    }

    private function promoteToRegistryRecord(MembershipApplication $application): void
    {
        $application->farmer->forceFill([
            'record_origin' => 'application',
        ])->save();
    }

    private function queueMobileUpdateNotification(MembershipApplication $application, mixed $document): void
    {
        if ($application->source !== 'mobile') {
            return;
        }

        $application->loadMissing('farmer.profile');
        $farmerName = trim((string) ($application->farmer?->full_name ?? '')) ?: 'A farmer';
        $documentLabel = $document->document_type?->label()
            ?? Str::headline(str_replace('_', ' ', (string) ($document->document_type ?? 'document')));
        $existingNotificationId = $this->existingMobileApplicationNotificationId($application, $this->adminRecipients());
        $isNewApplicationNotification = $existingNotificationId === null;

        $this->persistAdminApplicationNotification(
            $application,
            $isNewApplicationNotification
                ? NotificationType::MEMBERSHIP_APPLICATION_SUBMITTED
                : NotificationType::MEMBERSHIP_APPLICATION_UPDATED,
            [
                'subject' => $isNewApplicationNotification
                    ? 'New membership application'
                    : 'Mobile application updated',
                'message' => $isNewApplicationNotification
                    ? $farmerName . ' submitted a new membership application ' . ($application->application_no ?: 'N/A') . ' and uploaded ' . $documentLabel . '.'
                    : $farmerName . ' uploaded or updated ' . $documentLabel . ' for membership application ' . ($application->application_no ?: 'N/A') . '.',
                'application_id' => $application->id,
                'application_no' => $application->application_no,
                'farmer_id' => $application->farmer_id,
                'source' => $application->source,
                'document_type' => $document->document_type?->value ?? (string) $document->document_type,
                'document_label' => $documentLabel,
                'uploaded_at' => now()->toDateTimeString(),
                'is_new_application' => $isNewApplicationNotification,
            ],
            $existingNotificationId,
        );
    }

    private function persistAdminApplicationNotification(
        MembershipApplication $application,
        NotificationType $type,
        array $payload,
        ?int $existingNotificationId = null,
    ): void {
        $recipients = $this->adminRecipients();

        if ($recipients === []) {
            return;
        }

        $notification = $this->notificationDispatchService->queue($type, $recipients, $payload);

        if ($application->source !== 'mobile') {
            $this->notificationDispatchService->persistQueued($notification);
            return;
        }

        $notificationId = $existingNotificationId
            ?? $this->existingMobileApplicationNotificationId($application, $recipients);

        if ($notificationId === null) {
            $this->notificationDispatchService->persistQueued($notification);
            return;
        }

        $this->refreshAdminApplicationNotification($notificationId, $notification);
    }

    private function existingMobileApplicationNotificationId(MembershipApplication $application, array $recipients): ?int
    {
        $userIds = collect($recipients)
            ->pluck('user_id')
            ->filter()
            ->values()
            ->all();

        if ($userIds === []) {
            return null;
        }

        return DB::table('notifications')
            ->join('notification_recipients as recipients', 'recipients.notification_id', '=', 'notifications.id')
            ->whereIn('notifications.type', [
                NotificationType::MEMBERSHIP_APPLICATION_SUBMITTED->value,
                NotificationType::MEMBERSHIP_APPLICATION_UPDATED->value,
            ])
            ->where('notifications.payload->application_id', $application->id)
            ->whereIn('recipients.user_id', $userIds)
            ->orderByDesc('notifications.id')
            ->distinct()
            ->value('notifications.id');
    }

    private function refreshAdminApplicationNotification(int $notificationId, array $notification): void
    {
        $timestamp = CarbonImmutable::now()->toDateTimeString();
        $payload = $notification['payload'] ?? [];

        DB::transaction(function () use ($notificationId, $notification, $timestamp, $payload): void {
            DB::table('notifications')
                ->where('id', $notificationId)
                ->update([
                    'type' => $notification['type'],
                    'subject' => $notification['subject'],
                    'message' => $notification['message'],
                    'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
                    'status' => $notification['status'] ?? 'queued',
                    'queued_at' => $notification['queued_at'] ?? $timestamp,
                    'sent_at' => null,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);

            $existingRecipients = DB::table('notification_recipients')
                ->where('notification_id', $notificationId)
                ->get()
                ->keyBy(fn (object $recipient): string => (string) ($recipient->user_id ?? 0));

            foreach ($notification['recipients'] ?? [] as $recipient) {
                $userId = (int) ($recipient['user_id'] ?? 0);
                $existingRecipient = $existingRecipients->get((string) $userId);

                if ($existingRecipient !== null) {
                    DB::table('notification_recipients')
                        ->where('id', $existingRecipient->id)
                        ->update([
                            'recipient_address' => $recipient['recipient_address'] ?? null,
                            'status' => 'pending',
                            'delivered_at' => null,
                            'read_at' => null,
                            'failed_at' => null,
                            'failure_reason' => null,
                            'updated_at' => $timestamp,
                        ]);

                    continue;
                }

                DB::table('notification_recipients')->insert([
                    'notification_id' => $notificationId,
                    'user_id' => $recipient['user_id'] ?? null,
                    'farmer_id' => $recipient['farmer_id'] ?? null,
                    'recipient_address' => $recipient['recipient_address'] ?? null,
                    'status' => 'pending',
                    'delivered_at' => null,
                    'read_at' => null,
                    'failed_at' => null,
                    'failure_reason' => null,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);
            }
        });
    }

    private function queueApprovedNotification(MembershipApplication $application, float|int|string|null $amountDue, ?int $reviewerId = null): void
    {
        if (! $this->shouldSendNotifications($application)) {
            return;
        }

        $recipient = $this->farmerRecipient($application);

        if ($recipient === null) {
            return;
        }

        $formattedAmount = 'PHP ' . number_format((float) $amountDue, 2);

        $this->notificationDispatchService->persist(
            NotificationType::MEMBERSHIP_APPLICATION_APPROVED,
            [$recipient],
            [
                'subject' => 'Membership application approved',
                'message' => 'Application ' . ($application->application_no ?: 'N/A') . ' was approved. Amount due: ' . $formattedAmount . '.',
                'application_id' => $application->id,
                'application_no' => $application->application_no,
                'farmer_id' => $application->farmer_id,
                'amount_due' => (float) $amountDue,
                'reviewed_at' => optional($application->reviewed_at)->toDateTimeString(),
            ],
            $reviewerId,
        );
    }

    private function queueRejectedNotification(MembershipApplication $application, ?int $reviewerId = null): void
    {
        if (! $this->shouldSendNotifications($application)) {
            return;
        }

        $recipient = $this->farmerRecipient($application);

        if ($recipient === null) {
            return;
        }

        $reason = trim(collect([
            $application->rejection_reason,
            $application->effective_rejection_details,
        ])->filter()->implode(': '));

        $this->notificationDispatchService->persist(
            NotificationType::MEMBERSHIP_APPLICATION_REJECTED,
            [$recipient],
            [
                'subject' => 'Membership application rejected',
                'message' => 'Application ' . ($application->application_no ?: 'N/A') . ' was rejected' . ($reason !== '' ? '. Reason: ' . $reason . '.' : '.'),
                'application_id' => $application->id,
                'application_no' => $application->application_no,
                'farmer_id' => $application->farmer_id,
                'rejection_reason' => $application->rejection_reason,
                'rejection_details' => $application->effective_rejection_details,
                'reviewed_at' => optional($application->reviewed_at)->toDateTimeString(),
            ],
            $reviewerId,
        );
    }

    private function queuePaymentRecordedNotification(MembershipApplication $application, float $amountPaid, ?int $userId = null): void
    {
        if (! $this->shouldSendNotifications($application)) {
            return;
        }

        $recipient = $this->farmerRecipient($application);

        if ($recipient === null) {
            return;
        }

        $this->notificationDispatchService->persist(
            NotificationType::PAYMENT_RECORDED,
            [$recipient],
            [
                'subject' => 'Membership payment recorded',
                'message' => 'We recorded your membership payment of PHP ' . number_format($amountPaid, 2) . '.',
                'application_id' => $application->id,
                'application_no' => $application->application_no,
                'farmer_id' => $application->farmer_id,
                'amount_paid' => $amountPaid,
                'source_type' => 'membership_application',
            ],
            $userId,
        );
    }

    private function farmerRecipient(MembershipApplication $application): ?array
    {
        return null;
    }

    private function adminRecipients(): array
    {
        return User::query()
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_STAFF])
            ->get(['id', 'email'])
            ->map(fn (User $user): array => [
                'user_id' => $user->id,
                'email' => $user->email,
            ])
            ->all();
    }

    private function shouldSendNotifications(MembershipApplication $application): bool
    {
        return $application->source === 'mobile';
    }

    private function generateApplicationNumber(): string
    {
        return 'APP-' . now()->format('Y') . '-' . Str::upper(Str::random(6));
    }
}
