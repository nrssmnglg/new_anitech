<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PaymentGatewayService
{
    public function usesHostedCheckout(): bool
    {
        return filled(config('services.paymongo.secret_key'));
    }

    public function createCheckout(array|object $payload): array
    {
        $data = $this->normalize($payload);
        $reference = $this->resolveReference($data);
        $amount = round((float) ($data['amount'] ?? $data['amount_due'] ?? 0), 2);

        if (! $this->usesHostedCheckout()) {
            return [
                'reference_no' => $reference,
                'provider' => $data['provider'] ?? 'manual',
                'amount' => $amount,
                'currency' => $data['currency'] ?? 'PHP',
                'payment_status' => PaymentStatus::PENDING->value,
                'checkout_url' => $data['checkout_url'] ?? null,
                'metadata' => $data['metadata'] ?? [],
            ];
        }

        $payload = [
            'data' => [
                'attributes' => array_filter([
                    'cancel_url' => $data['cancel_url'] ?? null,
                    'description' => $data['description'] ?? null,
                    'line_items' => [[
                        'amount' => $this->toCentavos($amount),
                        'currency' => $data['currency'] ?? 'PHP',
                        'description' => $data['line_item_description'] ?? ($data['description'] ?? null),
                        'name' => $data['line_item_name'] ?? 'AniTech Membership Payment',
                        'quantity' => 1,
                    ]],
                    'metadata' => array_merge($data['metadata'] ?? [], [
                        'reference_no' => $reference,
                    ]),
                    'payment_method_types' => $this->resolvePaymentMethodTypes((string) ($data['payment_method'] ?? 'gcash')),
                    'reference_number' => $reference,
                    'send_email_receipt' => false,
                    'show_description' => true,
                    'show_line_items' => true,
                    'success_url' => $data['success_url'] ?? null,
                ], fn (mixed $value): bool => $value !== null),
            ],
        ];

        $response = Http::withBasicAuth((string) config('services.paymongo.secret_key'), '')
            ->acceptJson()
            ->asJson()
            ->post('https://api.paymongo.com/v1/checkout_sessions', $payload);

        if ($response->failed()) {
            throw new DomainException($this->resolveGatewayErrorMessage($response->json()));
        }

        $checkoutPayload = $response->json();
        $checkoutUrl = data_get($checkoutPayload, 'data.attributes.checkout_url');

        if (! filled($checkoutUrl)) {
            throw new DomainException('PayMongo did not return a checkout URL.');
        }

        return [
            'reference_no' => $reference,
            'provider' => 'paymongo',
            'checkout_id' => data_get($checkoutPayload, 'data.id'),
            'amount' => $amount,
            'currency' => $data['currency'] ?? 'PHP',
            'payment_status' => PaymentStatus::PENDING->value,
            'checkout_url' => (string) $checkoutUrl,
            'metadata' => data_get($checkoutPayload, 'data.attributes.metadata', $data['metadata'] ?? []),
        ];
    }

    public function createQrPhPayment(array|object $payload): array
    {
        if (! $this->usesHostedCheckout()) {
            throw new DomainException('PayMongo QR Ph is not configured.');
        }

        $data = $this->normalize($payload);
        $reference = $this->resolveReference($data);
        $amount = round((float) ($data['amount'] ?? $data['amount_due'] ?? 0), 2);
        $billing = $this->normalize($data['billing'] ?? []);
        $name = trim((string) ($billing['name'] ?? ''));
        $email = trim((string) ($billing['email'] ?? ''));
        $phone = trim((string) ($billing['phone'] ?? ''));
        $expirySeconds = $this->resolveQrPhExpirySeconds($data['expiry_seconds'] ?? null);

        if ($name === '' || $email === '') {
            throw new DomainException('QR Ph payments require the member name and email address.');
        }

        $paymentIntentResponse = $this->payMongoRequest('https://api.paymongo.com/v1/payment_intents', [
            'data' => [
                'attributes' => array_filter([
                    'amount' => $this->toCentavos($amount),
                    'capture_type' => 'automatic',
                    'currency' => $data['currency'] ?? 'PHP',
                    'description' => $data['description'] ?? null,
                    'payment_method_allowed' => ['qrph'],
                ], fn (mixed $value): bool => $value !== null),
            ],
        ]);

        $paymentIntentId = data_get($paymentIntentResponse, 'data.id');

        if (! filled($paymentIntentId)) {
            throw new DomainException('PayMongo did not return a payment intent.');
        }

        $paymentMethodResponse = $this->payMongoRequest('https://api.paymongo.com/v1/payment_methods', [
            'data' => [
                'attributes' => array_filter([
                    'billing' => array_filter([
                        'email' => $email,
                        'name' => $name,
                        'phone' => $phone !== '' ? $phone : null,
                    ], fn (mixed $value): bool => $value !== null),
                    'expiry_seconds' => $expirySeconds,
                    'type' => 'qrph',
                ], fn (mixed $value): bool => $value !== null),
            ],
        ]);

        $paymentMethodId = data_get($paymentMethodResponse, 'data.id');

        if (! filled($paymentMethodId)) {
            throw new DomainException('PayMongo did not return a QR Ph payment method.');
        }

        $attachResponse = $this->payMongoRequest('https://api.paymongo.com/v1/payment_intents/' . $paymentIntentId . '/attach', [
            'data' => [
                'attributes' => [
                    'payment_method' => $paymentMethodId,
                ],
            ],
        ]);

        $imageUrl = data_get($attachResponse, 'data.attributes.next_action.code.image_url');
        if (! filled($imageUrl)) {
            throw new DomainException('PayMongo did not return a QR Ph image.');
        }

        return [
            'reference_no' => $reference,
            'provider' => 'paymongo',
            'payment_intent_id' => (string) $paymentIntentId,
            'payment_method_id' => (string) $paymentMethodId,
            'amount' => $amount,
            'currency' => $data['currency'] ?? 'PHP',
            'payment_status' => PaymentStatus::PENDING->value,
            'qr_image_url' => (string) $imageUrl,
            'qr_code_id' => data_get($attachResponse, 'data.attributes.next_action.code.id'),
            'qr_label' => data_get($attachResponse, 'data.attributes.next_action.code.label'),
            'expires_at' => CarbonImmutable::now()->addSeconds($expirySeconds)->toIso8601String(),
            'metadata' => data_get($attachResponse, 'data.attributes.metadata', $data['metadata'] ?? []),
        ];
    }

    public function handleCallback(array|object $transaction, array|object $callbackPayload): array
    {
        $current = $this->normalize($transaction);
        $payload = $this->normalize($callbackPayload);
        $status = $this->resolveGatewayStatus($payload['status'] ?? $payload['payment_status'] ?? null);

        return array_merge($current, [
            'gateway_reference' => $payload['gateway_reference'] ?? $payload['reference_no'] ?? ($current['gateway_reference'] ?? null),
            'payment_status' => $status->value,
            'paid_at' => $status->isSettled() ? CarbonImmutable::now()->toDateTimeString() : ($current['paid_at'] ?? null),
            'callback_payload' => $payload,
        ]);
    }

    public function verify(array|object $transaction): bool
    {
        $data = $this->normalize($transaction);
        $status = $this->resolveGatewayStatus($data['payment_status'] ?? $data['status'] ?? PaymentStatus::PENDING->value);

        return in_array($status, [PaymentStatus::VERIFIED, PaymentStatus::PAID, PaymentStatus::OVERPAID], true);
    }

    private function resolveGatewayStatus(mixed $status): PaymentStatus
    {
        if ($status instanceof PaymentStatus) {
            return $status;
        }

        $value = strtolower((string) ($status ?? PaymentStatus::PENDING->value));

        return match ($value) {
            'success', 'successful', 'verified' => PaymentStatus::VERIFIED,
            'paid' => PaymentStatus::PAID,
            'overpaid' => PaymentStatus::OVERPAID,
            'rejected', 'failed' => PaymentStatus::REJECTED,
            'cancelled', 'canceled' => PaymentStatus::CANCELLED,
            default => PaymentStatus::PENDING,
        };
    }

    private function normalize(array|object $payload): array
    {
        if (is_array($payload)) {
            return $payload;
        }

        if (method_exists($payload, 'toArray')) {
            return $payload->toArray();
        }

        return get_object_vars($payload);
    }

    private function resolveReference(array $payload): string
    {
        $reference = trim((string) ($payload['reference_no'] ?? ''));

        if ($reference !== '') {
            return $reference;
        }

        return 'PAY-' . CarbonImmutable::now()->format('YmdHis') . '-' . Str::upper(Str::random(6));
    }

    private function resolvePaymentMethodTypes(string $paymentMethod): array
    {
        return match ($paymentMethod) {
            'gcash' => ['gcash'],
            'maya' => ['paymaya'],
            'card' => ['card'],
            'bank_transfer' => ['dob'],
            default => throw new DomainException('Unsupported payment method for PayMongo checkout.'),
        };
    }

    private function resolveGatewayErrorMessage(mixed $payload): string
    {
        $errors = data_get($payload, 'errors');

        if (is_array($errors) && isset($errors[0])) {
            $detail = data_get($errors[0], 'detail');
            $code = data_get($errors[0], 'code');

            if (filled($detail) && filled($code)) {
                return $detail . ' (' . $code . ')';
            }

            if (filled($detail)) {
                return (string) $detail;
            }
        }

        return 'Unable to create a PayMongo checkout session.';
    }

    private function payMongoRequest(string $url, array $payload): array
    {
        $response = Http::withBasicAuth((string) config('services.paymongo.secret_key'), '')
            ->acceptJson()
            ->asJson()
            ->post($url, $payload);

        if ($response->failed()) {
            throw new DomainException($this->resolveGatewayErrorMessage($response->json()));
        }

        return $response->json();
    }

    private function resolveQrPhExpirySeconds(mixed $value): int
    {
        $seconds = is_numeric($value) ? (int) $value : 1800;

        return max(60, min(9000, $seconds));
    }

    private function toCentavos(float $amount): int
    {
        return (int) round($amount * 100);
    }
}
