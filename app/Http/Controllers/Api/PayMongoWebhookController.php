<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MembershipApplication;
use App\Models\PaymentAssessment;
use App\Models\PayMongoWebhookEvent;
use App\Models\RenewalRequest;
use App\Services\Audit\AuditTrailService;
use App\Services\Membership\FeeCalculatorService;
use App\Services\Membership\MembershipApplicationService;
use App\Services\Membership\RenewalRequestService;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class PayMongoWebhookController extends Controller
{
    private const SUPPORTED_EVENTS = [
        'checkout_session.payment.paid',
        'payment.paid',
    ];

    public function __construct(
        private readonly MembershipApplicationService $membershipApplicationService,
        private readonly RenewalRequestService $renewalRequestService,
        private readonly FeeCalculatorService $feeCalculatorService,
        private readonly AuditTrailService $auditTrailService,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $this->normalizeIncomingPayload($request->json()->all());

        if (! is_array($payload) || $payload === []) {
            Log::warning('PayMongo webhook rejected: empty or invalid JSON payload.');

            return response()->json([
                'message' => 'Invalid webhook payload.',
            ], 400);
        }

        $eventType = (string) data_get($payload, 'data.attributes.type', '');
        $eventId = (string) data_get($payload, 'data.id', '');

        Log::info('PayMongo webhook received.', [
            'event_id' => $eventId,
            'event_type' => $eventType,
        ]);

        $webhookEvent = $this->storeWebhookEvent($payload, $eventType);

        if (! in_array($eventType, self::SUPPORTED_EVENTS, true)) {
            $this->markWebhookEvent($webhookEvent, [
                'status' => 'ignored',
                'message' => 'Unsupported PayMongo event type.',
            ]);

            $this->auditTrailService->record(
                'payments',
                'payment_webhook_ignored',
                'Ignored an unsupported PayMongo webhook event.',
                null,
                null,
                ['event_type' => $eventType]
            );

            Log::info('PayMongo webhook ignored: unsupported event type.', [
                'event_id' => $eventId,
                'event_type' => $eventType,
            ]);

            return response()->json([
                'status' => 'ignored',
                'event_type' => $eventType,
            ]);
        }

        try {
            $handled = DB::transaction(fn (): array => $this->handlePaidEvent($eventType, $payload));
            $this->markWebhookEvent($webhookEvent, $handled['event']);
            $this->recordAuditWebhookResult($handled['event'], $eventType);
        } catch (DomainException | InvalidArgumentException | ModelNotFoundException $exception) {
            $this->markWebhookEvent($webhookEvent, [
                'status' => 'rejected',
                'message' => $exception->getMessage(),
            ]);

            $this->auditTrailService->record(
                'payments',
                'payment_webhook_rejected',
                'Rejected a PayMongo webhook event.',
                null,
                null,
                [
                    'event_type' => $eventType,
                    'message' => $exception->getMessage(),
                ]
            );

            Log::warning('PayMongo webhook could not be applied.', [
                'event_type' => $eventType,
                'message' => $exception->getMessage(),
                'event_id' => $eventId,
            ]);

            return response()->json([
                'status' => 'rejected',
                'message' => $exception->getMessage(),
            ], 422);
        }

        Log::info('PayMongo webhook handled successfully.', [
            'event_id' => $eventId,
            'event_type' => $eventType,
            'result' => $handled['response']['status'] ?? null,
            'source' => $handled['response']['source'] ?? null,
            'source_id' => $handled['response']['source_id'] ?? null,
            'payment_reference' => $handled['response']['payment_reference'] ?? null,
        ]);

        return response()->json($handled['response']);
    }

    private function normalizeIncomingPayload(mixed $payload): array
    {
        if (! is_array($payload) || $payload === []) {
            return [];
        }

        if (is_array(data_get($payload, 'data.attributes.data'))) {
            return $payload;
        }

        if (($payload['type'] ?? null) !== 'payment') {
            return $payload;
        }

        $attributes = is_array($payload['attributes'] ?? null) ? $payload['attributes'] : [];

        if (strtolower((string) ($attributes['status'] ?? '')) !== 'paid') {
            return $payload;
        }

        return [
            'data' => [
                'id' => (string) ($payload['id'] ?? ''),
                'type' => 'event',
                'attributes' => [
                    'type' => 'payment.paid',
                    'livemode' => (bool) ($attributes['livemode'] ?? false),
                    'data' => $payload,
                ],
            ],
        ];
    }

    private function handlePaidEvent(string $eventType, array $payload): array
    {
        $resource = data_get($payload, 'data.attributes.data');

        if (! is_array($resource)) {
            throw new InvalidArgumentException('Webhook resource data is missing.');
        }

        $metadata = $this->extractMetadata($eventType, $resource);
        $candidateReferences = $this->candidateReferences($eventType, $resource, $metadata);
        $target = $this->resolveTarget($metadata, $candidateReferences, $resource);
        $assessment = $target['assessment'];

        if ($assessment !== null) {
            $assessment = PaymentAssessment::query()
                ->lockForUpdate()
                ->findOrFail($assessment->id);
            $target['assessment'] = $assessment;
        }

        $referenceNo = $this->resolveStoredReference($candidateReferences);
        $amountPaid = $this->resolveAmountPaid($eventType, $resource, $assessment);
        $baseEvent = $this->eventAttributes($target, $assessment, $referenceNo, $amountPaid);

        if ($assessment && $candidateReferences !== [] && $assessment->payments()->whereIn('reference_no', $candidateReferences)->exists()) {
            return [
                'response' => [
                    'status' => 'ignored',
                    'reason' => 'duplicate_payment',
                    'source' => $target['source'],
                    'source_id' => $target['source_id'],
                ],
                'event' => array_merge($baseEvent, [
                    'status' => 'ignored',
                    'message' => 'Duplicate payment reference received from PayMongo.',
                ]),
            ];
        }

        if ($assessment && $assessment->status?->isSettled()) {
            return [
                'response' => [
                    'status' => 'ignored',
                    'reason' => 'assessment_already_settled',
                    'source' => $target['source'],
                    'source_id' => $target['source_id'],
                ],
                'event' => array_merge($baseEvent, [
                    'status' => 'ignored',
                    'message' => 'Payment assessment is already settled.',
                ]),
            ];
        }

        $attributes = [
            'payment_method' => $this->resolveRecordedPaymentMethod($eventType, $resource, $metadata),
            'reference_no' => $referenceNo,
            'amount_paid' => $amountPaid,
            'paid_at' => $this->resolvePaidAt($eventType, $resource),
            'receipt_no' => $this->resolveReceiptNumber($eventType, $resource),
        ];

        if ($target['source'] === 'membership_application') {
            $result = $this->membershipApplicationService->recordPayment($target['model'], $attributes);

            return [
                'response' => [
                    'status' => 'processed',
                    'source' => 'membership_application',
                    'source_id' => $target['model']->id,
                    'assessment_status' => $result['summary']['assessment_status']->value,
                    'payment_reference' => $result['payment']->reference_no,
                ],
                'event' => array_merge($baseEvent, [
                    'status' => 'processed',
                    'message' => 'Payment posted successfully.',
                    'payment_assessment_id' => $result['assessment']->id,
                    'membership_application_id' => $target['model']->id,
                    'payment_id' => $result['payment']->id,
                ]),
            ];
        }

        $result = $this->renewalRequestService->recordPayment($target['model'], $attributes);

        return [
            'response' => [
                'status' => 'processed',
                'source' => 'renewal_request',
                'source_id' => $target['model']->id,
                'assessment_status' => $result['summary']['assessment_status']->value,
                'payment_reference' => $result['payment']->reference_no,
            ],
            'event' => array_merge($baseEvent, [
                'status' => 'processed',
                'message' => 'Payment posted successfully.',
                'payment_assessment_id' => $result['assessment']->id,
                'renewal_request_id' => $target['model']->id,
                'payment_id' => $result['payment']->id,
            ]),
        ];
    }

    private function eventAttributes(array $target, ?PaymentAssessment $assessment, ?string $referenceNo, float $amountPaid): array
    {
        return [
            'reference_no' => $referenceNo,
            'amount' => $amountPaid,
            'payment_assessment_id' => $assessment?->id,
            'membership_application_id' => $target['source'] === 'membership_application' ? $target['source_id'] : null,
            'renewal_request_id' => $target['source'] === 'renewal_request' ? $target['source_id'] : null,
        ];
    }

    private function storeWebhookEvent(array $payload, string $eventType): PayMongoWebhookEvent
    {
        $resource = is_array(data_get($payload, 'data.attributes.data')) ? data_get($payload, 'data.attributes.data') : [];

        return PayMongoWebhookEvent::query()->create([
            'paymongo_event_id' => data_get($payload, 'data.id'),
            'event_type' => $eventType !== '' ? $eventType : null,
            'resource_id' => data_get($resource, 'id'),
            'resource_type' => data_get($resource, 'type'),
            'livemode' => (bool) data_get($payload, 'data.attributes.livemode', false),
            'status' => 'received',
            'payload' => $payload,
        ]);
    }

    private function markWebhookEvent(PayMongoWebhookEvent $webhookEvent, array $attributes): void
    {
        $webhookEvent->forceFill(array_merge($attributes, [
            'processed_at' => now(),
        ]))->save();
    }

    private function recordAuditWebhookResult(array $event, string $eventType): void
    {
        $subject = null;

        if (filled($event['membership_application_id'] ?? null)) {
            $subject = MembershipApplication::query()->find($event['membership_application_id']);
        } elseif (filled($event['renewal_request_id'] ?? null)) {
            $subject = RenewalRequest::query()->find($event['renewal_request_id']);
        }

        $this->auditTrailService->record(
            'payments',
            'payment_webhook_processed',
            ($event['status'] ?? null) === 'processed'
                ? 'Processed a PayMongo payment confirmation.'
                : 'Handled a PayMongo payment confirmation.',
            null,
            $subject,
            [
                'event_type' => $eventType,
                'status' => $event['status'] ?? null,
                'reference_no' => $event['reference_no'] ?? null,
                'amount' => $event['amount'] ?? null,
                'message' => $event['message'] ?? null,
            ]
        );
    }

    private function resolveTarget(array $metadata, array $candidateReferences = [], array $resource = []): array
    {
        $assessmentId = $this->integerValue($metadata['assessment_id'] ?? null);
        if ($assessmentId !== null) {
            $assessment = PaymentAssessment::query()
                ->with([
                    'payments',
                    'membershipApplication.documents',
                    'membershipApplication.farmer.memberType',
                    'membershipApplication.farmer.users:id,farmer_id',
                    'renewalRequest.documents',
                    'renewalRequest.farmer.memberType',
                    'renewalRequest.farmer.users:id,farmer_id',
                ])
                ->findOrFail($assessmentId);

            if ($assessment->membershipApplication) {
                return [
                    'source' => 'membership_application',
                    'source_id' => $assessment->membershipApplication->id,
                    'model' => $assessment->membershipApplication,
                    'assessment' => $assessment,
                ];
            }

            if ($assessment->renewalRequest) {
                return [
                    'source' => 'renewal_request',
                    'source_id' => $assessment->renewalRequest->id,
                    'model' => $assessment->renewalRequest,
                    'assessment' => $assessment,
                ];
            }

            throw new InvalidArgumentException('Payment assessment is not linked to a payable source.');
        }

        if ($metadata !== []) {
            [$sourceType, $sourceId] = $this->resolveSourceMetadata($metadata);

            return $this->loadTargetBySource($sourceType, $sourceId);
        }

        foreach ($candidateReferences as $reference) {
            $target = $this->resolveTargetFromReference($reference);

            if ($target !== null) {
                return $target;
            }
        }

        if (($target = $this->resolveTargetFromBillingEmail($resource)) !== null) {
            return $target;
        }

        throw new InvalidArgumentException('Webhook metadata is missing a payable source identifier.');
    }

    private function loadTargetBySource(string $sourceType, int $sourceId): array
    {
        if ($sourceType === 'membership_application') {
            $application = MembershipApplication::query()
                ->with([
                    'documents',
                    'farmer.memberType',
                    'farmer.users:id,farmer_id',
                    'paymentAssessments.payments',
                ])
                ->findOrFail($sourceId);

            return [
                'source' => 'membership_application',
                'source_id' => $application->id,
                'model' => $application,
                'assessment' => $application->paymentAssessments->sortByDesc('id')->first(),
            ];
        }

        $renewal = RenewalRequest::query()
            ->with([
                'documents',
                'farmer.memberType',
                'farmer.users:id,farmer_id',
                'paymentAssessments.payments',
            ])
            ->findOrFail($sourceId);

        return [
            'source' => 'renewal_request',
            'source_id' => $renewal->id,
            'model' => $renewal,
            'assessment' => $renewal->paymentAssessments->sortByDesc('id')->first(),
        ];
    }

    private function resolveTargetFromReference(string $reference): ?array
    {
        if (preg_match('/^APP-\d{4}-[A-Z0-9]+$/i', $reference) === 1) {
            $application = MembershipApplication::query()
                ->with([
                    'documents',
                    'farmer.memberType',
                    'farmer.users:id,farmer_id',
                    'paymentAssessments.payments',
                ])
                ->where('application_no', $reference)
                ->first();

            if ($application) {
                return [
                    'source' => 'membership_application',
                    'source_id' => $application->id,
                    'model' => $application,
                    'assessment' => $application->paymentAssessments->sortByDesc('id')->first(),
                ];
            }
        }

        if (preg_match('/^REN-(\d{4})-(\d+)$/i', $reference, $matches) === 1) {
            $renewalId = (int) $matches[2];

            return $this->loadTargetBySource('renewal_request', $renewalId);
        }

        return null;
    }

    private function resolveSourceMetadata(array $metadata): array
    {
        $applicationId = $this->integerValue($metadata['membership_application_id'] ?? null);
        if ($applicationId !== null) {
            return ['membership_application', $applicationId];
        }

        $renewalId = $this->integerValue($metadata['renewal_request_id'] ?? null);
        if ($renewalId !== null) {
            return ['renewal_request', $renewalId];
        }

        $sourceType = strtolower((string) ($metadata['source_type'] ?? ''));
        $sourceId = $this->integerValue($metadata['source_id'] ?? null);

        if ($sourceId === null) {
            throw new InvalidArgumentException('Webhook metadata is missing a payable source identifier.');
        }

        return match ($sourceType) {
            'application', 'membership_application', 'membership-application' => ['membership_application', $sourceId],
            'renewal', 'renewal_request', 'renewal-request' => ['renewal_request', $sourceId],
            default => throw new InvalidArgumentException('Webhook metadata contains an unsupported source type.'),
        };
    }

    private function extractMetadata(string $eventType, array $resource): array
    {
        $resourceAttributes = is_array($resource['attributes'] ?? null) ? $resource['attributes'] : [];
        $payment = $this->extractPaymentResource($eventType, $resource);
        $paymentAttributes = is_array($payment['attributes'] ?? null) ? $payment['attributes'] : [];
        $intentAttributes = $this->arrayValue(data_get($resourceAttributes, 'payment_intent.attributes'));

        return array_merge(
            is_array($resourceAttributes['metadata'] ?? null) ? $resourceAttributes['metadata'] : [],
            is_array($intentAttributes['metadata'] ?? null) ? $intentAttributes['metadata'] : [],
            is_array($paymentAttributes['metadata'] ?? null) ? $paymentAttributes['metadata'] : [],
        );
    }

    private function extractPaymentResource(string $eventType, array $resource): array
    {
        if ($eventType === 'payment.paid') {
            return $resource;
        }

        $payments = $this->arrayValue(data_get($resource, 'attributes.payments'));
        if ($payments !== []) {
            return $payments[0];
        }

        $intentPayments = $this->arrayValue(data_get($resource, 'attributes.payment_intent.attributes.payments'));
        if ($intentPayments !== []) {
            return $intentPayments[0];
        }

        return [];
    }

    private function resolvePaymentMethod(string $eventType, array $resource): string
    {
        $payment = $this->extractPaymentResource($eventType, $resource);
        $sourceType = strtolower((string) data_get($payment, 'attributes.source.type', ''));

        return match ($sourceType) {
            'gcash' => 'gcash',
            'maya', 'paymaya' => 'maya',
            'qrph' => 'qrph',
            'card' => 'card',
            'bpi', 'dob', 'dob_ubp', 'dob_bpi', 'brankas_bdo', 'brankas_landbank', 'brankas_metrobank', 'bank_transfer' => 'bank_transfer',
            default => 'paymongo',
        };
    }

    private function resolveRecordedPaymentMethod(string $eventType, array $resource, array $metadata): string
    {
        $paymentMethod = strtolower((string) ($metadata['payment_method'] ?? ''));

        if ($paymentMethod !== '') {
            return $paymentMethod;
        }

        return $this->resolvePaymentMethod($eventType, $resource);
    }

    private function resolveAmountPaid(string $eventType, array $resource, ?PaymentAssessment $assessment): float
    {
        $payment = $this->extractPaymentResource($eventType, $resource);
        $amount = data_get($payment, 'attributes.amount');
        $resolvedAmount = null;

        if (is_numeric($amount)) {
            $resolvedAmount = round(((float) $amount) / 100, 2);
        }

        if ($resolvedAmount === null) {
            $resourceAmount = data_get($resource, 'attributes.amount_total', data_get($resource, 'attributes.amount'));
            if (is_numeric($resourceAmount)) {
                $resolvedAmount = round(((float) $resourceAmount) / 100, 2);
            }
        }

        if (
            $assessment !== null
            && $resolvedAmount !== null
            && ($testingAmount = $this->feeCalculatorService->testingChargeAmount()) !== null
            && round($resolvedAmount, 2) === round($testingAmount, 2)
        ) {
            return round((float) $assessment->total_amount_due, 2);
        }

        if ($resolvedAmount !== null) {
            return $resolvedAmount;
        }

        if ($assessment !== null) {
            return round((float) $assessment->total_amount_due, 2);
        }

        throw new InvalidArgumentException('Webhook payload is missing the paid amount.');
    }

    private function resolvePaidAt(string $eventType, array $resource): string
    {
        $payment = $this->extractPaymentResource($eventType, $resource);
        $timestamp = data_get($payment, 'attributes.paid_at')
            ?? data_get($resource, 'attributes.paid_at')
            ?? data_get($resource, 'attributes.updated_at');

        if (is_numeric($timestamp)) {
            return CarbonImmutable::createFromTimestamp((int) $timestamp)->toDateTimeString();
        }

        if (filled($timestamp)) {
            return CarbonImmutable::parse((string) $timestamp)->toDateTimeString();
        }

        return CarbonImmutable::now()->toDateTimeString();
    }

    private function resolveReceiptNumber(string $eventType, array $resource): ?string
    {
        $payment = $this->extractPaymentResource($eventType, $resource);

        $candidates = [
            data_get($payment, 'id'),
            data_get($resource, 'attributes.reference_number'),
            data_get($resource, 'id'),
        ];

        foreach ($candidates as $candidate) {
            if (filled($candidate)) {
                return (string) $candidate;
            }
        }

        return null;
    }

    private function candidateReferences(string $eventType, array $resource, array $metadata): array
    {
        $payment = $this->extractPaymentResource($eventType, $resource);

        return collect([
            $metadata['reference_no'] ?? null,
            $metadata['reference_number'] ?? null,
            data_get($payment, 'attributes.external_reference_number'),
            data_get($resource, 'attributes.reference_number'),
            data_get($payment, 'id'),
            data_get($resource, 'id'),
            ...$this->extractReferenceCandidates((string) data_get($payment, 'attributes.description')),
            ...$this->extractReferenceCandidates((string) data_get($resource, 'attributes.description')),
        ])
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => (string) $value)
            ->unique()
            ->values()
            ->all();
    }

    private function extractReferenceCandidates(string $value): array
    {
        if ($value === '') {
            return [];
        }

        preg_match_all('/\b(APP-\d{4}-[A-Z0-9]+|REN-\d{4}-\d+)\b/i', $value, $matches);

        return $matches[1] ?? [];
    }

    private function resolveStoredReference(array $candidateReferences): ?string
    {
        return $candidateReferences[0] ?? null;
    }

    private function resolveTargetFromBillingEmail(array $resource): ?array
    {
        $email = strtolower(trim((string) data_get($resource, 'attributes.billing.email', '')));

        if ($email === '') {
            return null;
        }

        $renewals = RenewalRequest::query()
            ->with([
                'documents',
                'farmer.memberType',
                'farmer.users:id,farmer_id,email',
                'paymentAssessments.payments',
            ])
            ->whereHas('farmer.users', fn ($query) => $query->whereRaw('LOWER(email) = ?', [$email]))
            ->get()
            ->filter(function (RenewalRequest $renewal): bool {
                $assessment = $renewal->paymentAssessments->sortByDesc('id')->first();

                return ! $assessment || ! $assessment->status?->isSettled();
            })
            ->values();

        if ($renewals->count() !== 1) {
            return null;
        }

        $renewal = $renewals->first();

        return [
            'source' => 'renewal_request',
            'source_id' => $renewal->id,
            'model' => $renewal,
            'assessment' => $renewal->paymentAssessments->sortByDesc('id')->first(),
        ];
    }

    private function integerValue(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    private function arrayValue(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }
}
