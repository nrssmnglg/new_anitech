<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Resources\Farmer\PaymentResource;
use App\Services\Farmer\FarmerPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PaymentController extends FarmerApiController
{
    public function __construct(
        private readonly FarmerPaymentService $paymentService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $farmer = $request->user('farmer_pwa')->farmer;
        $payments = $this->paymentService->list($farmer, (int) $request->integer('per_page', 10));

        return $this->paginated($payments, PaymentResource::collection($payments), [
            'summary' => $this->paymentService->summary($farmer),
        ]);
    }

    public function uploadProof(Request $request, string $payment): JsonResponse
    {
        $request->validate([
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ]);

        $record = $this->paymentService->uploadProof(
            $request->user('farmer_pwa')->farmer,
            (int) $payment,
            $request->file('proof'),
        );

        return $this->success(new PaymentResource($record), [], 'Payment proof uploaded successfully.');
    }
}
