<?php

namespace Tests\Feature;

use App\Services\Payments\PaymentGatewayService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PayMongoQrTestModeTest extends TestCase
{
    public function test_test_session_preserves_simulator_url_and_assessed_amount(): void
    {
        $payment = $this->createPayment(true, 'https://test-sources.paymongo.com/simulation');
        $this->assertTrue($payment['is_test']);
        $this->assertSame('https://test-sources.paymongo.com/simulation', $payment['test_url']);
        $this->assertSame(350.0, $payment['amount']);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/payment_intents')
            && $request['data']['attributes']['amount'] === 35000);
    }

    public function test_live_session_does_not_expose_simulation_url(): void
    {
        $payment = $this->createPayment(false, 'https://test-sources.paymongo.com/simulation');
        $this->assertFalse($payment['is_test']);
        $this->assertNull($payment['test_url']);
    }

    public function test_missing_or_untrusted_simulation_link_is_not_exposed(): void
    {
        $this->assertNull($this->createPayment(true, null)['test_url']);
        $this->assertNull($this->createPayment(true, 'https://paymongo.com.example.org/test')['test_url']);
    }

    public function test_old_sessions_and_sessions_from_another_mode_are_rejected(): void
    {
        config(['services.paymongo.secret_key' => 'sk_test_example']);
        $gateway = new PaymentGatewayService();
        $this->assertFalse($gateway->qrModeMatches([]));
        $this->assertFalse($gateway->qrModeMatches(['is_test' => false]));
        $this->assertTrue($gateway->qrModeMatches(['is_test' => true]));
        config(['services.paymongo.secret_key' => 'sk_live_example']);
        $this->assertFalse($gateway->qrModeMatches(['is_test' => true]));
    }

    private function createPayment(bool $test, ?string $testUrl): array
    {
        config(['services.paymongo.secret_key' => $test ? 'sk_test_example' : 'sk_live_example']);
        Http::preventStrayRequests();
        Http::fake([
            'api.paymongo.com/v1/payment_intents' => Http::response(['data' => ['id' => 'pi_example']]),
            'api.paymongo.com/v1/payment_methods' => Http::response(['data' => ['id' => 'pm_example']]),
            'api.paymongo.com/v1/payment_intents/pi_example/attach' => Http::response([
                'data' => ['attributes' => [
                    'livemode' => ! $test,
                    'next_action' => ['code' => ['image_url' => 'data:image/png;base64,example', 'test_url' => $testUrl]],
                ]],
            ]),
        ]);

        return (new PaymentGatewayService())->createQrPhPayment([
            'amount' => 350,
            'billing' => ['name' => 'Test Applicant', 'email' => 'applicant@example.test'],
        ]);
    }
}
