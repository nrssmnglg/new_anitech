<?php

namespace Tests\Feature\Api;

use App\Models\Association;
use App\Models\Barangay;
use App\Models\FeeSchedule;
use App\Models\MembershipApplication;
use App\Models\PaymentAssessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PayMongoCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_application_payment_creates_a_qrph_payment_when_gateway_is_configured(): void
    {
        Storage::fake('public');
        config()->set('services.paymongo.secret_key', 'sk_test_paymongo');

        [$barangay, $association] = $this->makeLookups();
        $this->makeFeeSchedule();

        $submitResponse = $this->postJson(route('api.v1.membership-applications.store'), $this->applicationPayload($barangay, $association));
        $applicationNo = $submitResponse->json('data.application_no');
        $application = MembershipApplication::query()->with(['documents', 'farmer'])->firstOrFail();

        foreach ($application->documents as $document) {
            $this->post(route('api.v1.membership-applications.documents.store', ['applicationNo' => $applicationNo]), [
                'birth_date' => '1995-04-15',
                'document_type' => $document->document_type->value,
                'document' => UploadedFile::fake()->create($document->document_type->value . '.pdf', 64, 'application/pdf'),
            ], ['Accept' => 'application/json'])->assertOk();
        }

        $admin = $this->makeAdminUser();
        foreach ($application->fresh()->documents as $document) {
            $this->actingAs($admin)
                ->post(route('admin.membership-applications.documents.review', [$application, $document]), [
                    'action' => 'verify',
                ])->assertRedirect(route('admin.membership-applications.show', $application));
        }

        $this->actingAs($admin)
            ->post(route('admin.membership-applications.review', $application), [
                'action' => 'approve',
            ])->assertRedirect(route('admin.membership-applications.show', $application));

        $assessment = PaymentAssessment::query()->where('membership_application_id', $application->id)->firstOrFail();

        Http::fake([
            'https://api.paymongo.com/v1/payment_intents' => Http::response([
                'data' => [
                    'id' => 'pi_test_checkout_123',
                    'type' => 'payment_intent',
                ],
            ], 200),
            'https://api.paymongo.com/v1/payment_methods' => Http::response([
                'data' => [
                    'id' => 'pm_test_qrph_123',
                    'type' => 'payment_method',
                ],
            ], 200),
            'https://api.paymongo.com/v1/payment_intents/pi_test_checkout_123/attach' => Http::response([
                'data' => [
                    'id' => 'pi_test_checkout_123',
                    'type' => 'payment_intent',
                    'attributes' => [
                        'metadata' => [
                            'assessment_id' => $assessment->id,
                            'membership_application_id' => $application->id,
                            'reference_no' => $application->application_no,
                            'payment_method' => 'qrph',
                        ],
                        'next_action' => [
                            'code' => [
                                'id' => 'qr_test_checkout_123',
                                'image_url' => 'https://files.paymongo.com/qr_test_checkout_123.png',
                                'label' => 'Scan QR Ph',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $payResponse = $this->postJson(route('farmer.pwa.application.payment.store', ['applicationNo' => $applicationNo]), [
            'birth_date' => '1995-04-15',
            'payment_method' => 'qrph',
        ]);

        $payResponse->assertOk();
        $payResponse->assertJsonPath('message', 'QR Ph code generated successfully.');
        $payResponse->assertJsonPath('data.qr_page_url', route('farmer.pwa.application.payment.qr', [
            'applicationNo' => $applicationNo,
            'birth_date' => '1995-04-15',
        ]));
        $payResponse->assertJsonPath('data.qr_image_url', 'https://files.paymongo.com/qr_test_checkout_123.png');
        $payResponse->assertJsonPath('data.provider', 'paymongo');
        $payResponse->assertJsonPath('data.application.payment.status', 'pending');

        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('membership_ledgers', 0);

        Http::assertSent(function (Request $request) use ($application): bool {
            $payload = $request->data();

            return str_starts_with($request->url(), 'https://api.paymongo.com/v1/payment_intents')
                && ! str_ends_with($request->url(), '/attach')
                && data_get($payload, 'data.attributes.description') === 'Membership application payment for ' . $application->application_no;
        });

        Http::assertSent(function (Request $request): bool {
            $payload = $request->data();

            return $request->url() === 'https://api.paymongo.com/v1/payment_methods'
                && data_get($payload, 'data.attributes.type') === 'qrph';
        });

        Http::assertSent(function (Request $request): bool {
            $payload = $request->data();

            return $request->url() === 'https://api.paymongo.com/v1/payment_intents/pi_test_checkout_123/attach'
                && data_get($payload, 'data.attributes.payment_method') === 'pm_test_qrph_123';
        });
    }

    public function test_mobile_application_payment_uses_test_charge_amount_without_changing_the_assessment_total(): void
    {
        Storage::fake('public');
        config()->set('services.paymongo.secret_key', 'sk_test_paymongo');
        config()->set('services.paymongo.qrph_test_amount', 1);

        [$barangay, $association] = $this->makeLookups();
        $this->makeFeeSchedule();

        $submitResponse = $this->postJson(route('api.v1.membership-applications.store'), $this->applicationPayload($barangay, $association));
        $applicationNo = $submitResponse->json('data.application_no');
        $application = MembershipApplication::query()->with(['documents', 'farmer'])->firstOrFail();

        foreach ($application->documents as $document) {
            $this->post(route('api.v1.membership-applications.documents.store', ['applicationNo' => $applicationNo]), [
                'birth_date' => '1995-04-15',
                'document_type' => $document->document_type->value,
                'document' => UploadedFile::fake()->create($document->document_type->value . '.pdf', 64, 'application/pdf'),
            ], ['Accept' => 'application/json'])->assertOk();
        }

        $admin = $this->makeAdminUser();
        foreach ($application->fresh()->documents as $document) {
            $this->actingAs($admin)
                ->post(route('admin.membership-applications.documents.review', [$application, $document]), [
                    'action' => 'verify',
                ])->assertRedirect(route('admin.membership-applications.show', $application));
        }

        $this->actingAs($admin)
            ->post(route('admin.membership-applications.review', $application), [
                'action' => 'approve',
            ])->assertRedirect(route('admin.membership-applications.show', $application));

        $assessment = PaymentAssessment::query()->where('membership_application_id', $application->id)->firstOrFail();

        Http::fake([
            'https://api.paymongo.com/v1/payment_intents' => Http::response([
                'data' => [
                    'id' => 'pi_test_checkout_456',
                    'type' => 'payment_intent',
                ],
            ], 200),
            'https://api.paymongo.com/v1/payment_methods' => Http::response([
                'data' => [
                    'id' => 'pm_test_qrph_456',
                    'type' => 'payment_method',
                ],
            ], 200),
            'https://api.paymongo.com/v1/payment_intents/pi_test_checkout_456/attach' => Http::response([
                'data' => [
                    'id' => 'pi_test_checkout_456',
                    'type' => 'payment_intent',
                    'attributes' => [
                        'metadata' => [
                            'assessment_id' => $assessment->id,
                            'membership_application_id' => $application->id,
                            'reference_no' => $application->application_no,
                            'payment_method' => 'qrph',
                        ],
                        'next_action' => [
                            'code' => [
                                'id' => 'qr_test_checkout_456',
                                'image_url' => 'https://files.paymongo.com/qr_test_checkout_456.png',
                                'label' => 'Scan QR Ph',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $payResponse = $this->postJson(route('farmer.pwa.application.payment.store', ['applicationNo' => $applicationNo]), [
            'birth_date' => '1995-04-15',
            'payment_method' => 'qrph',
        ]);

        $payResponse->assertOk();
        $payResponse->assertJsonPath('data.application.payment.total_amount_due', 350);

        Http::assertSent(function (Request $request): bool {
            $payload = $request->data();

            return $request->url() === 'https://api.paymongo.com/v1/payment_intents'
                && data_get($payload, 'data.attributes.amount') === 100;
        });
    }

    private function makeLookups(): array
    {
        Role::findOrCreate(User::ROLE_ADMIN, 'web');
        Role::findOrCreate(User::ROLE_STAFF, 'web');

        $barangay = Barangay::query()->create([
            'name' => 'San Roque',
            'code' => 'BRGY-' . uniqid(),
            'status' => 'active',
        ]);

        $association = Association::query()->create([
            'barangay_id' => $barangay->id,
            'name' => 'San Roque Association',
            'code' => 'ASSOC-' . uniqid(),
            'status' => 'active',
        ]);

        return [$barangay, $association];
    }

    private function applicationPayload(Barangay $barangay, Association $association): array
    {
        return [
            'first_name' => 'Juan',
            'middle_name' => 'Santos',
            'last_name' => 'Dela Cruz',
            'birth_date' => '1995-04-15',
            'sex' => 'male',
            'civil_status' => 'single',
            'mobile_number' => '09171234567',
            'email' => 'juan@example.test',
            'address' => 'Sitio Uno',
            'barangay_id' => $barangay->id,
            'association_id' => $association->id,
            'remarks' => 'Submitted from farmer mobile app.',
        ];
    }

    private function makeAdminUser(): User
    {
        Role::findOrCreate(User::ROLE_ADMIN, 'web');

        $user = User::query()->create([
            'name' => 'Checkout Admin',
            'email' => 'checkout-admin' . uniqid() . '@example.test',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        $user->assignRole(User::ROLE_ADMIN);

        return $user;
    }

    private function makeFeeSchedule(): void
    {
        FeeSchedule::query()->create([
            'year' => now()->year,
            'membership_fee' => 100,
            'annual_due' => 100,
            'mortuary_fee' => 150,
            'renewal_deadline' => now()->startOfYear()->addDays(44)->toDateString(),
            'is_active' => true,
            'effective_from' => now()->startOfYear()->toDateString(),
            'effective_to' => null,
        ]);
    }
}
