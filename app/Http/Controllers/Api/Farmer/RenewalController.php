<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Enums\AssessmentStatus;
use App\Http\Requests\Api\StoreRenewalPaymentRequest;
use App\Http\Requests\Api\SubmitRenewalRequest;
use App\Http\Resources\Farmer\RenewalResource;
use App\Models\RenewalRequest;
use App\Services\Farmer\FarmerRenewalService;
use App\Services\Membership\FeeCalculatorService;
use App\Services\Payments\PaymentAssessmentService;
use App\Services\Payments\PaymentGatewayService;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RenewalController extends FarmerApiController
{
    public function __construct(
        private readonly FarmerRenewalService $renewalService,
        private readonly PaymentAssessmentService $paymentAssessmentService,
        private readonly PaymentGatewayService $paymentGatewayService,
        private readonly FeeCalculatorService $feeCalculatorService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $farmer = $request->user('farmer_pwa')->farmer;
        $renewals = $this->renewalService->list($farmer, (int) $request->integer('per_page', 10));

        return $this->paginated($renewals, RenewalResource::collection($renewals), [
            'year' => $request->integer('year'),
            'eligibility' => $this->renewalService->eligibility($farmer, $request->integer('year')),
        ]);
    }

    public function eligibility(Request $request): JsonResponse
    {
        return $this->success($this->renewalService->eligibility(
            $request->user('farmer_pwa')->farmer,
            $request->integer('year'),
        ));
    }

    public function startOrResume(SubmitRenewalRequest $request): JsonResponse
    {
        try {
            $renewal = $this->renewalService->startOrResume(
                $request->user('farmer_pwa')->farmer,
                $request->validated('year') ? (int) $request->validated('year') : null,
            );
        } catch (DomainException $exception) {
            throw ValidationException::withMessages([
                'renewal' => $exception->getMessage(),
            ]);
        }

        return $this->success(
            new RenewalResource($renewal),
            [],
            'Renewal is ready. Continue to payment for this year.'
        );
    }

    public function pay(StoreRenewalPaymentRequest $request, RenewalRequest $renewal): JsonResponse
    {
        $farmer = $request->user('farmer_pwa')->farmer;

        if ((int) $renewal->farmer_id !== (int) $farmer->id) {
            abort(404);
        }

        $renewal->loadMissing(['farmer.profile', 'farmer.memberType', 'paymentAssessments']);

        $assessment = $this->resolveRenewalAssessment($renewal);

        if (in_array($assessment->status?->value, [AssessmentStatus::PAID->value, AssessmentStatus::OVERPAID->value, AssessmentStatus::WAIVED->value], true)) {
            throw ValidationException::withMessages([
                'payment' => 'Payment is already recorded for this renewal.',
            ]);
        }

        try {
            $qrPayment = $this->createRenewalQrPayment(
                $renewal,
                $assessment,
                (string) ($request->validated('reference_no') ?: $this->renewalReference($renewal)),
            );
        } catch (DomainException $exception) {
            throw ValidationException::withMessages([
                'payment' => $exception->getMessage(),
            ]);
        }

        $request->session()->put($this->renewalQrSessionKey($renewal), $qrPayment);
        Cache::put(
            $this->renewalQrCacheKey($renewal, $farmer->id),
            $qrPayment,
            CarbonImmutable::now()->addMinutes(35),
        );

        return $this->success([
            'renewal' => new RenewalResource($renewal->refresh()->load(['documents.documentType', 'paymentAssessments.payments', 'paymentAssessments.feeSchedule'])),
            'provider' => $qrPayment['provider'],
            'qr_page_url' => url('/farmer/app/payment/qr?' . http_build_query([
                'transaction' => 'renewal',
                'renewal_id' => $renewal->getRouteKey(),
            ])),
            'qr_image_url' => $qrPayment['qr_image_url'],
            'payment_reference' => $qrPayment['reference_no'],
        ], [], 'QR Ph code generated successfully.');
    }

    public function qrPage(Request $request, RenewalRequest $renewal): View|RedirectResponse|JsonResponse
    {
        $farmer = $request->user('farmer_pwa')->farmer;

        if ((int) $renewal->farmer_id !== (int) $farmer->id) {
            abort(404);
        }

        $renewal->loadMissing(['farmer.profile', 'farmer.memberType', 'paymentAssessments']);
        $assessment = $this->resolveRenewalAssessment($renewal);

        if ($assessment && in_array($assessment->status?->value, [AssessmentStatus::PAID->value, AssessmentStatus::OVERPAID->value, AssessmentStatus::WAIVED->value], true)) {
            $request->session()->forget($this->renewalQrSessionKey($renewal));
            Cache::forget($this->renewalQrCacheKey($renewal, $farmer->id));

            return response()->json([
                'message' => 'Payment recorded successfully.',
                'data' => [
                    'redirect_url' => url('/farmer/app/payments'),
                ],
            ]);
        }

        $qrPayment = $request->session()->get($this->renewalQrSessionKey($renewal))
            ?? Cache::get($this->renewalQrCacheKey($renewal, $farmer->id));

        if (! is_array($qrPayment) || ! filled($qrPayment['qr_image_url'] ?? null)) {
            try {
                $qrPayment = $this->createRenewalQrPayment($renewal, $assessment, (string) $renewal->application_no);
            } catch (DomainException $exception) {
                throw ValidationException::withMessages([
                    'payment' => $exception->getMessage(),
                ]);
            }

            $request->session()->put($this->renewalQrSessionKey($renewal), $qrPayment);
            Cache::put(
                $this->renewalQrCacheKey($renewal, $farmer->id),
                $qrPayment,
                CarbonImmutable::now()->addMinutes(35),
            );
        }

        if (! $this->qrAmountMatchesAssessment($qrPayment, $assessment?->total_amount_due)) {
            $request->session()->forget($this->renewalQrSessionKey($renewal));
            Cache::forget($this->renewalQrCacheKey($renewal, $farmer->id));

            return response()->json([
                'message' => 'The previous QR code no longer matches the current payment amount. Generate a new QR code before paying.',
                'data' => [
                    'redirect_url' => url('/farmer/app/payments'),
                ],
            ], 409);
        }

        $expiresAt = filled($qrPayment['expires_at'] ?? null)
            ? CarbonImmutable::parse((string) $qrPayment['expires_at'])
            : null;

        return response()->json([
            'data' => [
                'renewal_id' => $renewal->getRouteKey(),
                'reference_no' => $qrPayment['reference_no'] ?? $this->renewalReference($renewal),
                'assessment_status' => $assessment?->status?->value ?? 'pending',
                'assessment_status_label' => $assessment?->status?->label() ?? 'Pending',
                'amount_due' => (float) ($assessment?->total_amount_due ?? ($qrPayment['amount'] ?? 0)),
                'expires_at' => $expiresAt?->toIso8601String(),
                'is_expired' => $expiresAt?->isPast() ?? false,
                'qr_image_url' => $qrPayment['qr_image_url'],
                'breakdown' => [
                    'membership_fee' => $assessment?->membership_fee !== null ? (float) $assessment->membership_fee : null,
                    'annual_due' => $assessment?->annual_due !== null ? (float) $assessment->annual_due : null,
                    'mortuary_fee' => $assessment?->mortuary_fee !== null ? (float) $assessment->mortuary_fee : null,
                ],
                'track_status_url' => url('/farmer/app/payments'),
            ],
        ]);
    }

    private function renewalQrSessionKey(RenewalRequest $renewal): string
    {
        return 'farmer_pwa.renewal_qr_payment.' . $renewal->getRouteKey();
    }

    private function renewalQrCacheKey(RenewalRequest $renewal, int $farmerId): string
    {
        return 'farmer_pwa.renewal_qr_payment_cache.' . $farmerId . '.' . $renewal->getRouteKey();
    }

    private function resolveRenewalAssessment(RenewalRequest $renewal): mixed
    {
        return $this->feeCalculatorService->usesTestingChargeAmount()
            ? $this->paymentAssessmentService->createForRenewal($renewal)
            : ($renewal->paymentAssessments->sortByDesc('id')->first()
                ?? $this->paymentAssessmentService->createForRenewal($renewal));
    }

    private function createRenewalQrPayment(RenewalRequest $renewal, mixed $assessment, string $referenceNo): array
    {
        $billingEmail = trim((string) (($renewal->farmer?->users()->value('email')) ?: ('farmer-' . $renewal->farmer_id . '@anitech.local')));

        return $this->paymentGatewayService->createQrPhPayment([
            'payment_method' => 'qrph',
            'reference_no' => $referenceNo,
            'amount' => $this->gatewayChargeAmount($assessment->total_amount_due),
            'currency' => 'PHP',
            'description' => 'Membership renewal payment for ' . $this->renewalReference($renewal),
            'line_item_name' => 'AniTech Membership Renewal',
            'line_item_description' => 'Membership renewal payment for ' . $this->renewalReference($renewal),
            'expiry_seconds' => 1800,
            'metadata' => [
                'assessment_id' => $assessment->id,
                'payment_method' => 'qrph',
                'renewal_request_id' => $renewal->id,
                'source_type' => 'renewal_request',
                'source_id' => $renewal->id,
                'reference_no' => $referenceNo,
            ],
            'billing' => [
                'name' => trim((string) ($renewal->farmer?->full_name ?: 'AniTech Farmer')),
                'email' => $billingEmail,
                'phone' => trim((string) ($renewal->farmer?->profile?->mobile_number ?? '')),
            ],
        ]);
    }

    private function renewalReference(RenewalRequest $renewal): string
    {
        $reference = trim((string) ($renewal->application_no ?? ''));

        if ($reference !== '') {
            return $reference;
        }

        return 'REN-' . $renewal->year . '-' . $renewal->id;
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
}
