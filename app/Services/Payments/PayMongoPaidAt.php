<?php

namespace App\Services\Payments;

use Carbon\CarbonImmutable;

class PayMongoPaidAt
{
    public function fromResource(string $eventType, array $resource): CarbonImmutable
    {
        $payment = $eventType === 'payment.paid'
            ? $resource
            : (data_get($resource, 'attributes.payments.0')
                ?? data_get($resource, 'attributes.payment_intent.attributes.payments.0')
                ?? []);

        $timestamp = data_get($payment, 'attributes.paid_at')
            ?? data_get($resource, 'attributes.paid_at')
            ?? data_get($resource, 'attributes.updated_at');

        if (is_numeric($timestamp)) {
            return CarbonImmutable::createFromTimestamp((int) $timestamp, 'UTC')->setTimezone(config('app.timezone'));
        }

        if (filled($timestamp)) {
            return CarbonImmutable::parse((string) $timestamp)->setTimezone(config('app.timezone'));
        }

        return CarbonImmutable::now();
    }

    public function fromPayload(array $payload): ?CarbonImmutable
    {
        $resource = data_get($payload, 'data.attributes.data');
        if (! is_array($resource)) {
            return null;
        }

        $payment = data_get($resource, 'attributes.payments.0')
            ?? data_get($resource, 'attributes.payment_intent.attributes.payments.0');
        if (! filled(data_get($payment, 'attributes.paid_at'))
            && ! filled(data_get($resource, 'attributes.paid_at'))
            && ! filled(data_get($resource, 'attributes.updated_at'))) {
            return null;
        }

        return $this->fromResource((string) data_get($payload, 'data.attributes.type'), $resource);
    }
}
