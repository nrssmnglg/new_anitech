<?php

namespace Tests\Feature\Api;

use App\Enums\ApplicationStatus;
use App\Enums\MemberTypeCode;
use App\Enums\MembershipApplicationRejectionReason;
use App\Enums\MembershipStatus;
use App\Models\Association;
use App\Models\Barangay;
use App\Models\FeeSchedule;
use App\Models\MembershipApplication;
use App\Models\MembershipLedger;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MobileMembershipApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_membership_application_can_be_submitted_without_login(): void
    {
        [$barangay, $association] = $this->makeLookups();

        $response = $this->postJson(route('api.v1.membership-applications.store'), $this->applicationPayload($barangay, $association));

        $response->assertCreated();
        $response->assertJsonPath('data.source', 'mobile');
        $response->assertJsonPath('data.status', 'submitted');
        $response->assertJsonPath('data.farmer.membership_status', MembershipStatus::PENDING_DOCUMENTS->value);
        $response->assertJsonPath('data.farmer.member_type', MemberTypeCode::NM->value);
        $response->assertJsonCount(3, 'data.documents');

        $application = MembershipApplication::query()->with(['farmer.memberType', 'documents'])->firstOrFail();

        $this->assertSame('mobile', $application->source);
        $this->assertSame(MembershipStatus::PENDING_DOCUMENTS, $application->farmer->membership_status);
        $this->assertSame(MemberTypeCode::NM->value, $application->farmer->memberType->code);
        $this->assertCount(3, $application->documents);
    }

    public function test_mobile_membership_application_requires_sex(): void
    {
        [$barangay, $association] = $this->makeLookups();
        $payload = $this->applicationPayload($barangay, $association);
        $payload['sex'] = '';

        $response = $this->postJson(route('api.v1.membership-applications.store'), $payload);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sex']);

        $this->assertDatabaseCount('farmers', 0);
        $this->assertDatabaseCount('membership_applications', 0);
    }

    public function test_mobile_application_can_be_tracked_by_application_number_and_birth_date(): void
    {
        [$barangay, $association] = $this->makeLookups();

        $submitResponse = $this->postJson(route('api.v1.membership-applications.store'), $this->applicationPayload($barangay, $association));
        $applicationNo = $submitResponse->json('data.application_no');

        $trackResponse = $this->getJson(route('api.v1.membership-applications.track', [
            'application_no' => $applicationNo,
            'birth_date' => '1995-04-15',
        ]));

        $trackResponse->assertOk();
        $trackResponse->assertJsonPath('data.application_no', $applicationNo);
        $trackResponse->assertJsonPath('data.farmer.name', 'Juan Santos Dela Cruz');
        $trackResponse->assertJsonPath('data.payment.can_pay', false);
        $trackResponse->assertJsonCount(3, 'data.required_documents');
    }

    public function test_rejected_mobile_application_can_be_reapplied_without_creating_duplicate_farmer(): void
    {
        [$barangay, $association] = $this->makeLookups();

        $submitResponse = $this->postJson(route('api.v1.membership-applications.store'), $this->applicationPayload($barangay, $association));
        $submitResponse->assertCreated();

        $application = MembershipApplication::query()->with('farmer')->firstOrFail();
        $application->forceFill([
            'status' => ApplicationStatus::REJECTED,
            'rejection_reason' => MembershipApplicationRejectionReason::INVALID_PERSONAL_DETAILS->value,
            'rejection_details' => 'Please correct the address before resubmitting.',
        ])->save();

        $this->getJson(route('api.v1.membership-applications.track', [
            'application_no' => $application->application_no,
            'birth_date' => '1995-04-15',
        ]))
            ->assertOk()
            ->assertJsonPath('data.status', ApplicationStatus::REJECTED->value)
            ->assertJsonPath('data.rejection.reason', MembershipApplicationRejectionReason::INVALID_PERSONAL_DETAILS->value)
            ->assertJsonPath('data.rejection.reason_label', MembershipApplicationRejectionReason::INVALID_PERSONAL_DETAILS->label())
            ->assertJsonPath('data.rejection.details', 'Please correct the address before resubmitting.')
            ->assertJsonPath('data.reapply.can_reapply', true)
            ->assertJsonPath('data.reapply.application_id', $application->id);

        $reapplyPayload = $this->applicationPayload($barangay, $association);
        $reapplyPayload['address'] = 'Sitio Dos';
        $reapplyPayload['remarks'] = 'Updated address for reapplication.';
        $reapplyPayload['reapply_from_application_id'] = $application->id;

        $reapplyResponse = $this->postJson(route('api.v1.membership-applications.store'), $reapplyPayload);

        $reapplyResponse->assertCreated();
        $reapplyResponse->assertJsonPath('message', 'Membership application resubmitted successfully.');
        $reapplyResponse->assertJsonPath('data.source', 'mobile');

        $this->assertDatabaseCount('farmers', 1);
        $this->assertDatabaseCount('membership_applications', 2);

        $latestApplication = MembershipApplication::query()->latest('id')->firstOrFail();
        $this->assertNotSame($application->id, $latestApplication->id);
        $this->assertSame($application->farmer_id, $latestApplication->farmer_id);
        $this->assertSame('Sitio Dos', $latestApplication->fresh()->farmer->address);
    }

    public function test_mobile_documents_can_be_uploaded_and_status_moves_to_pending_verification_when_complete(): void
    {
        Storage::fake('public');
        [$barangay, $association] = $this->makeLookups();

        $submitResponse = $this->postJson(route('api.v1.membership-applications.store'), $this->applicationPayload($barangay, $association));
        $applicationNo = $submitResponse->json('data.application_no');
        $application = MembershipApplication::query()->with(['documents', 'farmer'])->firstOrFail();

        foreach ($application->documents as $document) {
            $response = $this->post(route('api.v1.membership-applications.documents.store', ['applicationNo' => $applicationNo]), [
                'birth_date' => '1995-04-15',
                'document_type' => $document->document_type->value,
                'document' => UploadedFile::fake()->create($document->document_type->value . '.pdf', 64, 'application/pdf'),
            ], ['Accept' => 'application/json']);

            $response->assertOk();
            $response->assertJsonPath('data.document.uploaded', true);
        }

        $application->refresh()->load(['documents', 'farmer']);

        $this->assertSame(MembershipStatus::PENDING_VERIFICATION, $application->farmer->membership_status);
        $this->assertTrue($application->documents->every(fn ($document) => $document->disk === 'public'));

        foreach ($application->documents as $document) {
            $path = (string) $document->path;

            $this->assertNotSame('', $path);
            $this->assertTrue(Storage::disk('public')->exists($path));
        }
    }

    public function test_mobile_application_can_be_paid_from_the_farmer_pwa_after_approval(): void
    {
        Storage::fake('public');
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

        $payResponse = $this->postJson(route('farmer.pwa.application.payment.store', ['applicationNo' => $applicationNo]), [
            'birth_date' => '1995-04-15',
            'payment_method' => 'qrph',
            'reference_no' => 'QRPH-1001',
        ]);

        $payResponse->assertOk();
        $payResponse->assertJsonPath('data.application.payment.status', 'paid');
        $payResponse->assertJsonPath('data.application.payment.can_pay', false);
        $payResponse->assertJsonPath('data.application.farmer.membership_status', MembershipStatus::ACTIVE->value);
        $payResponse->assertJsonPath('data.application.account.can_setup', true);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('membership_ledgers', 1);
    }

    public function test_mobile_application_payment_updates_existing_yearly_ledger_instead_of_failing(): void
    {
        Storage::fake('public');
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

        MembershipLedger::query()->create([
            'farmer_id' => $application->farmer_id,
            'year' => now()->year,
            'membership_application_id' => null,
            'renewal_request_id' => null,
            'member_type_snapshot' => $application->fresh()->farmer->memberType->code,
            'membership_fee' => 0,
            'annual_due' => 0,
            'mortuary_fee' => 0,
            'total_amount_due' => 0,
            'amount_paid' => 0,
            'paid_at' => null,
            'payment_status' => 'pending',
            'mortuary_eligible' => true,
            'status' => 'pending',
        ]);

        $payResponse = $this->postJson(route('farmer.pwa.application.payment.store', ['applicationNo' => $applicationNo]), [
            'birth_date' => '1995-04-15',
            'payment_method' => 'qrph',
            'reference_no' => 'QRPH-2001',
        ]);

        $payResponse->assertOk();
        $payResponse->assertJsonPath('data.application.payment.status', 'paid');
        $this->assertDatabaseCount('membership_ledgers', 1);
        $this->assertDatabaseHas('membership_ledgers', [
            'farmer_id' => $application->farmer_id,
            'year' => now()->year,
            'membership_application_id' => $application->id,
            'payment_status' => 'paid',
        ]);
    }

    public function test_active_mobile_member_can_set_up_farmer_account(): void
    {
        Storage::fake('public');
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

        $this->postJson(route('farmer.pwa.application.payment.store', ['applicationNo' => $applicationNo]), [
            'birth_date' => '1995-04-15',
            'payment_method' => 'qrph',
            'reference_no' => 'QRPH-1002',
        ])->assertOk();

        $response = $this->postJson(route('farmer.pwa.account.setup.store'), [
            'application_no' => $applicationNo,
            'birth_date' => '1995-04-15',
            'email' => 'farmer.account@example.test',
            'password' => 'secret12345',
            'password_confirmation' => 'secret12345',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.account.email', 'farmer.account@example.test');
        $this->assertDatabaseHas('users', [
            'email' => 'farmer.account@example.test',
            'farmer_id' => $application->fresh()->farmer_id,
        ]);
    }

    public function test_active_farmer_can_log_in_after_account_setup(): void
    {
        Storage::fake('public');
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

        $this->postJson(route('farmer.pwa.application.payment.store', ['applicationNo' => $applicationNo]), [
            'birth_date' => '1995-04-15',
            'payment_method' => 'qrph',
            'reference_no' => 'QRPH-1003',
        ])->assertOk();

        $this->postJson(route('farmer.pwa.account.setup.store'), [
            'application_no' => $applicationNo,
            'birth_date' => '1995-04-15',
            'email' => 'farmer.login@example.test',
            'password' => 'secret12345',
            'password_confirmation' => 'secret12345',
        ])->assertCreated();

        $loginResponse = $this->postJson(route('farmer.pwa.login.store'), [
            'email' => 'farmer.login@example.test',
            'password' => 'secret12345',
        ]);

        $loginResponse->assertOk();
        $loginResponse->assertJsonPath('data.redirect_url', route('farmer.pwa.home'));
        $this->assertAuthenticated('farmer_pwa');

        $this->get(route('farmer.pwa.home'))
            ->assertOk()
            ->assertSee('Juan Santos Dela Cruz')
            ->assertSee('Renewal Overview');

        $logoutResponse = $this->postJson(route('farmer.pwa.logout'));
        $logoutResponse->assertOk();
        $logoutResponse->assertJsonPath('data.redirect_url', route('farmer.pwa.login'));
        $this->assertGuest('farmer_pwa');
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
            'name' => 'Mobile Admin',
            'email' => 'mobile-admin' . uniqid() . '@example.test',
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
