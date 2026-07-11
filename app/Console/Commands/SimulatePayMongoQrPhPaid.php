<?php

namespace App\Console\Commands;

use App\Models\MembershipApplication;
use App\Models\RenewalRequest;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;

class SimulatePayMongoQrPhPaid extends Command
{
    protected $signature = 'app:simulate-paymongo-qrph-paid
        {reference : Payment reference like APP-2026-XXXXXX or REN-2026-123}
        {--amount= : Override amount in pesos}
        {--paid-at= : Override paid_at unix timestamp}';

    protected $description = 'Simulate a PayMongo QR Ph payment.paid webhook for a membership application or renewal.';

    public function handle(HttpKernel $kernel): int
    {
        $reference = strtoupper(trim((string) $this->argument('reference')));
        $timestamp = (int) ($this->option('paid-at') ?: now()->timestamp);

        $target = $this->resolveTarget($reference);

        if ($target === null) {
            $this->error('No payable source matched reference [' . $reference . '].');

            return self::FAILURE;
        }

        $amount = $this->resolveAmount($target, $reference);
        $payload = $this->buildPayload(
            reference: $reference,
            amountInCentavos: (int) round($amount * 100),
            paidAt: $timestamp,
            description: $target['description'],
            livemode: false,
        );

        $secret = (string) config('services.paymongo.webhook_secret', '');
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $headerTimestamp = (string) now()->timestamp;
        $signature = $secret !== '' ? hash_hmac('sha256', $headerTimestamp . '.' . $body, $secret) : null;

        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ];

        if ($signature !== null) {
            $headers['HTTP_PAYMONGO_SIGNATURE'] = 't=' . $headerTimestamp . ',te=' . $signature . ',li=';
        }

        $request = Request::create(
            uri: route('api.v1.payments.paymongo.webhook'),
            method: 'POST',
            content: $body,
            server: $headers,
        );

        $response = $kernel->handle($request);
        $content = $response->getContent();

        $this->line('HTTP ' . $response->getStatusCode());

        if (is_string($content) && $content !== '') {
            $this->line($content);
        }

        $kernel->terminate($request, $response);

        return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300
            ? self::SUCCESS
            : self::FAILURE;
    }

    private function resolveTarget(string $reference): ?array
    {
        if (preg_match('/^APP-\d{4}-[A-Z0-9]+$/', $reference) === 1) {
            $application = MembershipApplication::query()
                ->with(['paymentAssessments' => fn ($query) => $query->latest('id')])
                ->where('application_no', $reference)
                ->first();

            if (! $application) {
                return null;
            }

            return [
                'assessment' => $application->paymentAssessments->first(),
                'description' => 'Membership application payment for ' . $reference,
            ];
        }

        if (preg_match('/^REN-\d{4}-(\d+)$/', $reference, $matches) === 1) {
            $renewal = RenewalRequest::query()
                ->with(['paymentAssessments' => fn ($query) => $query->latest('id')])
                ->find((int) $matches[1]);

            if (! $renewal) {
                return null;
            }

            return [
                'assessment' => $renewal->paymentAssessments->first(),
                'description' => 'Renewal payment for ' . $reference,
            ];
        }

        return null;
    }

    private function resolveAmount(array $target, string $reference): float
    {
        $override = $this->option('amount');

        if ($override !== null && $override !== '') {
            return round((float) $override, 2);
        }

        $amount = (float) ($target['assessment']?->total_amount_due ?? 0);

        if ($amount <= 0) {
            $this->warn('No assessment total was found for [' . $reference . ']. Falling back to PHP 1.00.');

            return 1.00;
        }

        return round($amount, 2);
    }

    private function buildPayload(
        string $reference,
        int $amountInCentavos,
        int $paidAt,
        string $description,
        bool $livemode,
    ): array {
        $suffix = preg_replace('/[^A-Z0-9]+/i', '', $reference) ?: 'TEST';

        return [
            'data' => [
                'id' => 'evt_' . strtolower($suffix),
                'type' => 'event',
                'attributes' => [
                    'type' => 'payment.paid',
                    'livemode' => $livemode,
                    'data' => [
                        'id' => 'pay_' . strtolower($suffix),
                        'type' => 'payment',
                        'attributes' => [
                            'amount' => $amountInCentavos,
                            'currency' => 'PHP',
                            'description' => $description,
                            'external_reference_number' => $reference,
                            'paid_at' => $paidAt,
                            'status' => 'paid',
                            'source' => [
                                'id' => 'qrph_' . strtolower($suffix),
                                'type' => 'qrph',
                            ],
                            'metadata' => [
                                'payment_method' => 'qrph',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
