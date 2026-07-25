<?php

namespace Tests\Feature\Api;

use App\Models\Association;
use App\Models\Barangay;
use App\Models\FeeSchedule;
use App\Models\MembershipApplication;
use App\Models\PaymentAssessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PayMongoWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_qrph_payment_paid_webhook_marks_membership_application_as_paid_using_reference_matching(): void
    {
        Storage::fake('public');
        config()->set('services.paymongo.webhook_secret', 'whsec_test_123');
        [$application, $assessment] = $this->createApprovedMobileApplication();

        $payload = [
            'data' => [
                'id' => 'evt_qrph_1001',
                'type' => 'event',
                'attributes' => [
                    'type' => 'payment.paid',
                    'livemode' => false,
                    'data' => [
                        'id' => 'pay_qrph_1001',
                        'type' => 'payment',
                        'attributes' => [
                            'amount' => 35000,
                            'currency' => 'PHP',
                            'description' => 'Membership application payment for ' . $application->application_no,
                            'external_reference_number' => $application->application_no,
                            'paid_at' => 1711886400,
                            'status' => 'paid',
                            'source' => [
                                'id' => 'qrph_test_1001',
                                'type' => 'qrph',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postSignedWebhook($payload);

        $response->assertOk();
        $response->assertJsonPath('status', 'processed');
        $response->assertJsonPath('source', 'membership_application');
        $response->assertJsonPath('assessment_status', 'paid');

        $this->assertDatabaseHas('payments', [
            'payment_assessment_id' => $assessment->id,
            'payment_method' => 'qrph',
            'reference_no' => $application->application_no,
            'amount_paid' => 350.00,
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('payment_assessments', [
            'id' => $assessment->id,
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('membership_ledgers', [
            'membership_application_id' => $application->id,
            'payment_status' => 'paid',
        ]);
        $this->assertDatabaseHas('paymongo_webhook_events', [
            'paymongo_event_id' => 'evt_qrph_1001',
            'event_type' => 'payment.paid',
            'status' => 'processed',
            'payment_assessment_id' => $assessment->id,
            'membership_application_id' => $application->id,
            'reference_no' => $application->application_no,
        ]);
    }

    public function test_checkout_session_paid_webhook_records_membership_payment_once(): void
    {
        Storage::fake('public');
        config()->set('services.paymongo.webhook_secret', 'whsec_test_123');

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

        $assessment = PaymentAssessment::query()
            ->where('membership_application_id', $application->id)
            ->firstOrFail();

        $payload = $this->checkoutSessionPaidPayload($assessment, $application);

        $firstResponse = $this->postSignedWebhook($payload);
        $firstResponse->assertOk();
        $firstResponse->assertJsonPath('status', 'processed');
        $firstResponse->assertJsonPath('source', 'membership_application');
        $firstResponse->assertJsonPath('assessment_status', 'paid');

        $secondResponse = $this->postSignedWebhook($payload);
        $secondResponse->assertOk();
        $secondResponse->assertJsonPath('status', 'ignored');
        $secondResponse->assertJsonPath('reason', 'duplicate_payment');

        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('paymongo_webhook_events', 2);
        $this->assertDatabaseHas('payments', [
            'payment_assessment_id' => $assessment->id,
            'payment_method' => 'gcash',
            'reference_no' => 'PM-ORDER-1001',
            'amount_paid' => 350.00,
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('payment_assessments', [
            'id' => $assessment->id,
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('membership_ledgers', [
            'membership_application_id' => $application->id,
            'payment_status' => 'paid',
        ]);
        $this->assertDatabaseHas('paymongo_webhook_events', [
            'paymongo_event_id' => 'evt_paymongo_1001',
            'event_type' => 'checkout_session.payment.paid',
            'status' => 'processed',
            'payment_assessment_id' => $assessment->id,
            'membership_application_id' => $application->id,
            'reference_no' => 'PM-ORDER-1001',
            'amount' => 350.00,
        ]);
        $this->assertDatabaseHas('paymongo_webhook_events', [
            'paymongo_event_id' => 'evt_paymongo_1001',
            'event_type' => 'checkout_session.payment.paid',
            'status' => 'ignored',
            'message' => 'Duplicate payment reference received from PayMongo.',
            'payment_assessment_id' => $assessment->id,
            'membership_application_id' => $application->id,
            'reference_no' => 'PM-ORDER-1001',
        ]);
        $this->assertDatabaseHas('farmers', [
            'id' => $application->farmer_id,
            'membership_status' => 'active',
            'is_registry_record' => 1,
            'source_application_id' => $application->id,
        ]);
    }

    public function test_qrph_test_charge_webhook_marks_the_full_assessment_as_paid(): void
    {
        Storage::fake('public');
        config()->set('services.paymongo.webhook_secret', 'whsec_test_123');
        config()->set('services.paymongo.qrph_test_amount', 1);
        [$application, $assessment] = $this->createApprovedMobileApplication();

        $payload = [
            'data' => [
                'id' => 'evt_qrph_test_1002',
                'type' => 'event',
                'attributes' => [
                    'type' => 'payment.paid',
                    'livemode' => false,
                    'data' => [
                        'id' => 'pay_qrph_test_1002',
                        'type' => 'payment',
                        'attributes' => [
                            'amount' => 100,
                            'currency' => 'PHP',
                            'description' => 'Membership application payment for ' . $application->application_no,
                            'external_reference_number' => $application->application_no,
                            'paid_at' => 1711886400,
                            'status' => 'paid',
                            'source' => [
                                'id' => 'qrph_test_1002',
                                'type' => 'qrph',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postSignedWebhook($payload);

        $response->assertOk();
        $response->assertJsonPath('status', 'processed');
        $response->assertJsonPath('assessment_status', 'paid');

        $this->assertDatabaseHas('payments', [
            'payment_assessment_id' => $assessment->id,
            'reference_no' => $application->application_no,
            'amount_paid' => 350.00,
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('paymongo_webhook_events', [
            'paymongo_event_id' => 'evt_qrph_test_1002',
            'amount' => 350.00,
            'payment_assessment_id' => $assessment->id,
        ]);
    }

    public function test_raw_paid_payment_payload_is_normalized_and_processed(): void
    {
        Storage::fake('public');
        config()->set('services.paymongo.webhook_secret', 'whsec_test_123');
        config()->set('services.paymongo.qrph_test_amount', 1);
        [$application, $assessment] = $this->createApprovedMobileApplication();

        $payload = [
            'id' => 'pay_6akspf853ux6BTT9LisQz5Pe',
            'type' => 'payment',
            'attributes' => [
                'amount' => 100,
                'billing' => [
                    'email' => 'applicant-2@anitech.local',
                    'name' => 'Michael Valdez Sorino',
                    'phone' => '09525256369',
                ],
                'currency' => 'PHP',
                'description' => 'Membership application payment for ' . $application->application_no,
                'fee' => 2,
                'livemode' => true,
                'net_amount' => 98,
                'source' => [
                    'id' => 'qrph_S7wTkSc2UQHHENk4wPvmyj2g',
                    'type' => 'qrph',
                ],
                'status' => 'paid',
                'metadata' => [
                    'reference_no' => $application->application_no,
                    'source_type' => 'membership_application',
                    'payment_method' => 'qrph',
                    'membership_application_id' => (string) $application->id,
                    'source_id' => (string) $application->id,
                    'assessment_id' => (string) $assessment->id,
                ],
                'paid_at' => 1784871612,
                'updated_at' => 1784871613,
            ],
        ];

        $response = $this->postSignedWebhook($payload);

        $response->assertOk();
        $response->assertJsonPath('status', 'processed');
        $response->assertJsonPath('source', 'membership_application');
        $response->assertJsonPath('assessment_status', 'paid');

        $this->assertDatabaseHas('payments', [
            'payment_assessment_id' => $assessment->id,
            'payment_method' => 'qrph',
            'reference_no' => $application->application_no,
            'amount_paid' => 350.00,
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('paymongo_webhook_events', [
            'paymongo_event_id' => 'pay_6akspf853ux6BTT9LisQz5Pe',
            'event_type' => 'payment.paid',
            'status' => 'processed',
            'payment_assessment_id' => $assessment->id,
            'membership_application_id' => $application->id,
            'reference_no' => $application->application_no,
            'amount' => 350.00,
        ]);
    }

    public function test_webhook_rejects_invalid_signature_in_middleware(): void
    {
        config()->set('services.paymongo.webhook_secret', 'whsec_test_123');

        $payload = [
            'data' => [
                'id' => 'evt_invalid_sig',
                'type' => 'event',
                'attributes' => [
                    'type' => 'payment.paid',
                    'livemode' => false,
                    'data' => [
                        'id' => 'pay_invalid_sig',
                        'type' => 'payment',
                        'attributes' => [
                            'amount' => 10000,
                            'status' => 'paid',
                            'source' => [
                                'type' => 'gcash',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postSignedWebhook($payload, 'bad-signature');

        $response->assertUnauthorized();
        $response->assertJsonPath('message', 'Invalid webhook signature.');
        $this->assertDatabaseCount('paymongo_webhook_events', 0);
    }

    private function checkoutSessionPaidPayload(PaymentAssessment $assessment, MembershipApplication $application): array
    {
        return [
            'data' => [
                'id' => 'evt_paymongo_1001',
                'type' => 'event',
                'attributes' => [
                    'type' => 'checkout_session.payment.paid',
                    'livemode' => false,
                    'data' => [
                        'id' => 'cs_test_1001',
                        'type' => 'checkout_session',
                        'attributes' => [
                            'reference_number' => 'PM-CS-1001',
                            'payments' => [
                                [
                                    'id' => 'pay_test_1001',
                                    'type' => 'payment',
                                    'attributes' => [
                                        'amount' => 35000,
                                        'status' => 'paid',
                                        'paid_at' => 1711886400,
                                        'source' => [
                                            'id' => 'src_test_1001',
                                            'type' => 'gcash',
                                        ],
                                        'metadata' => [
                                            'assessment_id' => $assessment->id,
                                            'membership_application_id' => $application->id,
                                            'reference_no' => 'PM-ORDER-1001',
                                        ],
                                    ],
                                ],
                            ],
                            'metadata' => [
                                'assessment_id' => $assessment->id,
                                'membership_application_id' => $application->id,
                                'reference_no' => 'PM-ORDER-1001',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function postSignedWebhook(array $payload, ?string $signatureOverride = null)
    {
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp = (string) now()->timestamp;
        $signature = $signatureOverride
            ?? hash_hmac('sha256', $timestamp . '.' . $body, (string) config('services.paymongo.webhook_secret'));

        return $this->call(
            'POST',
            route('api.v1.payments.paymongo.webhook'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_PAYMONGO_SIGNATURE' => 't=' . $timestamp . ',te=' . $signature . ',li=',
            ],
            $body,
        );
    }

    private function createApprovedMobileApplication(): array
    {
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

        $application = MembershipApplication::query()->with('farmer')->findOrFail($application->id);
        $assessment = PaymentAssessment::query()
            ->where('membership_application_id', $application->id)
            ->firstOrFail();

        return [$application, $assessment];
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
            'name' => 'Webhook Admin',
            'email' => 'webhook-admin' . uniqid() . '@example.test',
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
