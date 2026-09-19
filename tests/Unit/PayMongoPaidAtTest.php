<?php

namespace Tests\Unit;

use App\Services\Payments\PayMongoPaidAt;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class PayMongoPaidAtTest extends TestCase
{
    public function test_unix_payment_timestamp_is_converted_to_manila_time(): void
    {
        config()->set('app.timezone', 'Asia/Manila');
        $timestamp = CarbonImmutable::parse('2026-09-18 09:16:00', 'UTC')->timestamp;
        $resource = ['attributes' => ['paid_at' => $timestamp]];

        $service = app(PayMongoPaidAt::class);
        $this->assertSame('2026-09-18 17:16:00', $service->fromResource('payment.paid', $resource)->toDateTimeString());
        $this->assertSame('2026-09-18 17:16:00', $service->fromPayload([
            'data' => ['attributes' => ['type' => 'payment.paid', 'data' => $resource]],
        ])->toDateTimeString());
        $this->assertSame('2026-09-18 17:16:00', $service->fromResource('checkout_session.payment.paid', [
            'attributes' => ['payments' => [$resource]],
        ])->toDateTimeString());
        $this->assertNull($service->fromPayload(['data' => ['attributes' => ['data' => []]]]));
    }
}
