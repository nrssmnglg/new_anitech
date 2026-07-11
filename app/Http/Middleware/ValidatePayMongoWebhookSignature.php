<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ValidatePayMongoWebhookSignature
{
    public function handle(Request $request, Closure $next): mixed
    {
        $secret = (string) config('services.paymongo.webhook_secret', '');
        $payload = $request->getContent();
        $decodedPayload = json_decode($payload, true);
        $eventId = data_get($decodedPayload, 'data.id');
        $eventType = data_get($decodedPayload, 'data.attributes.type');

        if ($secret === '') {
            Log::info('PayMongo webhook signature skipped because no secret is configured.', [
                'event_id' => $eventId,
                'event_type' => $eventType,
            ]);

            return $next($request);
        }

        $header = trim((string) $request->header('Paymongo-Signature', ''));
        if ($header === '') {
            Log::warning('PayMongo webhook rejected: missing signature header.', [
                'event_id' => $eventId,
                'event_type' => $eventType,
            ]);

            return $this->unauthorizedResponse();
        }

        $parts = collect(explode(',', $header))
            ->map(fn (string $segment): string => trim($segment))
            ->filter()
            ->mapWithKeys(function (string $segment): array {
                [$key, $value] = array_pad(explode('=', $segment, 2), 2, null);

                return [trim((string) $key) => trim((string) $value)];
            });

        $timestamp = $parts->get('t');
        if (blank($timestamp)) {
            Log::warning('PayMongo webhook rejected: missing signature timestamp.', [
                'event_id' => $eventId,
                'event_type' => $eventType,
            ]);

            return $this->unauthorizedResponse();
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
        $signatureKey = data_get($decodedPayload, 'data.attributes.livemode') ? 'li' : 'te';
        $received = (string) $parts->get($signatureKey, '');

        if ($received === '' || ! hash_equals($expected, $received)) {
            Log::warning('PayMongo webhook rejected: signature mismatch.', [
                'event_id' => $eventId,
                'event_type' => $eventType,
                'signature_key' => $signatureKey,
                'has_received_signature' => $received !== '',
            ]);

            return $this->unauthorizedResponse();
        }

        Log::info('PayMongo webhook signature validated.', [
            'event_id' => $eventId,
            'event_type' => $eventType,
            'signature_key' => $signatureKey,
        ]);

        return $next($request);
    }

    private function unauthorizedResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'Invalid webhook signature.',
        ], 401);
    }
}
