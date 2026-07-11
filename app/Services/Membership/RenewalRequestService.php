<?php

namespace App\Services\Membership;

use App\Enums\AssessmentStatus;
use App\Enums\NotificationType;
use App\Enums\RenewalStatus;
use App\Models\FarmerDocument;
use App\Models\MembershipLedger;
use App\Models\RenewalRequest;
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

class RenewalRequestService
{
    public function __construct(
        private readonly RenewalWorkflowService $workflow,
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

    public function create(array $attributes): RenewalRequest
    {
        return DB::transaction(function () use ($attributes): RenewalRequest {
            $year = (int) ($attributes['year'] ?? now()->year);
            $farmerId = (int) $attributes['farmer_id'];

            if (RenewalRequest::query()->where('farmer_id', $farmerId)->where('year', $year)->exists()) {
                throw new DomainException('A renewal request already exists for this farmer and year.');
            }

            if ($this->hasSettledApplicationCoverage($farmerId, $year)) {
                throw new DomainException('Your membership for ' . $year . ' is already covered by your paid membership application. Renewal will be available next year.');
            }

            $renewalRequest = RenewalRequest::query()->create([
                'farmer_id' => $farmerId,
                'year' => $year,
                'source' => $attributes['source'],
                'status' => RenewalStatus::APPROVED,
                'submitted_at' => CarbonImmutable::now(),
                'reviewed_at' => CarbonImmutable::now(),
                'is_late' => $this->isLate($year),
            ]);

            $this->farmerDocumentService->ensureRenewalChecklist($renewalRequest);
            $this->paymentAssessmentService->createForRenewal($renewalRequest);
            $this->auditTrailService->recordById(
                'renewals',
                'renewal_created',
                'Created a renewal request.',
                null,
                $renewalRequest,
                [
                    'year' => $renewalRequest->year,
                    'source' => $renewalRequest->source,
                    'farmer_id' => $renewalRequest->farmer_id,
                ]
            );

            return $renewalRequest->refresh()->load(['farmer.memberType', 'documents']);
        });
    }

    public function markDocumentReceived(RenewalRequest $renewalRequest, int $documentId, bool $received, ?int $userId = null): FarmerDocument
    {
        $renewalRequest->loadMissing('documents');
        $document = $renewalRequest->documents->firstWhere('id', $documentId)
            ?? throw new DomainException('Document not found for this renewal request.');

        $document = $this->farmerDocumentService->markReceived($document, $received, $userId);

        if ($renewalRequest->source === 'walk_in' && $received) {
            $document = $this->documentVerificationService->verifyDocument($document, $userId, 'Verified during walk-in renewal intake.');
        }

        $this->auditTrailService->recordById(
            'renewals',
            $received ? 'document_received' : 'document_unreceived',
            $received ? 'Marked a renewal document as received.' : 'Marked a renewal document as not received.',
            $userId,
            $renewalRequest,
            [
                'document_id' => $document->id,
                'document_type' => $document->document_type?->value ?? $document->document_type,
            ]
        );

        return $document;
    }

    public function verifyDocument(RenewalRequest $renewalRequest, int $documentId, ?int $userId = null, ?string $remarks = null): FarmerDocument
    {
        $renewalRequest->loadMissing('documents');
        $document = $renewalRequest->documents->firstWhere('id', $documentId)
            ?? throw new DomainException('Document not found for this renewal request.');

        $document = $this->documentVerificationService->verifyDocument($document, $userId, $remarks);
        $this->auditTrailService->recordById(
            'renewals',
            'document_verified',
            'Verified a renewal document.',
            $userId,
            $renewalRequest,
            [
                'document_id' => $document->id,
                'document_type' => $document->document_type?->value ?? $document->document_type,
                'remarks' => $remarks,
            ]
        );

        return $document;
    }

    public function rejectDocument(RenewalRequest $renewalRequest, int $documentId, ?int $userId = null, ?string $remarks = null): FarmerDocument
    {
        $renewalRequest->loadMissing('documents');
        $document = $renewalRequest->documents->firstWhere('id', $documentId)
            ?? throw new DomainException('Document not found for this renewal request.');

        $document = $this->documentVerificationService->rejectDocument($document, $userId, $remarks);
        $this->auditTrailService->recordById(
            'renewals',
            'document_rejected',
            'Rejected a renewal document.',
            $userId,
            $renewalRequest,
            [
                'document_id' => $document->id,
                'document_type' => $document->document_type?->value ?? $document->document_type,
                'remarks' => $remarks,
            ]
        );

        return $document;
    }

    public function approve(RenewalRequest $renewalRequest, ?int $reviewerId = null): RenewalRequest
    {
        if ($renewalRequest->source === 'walk_in') {
            throw new DomainException('Walk-in renewals are approved automatically when payment is recorded.');
        }

        return DB::transaction(fn (): RenewalRequest => $this->finalizeApproval($renewalRequest, $reviewerId));
    }

    public function reject(RenewalRequest $renewalRequest, string $remarks, ?int $reviewerId = null): RenewalRequest
    {
        if (blank($remarks)) {
            throw new DomainException('Rejection remarks are required.');
        }

        return DB::transaction(function () use ($renewalRequest, $remarks, $reviewerId): RenewalRequest {
            $renewalRequest->loadMissing('farmer');
            $currentStatus = $renewalRequest->status;
            if ($currentStatus === RenewalStatus::SUBMITTED) {
                $currentStatus = $this->workflow->startReview($currentStatus);
            }

            $renewalRequest->forceFill([
                'status' => $this->workflow->reject($currentStatus ?? RenewalStatus::UNDER_REVIEW),
                'reviewed_by' => $reviewerId,
                'reviewed_at' => CarbonImmutable::now(),
                'rejected_at' => CarbonImmutable::now(),
                'rejection_reason' => $remarks,
            ])->save();

            $this->queueRejectedNotification($renewalRequest, $reviewerId);
            $this->auditTrailService->recordById(
                'renewals',
                'renewal_rejected',
                'Rejected a renewal request.',
                $reviewerId,
                $renewalRequest,
                $this->auditTrailService->activityMetadata(
                    AuditTrailService::ACTION_APPROVED,
                    'Approval decision',
                    [
                    'year' => $renewalRequest->year,
                    'remarks' => $remarks,
                    ]
                )
            );

            return $renewalRequest->refresh()->load(['farmer', 'documents']);
        });
    }

    public function recordPayment(RenewalRequest $renewalRequest, array $attributes, ?int $userId = null): array
    {
        return DB::transaction(function () use ($renewalRequest, $attributes, $userId): array {
            $renewalRequest->loadMissing(['farmer.memberType', 'documents']);

            if ($renewalRequest->status === RenewalStatus::REJECTED) {
                throw new DomainException('Rejected renewals cannot accept payment.');
            }

            if (! in_array($renewalRequest->status, [RenewalStatus::SUBMITTED, RenewalStatus::APPROVED, RenewalStatus::COMPLETED], true)) {
                throw new DomainException('This renewal cannot accept payment right now.');
            }

            $assessment = $this->paymentAssessmentService->createForRenewal($renewalRequest);
            $paymentResult = $this->paymentPostingService->record($assessment, $attributes, $userId);
            $summary = $paymentResult['summary'];

            $ledger = MembershipLedger::query()->firstOrNew([
                'membership_transaction_id' => $renewalRequest->id,
                'year' => $renewalRequest->year,
            ]);

            $ledger->fill($this->ledgerService->buildFromSource(
                'renewal',
                [
                    'id' => $renewalRequest->id,
                    'status' => in_array($summary['assessment_status'], [AssessmentStatus::PAID, AssessmentStatus::OVERPAID, AssessmentStatus::WAIVED], true)
                        ? 'Active'
                        : 'Inactive',
                    'member_type' => $renewalRequest->farmer->memberType?->code,
                    'year' => $renewalRequest->year,
                ],
                [
                    'fee_schedule_id' => $assessment->fee_schedule_id,
                    'member_type' => $renewalRequest->farmer->memberType?->code,
                    'mortuary_eligible' => ! in_array($renewalRequest->farmer->memberType?->code, ['NSC', 'OSC'], true),
                ],
                $summary,
            ));
            $ledger->save();

            if (in_array($summary['assessment_status'], [AssessmentStatus::PAID, AssessmentStatus::OVERPAID, AssessmentStatus::WAIVED], true)) {
                $renewalRequest->forceFill([
                    'status' => RenewalStatus::COMPLETED,
                    'reviewed_at' => $renewalRequest->reviewed_at ?? CarbonImmutable::now(),
                ])->save();

                $this->membershipStatusService->activateMembership($renewalRequest->farmer, $renewalRequest->year);
            }

            $this->queuePaymentRecordedNotification(
                $renewalRequest->refresh()->load(['farmer', 'paymentAssessments']),
                round((float) ($attributes['amount_paid'] ?? 0), 2),
                $userId,
            );
            $this->auditTrailService->recordById(
                'renewal_payments',
                'payment_recorded',
                'Recorded a renewal payment.',
                $userId,
                $renewalRequest,
                $this->auditTrailService->activityMetadata(
                    AuditTrailService::ACTION_PROCESSED,
                    'Payment processing',
                    [
                    'year' => $renewalRequest->year,
                    'payment_id' => $paymentResult['payment']->id,
                    'assessment_id' => $assessment->id,
                    'amount_paid' => round((float) ($attributes['amount_paid'] ?? 0), 2),
                    'assessment_status' => $summary['assessment_status'] ?? null,
                    ]
                )
            );

            return [
                'renewal' => $renewalRequest->refresh()->load(['farmer', 'documents']),
                'assessment' => $assessment->refresh(),
                'payment' => $paymentResult['payment'],
                'ledger' => $ledger->refresh(),
                'summary' => $summary,
            ];
        });
    }

    public function uploadMobileDocument(RenewalRequest $renewalRequest, string $documentType, UploadedFile $file): FarmerDocument
    {
        throw new DomainException('Renewal documents are no longer required.');
    }

    private function finalizeApproval(RenewalRequest $renewalRequest, ?int $reviewerId = null): RenewalRequest
    {
        $renewalRequest->loadMissing(['documents', 'farmer.memberType']);

        $currentStatus = $renewalRequest->status;
        if ($currentStatus === RenewalStatus::SUBMITTED) {
            $currentStatus = $this->workflow->startReview($currentStatus);
        }

        $renewalRequest->forceFill([
            'status' => $this->workflow->approve($currentStatus),
            'reviewed_by' => $reviewerId,
            'reviewed_at' => CarbonImmutable::now(),
            'rejected_at' => null,
            'rejection_reason' => null,
        ])->save();

        $assessment = $this->paymentAssessmentService->createForRenewal($renewalRequest);
        $approvedRenewal = $renewalRequest->refresh()->load(['farmer', 'documents', 'paymentAssessments']);

        $this->queueApprovedNotification($approvedRenewal, $assessment->total_amount_due, $reviewerId);
        $this->auditTrailService->recordById(
            'renewals',
            'renewal_approved',
            'Approved a renewal request.',
            $reviewerId,
            $approvedRenewal,
            $this->auditTrailService->activityMetadata(
                AuditTrailService::ACTION_APPROVED,
                'Approval decision',
                [
                'year' => $approvedRenewal->year,
                'assessment_id' => $assessment->id,
                'amount_due' => $assessment->total_amount_due,
                ]
            )
        );

        return $approvedRenewal;
    }

    private function queueSubmittedNotification(RenewalRequest $renewalRequest): void
    {
        if ($renewalRequest->source === 'walk_in') {
            return;
        }

        $recipients = $this->adminRecipients();

        if ($recipients === []) {
            return;
        }

        $renewalRequest->loadMissing('farmer.profile', 'farmer:id,farmer_code');
        $farmerName = $renewalRequest->farmer?->full_name ?: 'A farmer';

        $this->notificationDispatchService->persist(
            NotificationType::RENEWAL_REQUEST_SUBMITTED,
            $recipients,
            [
                'subject' => 'Membership renewed for ' . $renewalRequest->year,
                'message' => 'Renewal for ' . $farmerName . ' has been renewed for this year.',
                'renewal_request_id' => $renewalRequest->id,
                'farmer_id' => $renewalRequest->farmer_id,
                'year' => $renewalRequest->year,
                'source' => $renewalRequest->source,
                'submitted_at' => optional($renewalRequest->submitted_at)->toDateTimeString(),
            ],
        );
    }

    private function queueApprovedNotification(RenewalRequest $renewalRequest, float|int|string|null $amountDue, ?int $reviewerId = null): void
    {
        $recipient = $this->farmerRecipient($renewalRequest);

        if ($recipient === null) {
            return;
        }

        $formattedAmount = 'PHP ' . number_format((float) $amountDue, 2);

        $this->notificationDispatchService->persist(
            NotificationType::RENEWAL_REQUEST_APPROVED,
            [$recipient],
            [
                'subject' => 'Renewal approved',
                'message' => 'Your ' . $renewalRequest->year . ' renewal request was approved. Amount due: ' . $formattedAmount . '.',
                'renewal_request_id' => $renewalRequest->id,
                'farmer_id' => $renewalRequest->farmer_id,
                'year' => $renewalRequest->year,
                'amount_due' => (float) $amountDue,
                'reviewed_at' => optional($renewalRequest->reviewed_at)->toDateTimeString(),
            ],
            $reviewerId,
        );
    }

    private function queueRejectedNotification(RenewalRequest $renewalRequest, ?int $reviewerId = null): void
    {
        $recipient = $this->farmerRecipient($renewalRequest);

        if ($recipient === null) {
            return;
        }

        $this->notificationDispatchService->persist(
            NotificationType::RENEWAL_REQUEST_REJECTED,
            [$recipient],
            [
                'subject' => 'Renewal rejected',
                'message' => 'Your ' . $renewalRequest->year . ' renewal request was rejected. Remarks: ' . ($renewalRequest->rejection_reason ?: 'No remarks provided') . '.',
                'renewal_request_id' => $renewalRequest->id,
                'farmer_id' => $renewalRequest->farmer_id,
                'year' => $renewalRequest->year,
                'remarks' => $renewalRequest->rejection_reason,
                'reviewed_at' => optional($renewalRequest->reviewed_at)->toDateTimeString(),
            ],
            $reviewerId,
        );
    }

    private function queuePaymentRecordedNotification(RenewalRequest $renewalRequest, float $amountPaid, ?int $userId = null): void
    {
        $recipient = $this->farmerRecipient($renewalRequest);

        if ($recipient === null) {
            return;
        }

        $this->notificationDispatchService->persist(
            NotificationType::PAYMENT_RECORDED,
            [$recipient],
            [
                'subject' => 'Renewal payment recorded',
                'message' => 'We recorded your renewal payment of PHP ' . number_format($amountPaid, 2) . ' for ' . $renewalRequest->year . '.',
                'renewal_request_id' => $renewalRequest->id,
                'farmer_id' => $renewalRequest->farmer_id,
                'year' => $renewalRequest->year,
                'amount_paid' => $amountPaid,
                'source_type' => 'renewal_request',
            ],
            $userId,
        );
    }

    private function farmerRecipient(RenewalRequest $renewalRequest): ?array
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

    private function hasSettledApplicationCoverage(int $farmerId, int $year): bool
    {
        return MembershipLedger::query()
            ->whereHas('membershipTransaction', fn ($query) => $query->where('farmer_id', $farmerId))
            ->where('year', $year)
            ->where('payment_status', 'Paid')
            ->exists();
    }

    private function isLate(int $year): bool
    {
        return CarbonImmutable::now()->greaterThan(CarbonImmutable::create($year, 2, 14)->endOfDay());
    }
}
