<?php

namespace Tests\Feature\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\AssessmentStatus;
use App\Enums\DocumentVerificationStatus;
use App\Enums\FarmerStatus;
use App\Enums\MemberTypeCode;
use App\Enums\MembershipApplicationRejectionReason;
use App\Enums\MembershipStatus;
use App\Enums\NotificationType;
use App\Models\Association;
use App\Models\Barangay;
use App\Models\Farmer;
use App\Models\FeeSchedule;
use App\Models\MemberType;
use App\Models\MembershipApplication;
use App\Models\PaymentAssessment;
use App\Models\User;
use App\Services\Documents\FarmerDocumentService;
use App\Services\Membership\MembershipApplicationService;
use App\Services\Membership\MembershipStatusService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MembershipApplicationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_walk_in_application_creates_required_checklist_rows(): void
    {
        $user = $this->makeAdminUser();
        $lookups = $this->makeLookups();
        $this->makeFeeSchedule();

        $response = $this->actingAs($user)->post(route('admin.membership-applications.store'), $this->walkInPayload($lookups));

        $application = MembershipApplication::query()->with('documents')->first();
        $farmer = Farmer::query()->first();

        $response->assertRedirect(route('admin.membership-applications.show', $application));
        $this->assertNotNull($application);
        $this->assertNotNull($farmer);
        $this->assertSame('walk_in', $application->source);
        $this->assertSame(ApplicationStatus::SUBMITTED, $application->status);
        $this->assertCount(3, $application->documents);
        $this->assertTrue($application->documents->every(fn ($document) => $document->is_received === false));
        $this->assertSame(MembershipStatus::PENDING_DOCUMENTS, $farmer->fresh()->membership_status);
    }

    public function test_walk_in_application_requires_sex_before_creating_records(): void
    {
        $user = $this->makeAdminUser();
        $lookups = $this->makeLookups();
        $payload = $this->walkInPayload($lookups);
        $payload['sex'] = '';

        $response = $this->actingAs($user)
            ->from(route('admin.membership-applications.create'))
            ->post(route('admin.membership-applications.store'), $payload);

        $response->assertRedirect(route('admin.membership-applications.create'));
        $response->assertSessionHasErrors(['sex']);
        $this->assertDatabaseCount('farmers', 0);
        $this->assertDatabaseCount('membership_applications', 0);
    }

    public function test_walk_in_duplicate_farmer_blocks_application_and_returns_error(): void
    {
        $user = $this->makeAdminUser();
        $lookups = $this->makeLookups();
        $this->makeFeeSchedule();

        Farmer::query()->create([
            'farmer_code' => 'FRM-2026-00001',
            'first_name' => 'Juan',
            'middle_name' => 'Santos',
            'last_name' => 'Dela Cruz',
            'birth_date' => now()->subYears(30)->toDateString(),
            'mobile_number' => '09171234567',
            'barangay_id' => $lookups['barangay']->id,
            'association_id' => $lookups['association']->id,
            'member_type_id' => $lookups['memberType']->id,
            'status' => FarmerStatus::PENDING->value,
            'registered_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->from(route('admin.membership-applications.create'))
            ->post(route('admin.membership-applications.store'), $this->walkInPayload($lookups));

        $response->assertRedirect(route('admin.membership-applications.create'));
        $response->assertSessionHasErrors('duplicate_check');
        $this->assertDatabaseCount('farmers', 1);
        $this->assertDatabaseCount('membership_applications', 0);
    }

    public function test_walk_in_document_confirmation_is_automatically_verified(): void
    {
        $user = $this->makeAdminUser();
        $lookups = $this->makeLookups();
        $this->makeFeeSchedule();

        $this->actingAs($user)->post(route('admin.membership-applications.store'), $this->walkInPayload($lookups));

        $application = MembershipApplication::query()->with('documents')->firstOrFail();
        $document = $application->documents->firstOrFail();

        $response = $this->actingAs($user)
            ->post(route('admin.membership-applications.documents.review', [$application, $document]), [
                'action' => 'receive',
            ]);

        $response->assertRedirect(route('admin.membership-applications.show', $application));
        $document->refresh();

        $this->assertTrue($document->is_received);
        $this->assertSame(DocumentVerificationStatus::VERIFIED, $document->verification_status);
        $this->assertNotNull($document->verified_by);
        $this->assertNotNull($document->verified_at);
    }

    public function test_normal_membership_application_ignores_legacy_renewal_fields(): void
    {
        $user = $this->makeAdminUser();
        $lookups = $this->makeLookups();
        $payload = $this->walkInPayload($lookups);
        $payload['member_type_id'] = $lookups['memberType']->id;
        $payload['create_renewal_record'] = true;
        $payload['renewal_year'] = now()->year;

        $response = $this->actingAs($user)->post(route('admin.membership-applications.store'), $payload);

        $application = MembershipApplication::query()->firstOrFail();

        $response->assertRedirect(route('admin.membership-applications.show', $application));
        $this->assertDatabaseHas('membership_transactions', [
            'id' => $application->id,
            'transaction_type' => 'Application',
        ]);
        $this->assertDatabaseMissing('membership_transactions', [
            'farmer_id' => $application->farmer_id,
            'transaction_type' => 'Renewal',
        ]);
    }

    public function test_document_review_url_redirects_to_application_review_screen(): void
    {
        $user = $this->makeAdminUser();
        $lookups = $this->makeLookups();
        $this->makeFeeSchedule();

        $this->actingAs($user)->post(route('admin.membership-applications.store'), $this->walkInPayload($lookups));

        $application = MembershipApplication::query()->with('documents')->firstOrFail();
        $document = $application->documents->firstOrFail();

        $response = $this->actingAs($user)
            ->get(route('admin.membership-applications.documents.review.show', [$application, $document]));

        $response->assertRedirect(route('admin.membership-applications.show', $application) . '#document-' . $document->id);
    }

    public function test_walk_in_application_recreates_missing_member_type_row_from_enum(): void
    {
        $user = $this->makeAdminUser();
        $lookups = $this->makeLookups();
        $this->makeFeeSchedule();

        $lookups['memberType']->delete();

        $response = $this->actingAs($user)->post(route('admin.membership-applications.store'), $this->walkInPayload($lookups));

        $application = MembershipApplication::query()->first();
        $farmer = Farmer::query()->first();

        $response->assertRedirect(route('admin.membership-applications.show', $application));
        $this->assertNotNull($farmer);
        $this->assertDatabaseHas('member_types', [
            'code' => MemberTypeCode::NM->value,
            'name' => MemberTypeCode::NM->label(),
        ]);
        $this->assertSame(MemberTypeCode::NM->value, $farmer->fresh()->memberType->code);
    }

    public function test_mobile_application_cannot_be_approved_until_uploaded_documents_are_verified(): void
    {
        $user = $this->makeAdminUser();
        $lookups = $this->makeLookups();
        $this->makeFeeSchedule();
        $farmer = $this->makeFarmer($lookups);
        $application = MembershipApplication::query()->create([
            'farmer_id' => $farmer->id,
            'application_no' => 'APP-' . now()->format('Y') . '-MOBILE1',
            'source' => 'mobile',
            'status' => ApplicationStatus::SUBMITTED->value,
            'submitted_at' => now(),
        ]);

        app(FarmerDocumentService::class)->ensureApplicationChecklist($application);
        app(MembershipStatusService::class)->markPendingDocuments($farmer);
        $application->load('documents');

        $blocked = $this->from(route('admin.membership-applications.show', $application))
            ->actingAs($user)
            ->post(route('admin.membership-applications.review', $application), [
                'action' => 'approve',
            ]);

        $blocked->assertRedirect(route('admin.membership-applications.show', $application));
        $blocked->assertSessionHasErrors('application');
        $this->assertSame(ApplicationStatus::SUBMITTED, $application->fresh()->status);
        $this->assertSame(MembershipStatus::PENDING_DOCUMENTS, $farmer->fresh()->membership_status);

        foreach ($application->documents as $document) {
            $document->update([
                'disk' => 'public',
                'path' => 'documents/' . $document->document_type->value . '.pdf',
                'original_name' => $document->document_type->value . '.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 1024,
            ]);

            $verifyResponse = $this->actingAs($user)
                ->post(route('admin.membership-applications.documents.review', [$application, $document]), [
                    'action' => 'verify',
                ]);

            $verifyResponse->assertRedirect(route('admin.membership-applications.show', $application));
        }

        $this->assertSame(MembershipStatus::PENDING_VERIFICATION, $farmer->fresh()->membership_status);

        $approveResponse = $this->actingAs($user)
            ->post(route('admin.membership-applications.review', $application), [
                'action' => 'approve',
            ]);

        $approveResponse->assertRedirect(route('admin.membership-applications.show', $application));
        $this->assertSame(ApplicationStatus::APPROVED, $application->fresh()->status);
        $this->assertSame(MembershipStatus::PENDING_PAYMENT, $farmer->fresh()->membership_status);
    }

    public function test_staff_can_approve_mobile_application_after_documents_are_verified(): void
    {
        $user = $this->makeStaffUser();
        $lookups = $this->makeLookups();
        $this->makeFeeSchedule();
        $farmer = $this->makeFarmer($lookups);
        $application = MembershipApplication::query()->create([
            'farmer_id' => $farmer->id,
            'application_no' => 'APP-' . now()->format('Y') . '-STAFF1',
            'source' => 'mobile',
            'status' => ApplicationStatus::SUBMITTED->value,
            'submitted_at' => now(),
        ]);

        app(FarmerDocumentService::class)->ensureApplicationChecklist($application);
        app(MembershipStatusService::class)->markPendingDocuments($farmer);
        $application->load('documents');

        foreach ($application->documents as $document) {
            $document->update([
                'disk' => 'public',
                'path' => 'documents/' . $document->document_type->value . '.pdf',
                'original_name' => $document->document_type->value . '.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 1024,
            ]);

            $this->actingAs($user)
                ->post(route('admin.membership-applications.documents.review', [$application, $document]), [
                    'action' => 'verify',
                ])->assertRedirect(route('admin.membership-applications.show', $application));
        }

        $response = $this->actingAs($user)
            ->post(route('admin.membership-applications.review', $application), [
                'action' => 'approve',
            ]);

        $response->assertRedirect(route('admin.membership-applications.show', $application));
        $this->assertSame(ApplicationStatus::APPROVED, $application->fresh()->status);
        $this->assertSame($user->id, $application->fresh()->reviewed_by);
        $this->assertSame(MembershipStatus::PENDING_PAYMENT, $farmer->fresh()->membership_status);
    }

    public function test_staff_cannot_reject_membership_application(): void
    {
        $user = $this->makeStaffUser();
        $lookups = $this->makeLookups();
        $this->makeFeeSchedule();

        $this->actingAs($this->makeAdminUser())->post(route('admin.membership-applications.store'), $this->walkInPayload($lookups));

        $application = MembershipApplication::query()->firstOrFail();

        $response = $this->actingAs($user)->post(route('admin.membership-applications.review', $application), [
            'action' => 'reject',
            'rejection_reason' => MembershipApplicationRejectionReason::MISSING_DOCUMENTS->value,
            'rejection_details' => 'Staff should not be able to reject applications.',
        ]);

        $response->assertForbidden();
        $this->assertSame(ApplicationStatus::SUBMITTED, $application->fresh()->status);
    }

    public function test_mobile_application_uses_new_notification_for_first_upload_and_updated_for_later_uploads(): void
    {
        Storage::fake('public');

        $user = $this->makeAdminUser();
        $lookups = $this->makeLookups();
        $farmer = $this->makeFarmer($lookups);
        $service = app(MembershipApplicationService::class);

        $application = $service->create([
            'farmer_id' => $farmer->id,
            'source' => 'mobile',
        ]);

        $this->assertDatabaseCount('notifications', 0);
        $this->assertDatabaseCount('notification_recipients', 0);

        $service->uploadMobileDocument(
            $application->fresh(),
            'cedula',
            UploadedFile::fake()->create('cedula.pdf', 120, 'application/pdf'),
        );

        $notificationId = DB::table('notifications')
            ->value('id');

        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseCount('notification_recipients', 1);
        $this->assertNotNull($notificationId);
        $this->assertDatabaseHas('notifications', [
            'id' => $notificationId,
            'type' => NotificationType::MEMBERSHIP_APPLICATION_SUBMITTED->value,
            'subject' => 'New membership application',
        ]);
        $this->assertDatabaseHas('notifications', [
            'id' => $notificationId,
            'message' => 'Juan Dela Cruz submitted a new membership application ' . $application->application_no . ' and uploaded Cedula.',
        ]);
        $this->assertDatabaseHas('notification_recipients', [
            'notification_id' => $notificationId,
            'status' => 'pending',
            'read_at' => null,
        ]);

        $service->uploadMobileDocument(
            $application->fresh(),
            'two_by_two_picture',
            UploadedFile::fake()->image('id-picture.jpg'),
        );

        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseHas('notifications', [
            'id' => $notificationId,
            'type' => NotificationType::MEMBERSHIP_APPLICATION_UPDATED->value,
            'subject' => 'Mobile application updated',
            'message' => 'Juan Dela Cruz uploaded or updated 2x2 Picture for membership application ' . $application->application_no . '.',
        ]);
        $this->assertDatabaseHas('notification_recipients', [
            'notification_id' => $notificationId,
            'status' => 'pending',
            'read_at' => null,
        ]);
    }

    public function test_paid_and_approved_mobile_application_remains_in_approved_queue(): void
    {
        // The queue's year options use MySQL's YEAR function.
        DB::connection()->getPdo()->sqliteCreateFunction('year', fn ($date) => $date ? (int) substr($date, 0, 4) : null);
        $user = $this->makeAdminUser();
        $lookups = $this->makeLookups();
        $this->makeFeeSchedule();

        $paidFarmer = $this->makeFarmer($lookups);
        $paidApplication = MembershipApplication::query()->create([
            'farmer_id' => $paidFarmer->id,
            'application_no' => 'APP-' . now()->format('Y') . '-PAID01',
            'source' => 'mobile',
            'status' => ApplicationStatus::APPROVED->value,
            'submitted_at' => now(),
            'reviewed_at' => now(),
            'approved_at' => now(),
        ]);

        PaymentAssessment::query()->create([
            'farmer_id' => $paidFarmer->id,
            'membership_transaction_id' => $paidApplication->id,
            'fee_schedule_id' => FeeSchedule::query()->value('id'),
            'member_type_snapshot' => MemberTypeCode::NM->value,
            'membership_fee' => 100,
            'annual_due' => 100,
            'mortuary_fee' => 150,
            'total_amount_due' => 350,
            'due_date' => now()->toDateString(),
            'status' => AssessmentStatus::PAID->value,
        ]);

        $pendingFarmer = $this->makeFarmer($lookups);
        $pendingApplication = MembershipApplication::query()->create([
            'farmer_id' => $pendingFarmer->id,
            'application_no' => 'APP-' . now()->format('Y') . '-PEND01',
            'source' => 'mobile',
            'status' => ApplicationStatus::SUBMITTED->value,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('admin.membership-applications.index', ['source' => 'mobile']));

        $response->assertOk();
        $response->assertSee($paidApplication->application_no);
        $response->assertSee($pendingApplication->application_no);
        $response->assertInertia(fn ($page) => $page
            ->where('summary.total', 2)
            ->where('summary.approved', 1)
            ->where('summary.pending', 1));

        $approvedResponse = $this->actingAs($user)->get(route('admin.membership-applications.index', [
            'source' => 'mobile', 'status' => 'approved',
        ]));
        $approvedResponse->assertOk();
        $approvedResponse->assertSee($paidApplication->application_no);
        $approvedResponse->assertDontSee($pendingApplication->application_no);
    }

    public function test_paid_application_annual_dues_appear_in_renewal_history_and_cover_only_the_paid_year(): void
    {
        $lookups = $this->makeLookups();
        $this->makeFeeSchedule();
        $farmer = $this->makeFarmer($lookups);
        $application = MembershipApplication::query()->create([
            'farmer_id' => $farmer->id, 'application_no' => 'APP-ANNUAL-COVERAGE',
            'source' => 'mobile', 'status' => ApplicationStatus::APPROVED, 'submitted_at' => now(),
        ]);
        $assessment = PaymentAssessment::query()->create([
            'membership_transaction_id' => $application->id,
            'fee_schedule_id' => FeeSchedule::query()->value('id'),
            'annual_due' => 100, 'membership_fee' => 100, 'mortuary_fee' => 150,
            'total_amount_due' => 350, 'status' => AssessmentStatus::PAID,
        ]);
        $ledger = \App\Models\MembershipLedger::query()->create([
            'membership_transaction_id' => $application->id,
            'fee_schedule_id' => $assessment->fee_schedule_id, 'year' => now()->year,
            'amount_paid' => 350, 'payment_status' => 'paid', 'paid_at' => now(),
        ]);
        $service = app(\App\Services\Farmer\FarmerRenewalService::class);
        $eligibility = $service->eligibility($farmer);
        $this->assertSame('already_renewed', $eligibility['state']);
        $this->assertFalse($eligibility['can_start']);
        $this->assertTrue($service->eligibility($farmer, now()->year + 1)['can_start']);

        $method = new \ReflectionMethod(\App\Http\Controllers\Admin\FarmerController::class, 'renewalTimeline');
        $history = $method->invoke(app(\App\Http\Controllers\Admin\FarmerController::class), $farmer);
        $this->assertCount(1, $history);
        $this->assertSame('renewal', $history[0]['type']);
        $this->assertSame('Completed', $history[0]['status']);
        $this->assertSame((string) now()->year, $history[0]['meta']['Year']);
        $this->assertDatabaseCount('payment_assessments', 1);

        try {
            $service->startOrResume($farmer);
            $this->fail('A covered year must not create another renewal.');
        } catch (\DomainException $exception) {
            $this->assertStringContainsString('already paid', $exception->getMessage());
        }
        $ledger->update(['payment_status' => 'unpaid']);
        $this->assertTrue($service->eligibility($farmer)['can_start']);
        $this->assertCount(0, $method->invoke(app(\App\Http\Controllers\Admin\FarmerController::class), $farmer));
    }

    public function test_walk_in_manual_approval_is_blocked_and_payment_is_the_approval_step(): void
    {
        $user = $this->makeAdminUser();
        $lookups = $this->makeLookups();
        $this->makeFeeSchedule();

        $this->actingAs($user)->post(route('admin.membership-applications.store'), $this->walkInPayload($lookups));

        $application = MembershipApplication::query()->with('documents')->firstOrFail();

        foreach ($application->documents as $document) {
            $this->actingAs($user)
                ->post(route('admin.membership-applications.documents.review', [$application, $document]), [
                    'action' => 'receive',
                ])->assertRedirect(route('admin.membership-applications.show', $application));
        }

        $approveResponse = $this->from(route('admin.membership-applications.show', $application))
            ->actingAs($user)
            ->post(route('admin.membership-applications.review', $application), [
                'action' => 'approve',
            ]);

        $approveResponse->assertRedirect(route('admin.membership-applications.show', $application));
        $approveResponse->assertSessionHasErrors('application');
        $this->assertSame(ApplicationStatus::SUBMITTED, $application->fresh()->status);
    }

    public function test_rejecting_application_requires_reason_and_preserves_application_remarks(): void
    {
        $user = $this->makeAdminUser();
        $lookups = $this->makeLookups();
        $this->makeFeeSchedule();

        $this->actingAs($user)->post(route('admin.membership-applications.store'), $this->walkInPayload($lookups));

        $application = MembershipApplication::query()->firstOrFail();
        $farmer = Farmer::query()->firstOrFail();

        $invalid = $this->from(route('admin.membership-applications.show', $application))
            ->actingAs($user)
            ->post(route('admin.membership-applications.review', $application), [
                'action' => 'reject',
            ]);

        $invalid->assertRedirect(route('admin.membership-applications.show', $application));
        $invalid->assertSessionHasErrors('rejection_reason');

        $valid = $this->actingAs($user)
            ->post(route('admin.membership-applications.review', $application), [
                'action' => 'reject',
                'rejection_reason' => MembershipApplicationRejectionReason::INVALID_PERSONAL_DETAILS->value,
                'rejection_details' => 'Birth date does not match the submitted supporting records.',
            ]);

        $valid->assertRedirect(route('admin.membership-applications.show', $application));
        $this->assertSame(ApplicationStatus::REJECTED, $application->fresh()->status);
        $this->assertSame(MembershipApplicationRejectionReason::INVALID_PERSONAL_DETAILS, $application->fresh()->rejection_reason);
        $this->assertSame('Birth date does not match the submitted supporting records.', $application->fresh()->rejection_details);
        $this->assertSame('Walk-in office application', $application->fresh()->remarks);
        $this->assertSame(MembershipStatus::PENDING_APPLICATION, $farmer->fresh()->membership_status);
    }

    public function test_walk_in_application_becomes_active_after_payment_is_recorded(): void
    {
        $user = $this->makeAdminUser();
        $lookups = $this->makeLookups();
        $this->makeFeeSchedule();

        $this->actingAs($user)->post(route('admin.membership-applications.store'), $this->walkInPayload($lookups));

        $application = MembershipApplication::query()->with('documents')->firstOrFail();
        $farmer = Farmer::query()->firstOrFail();

        foreach ($application->documents as $document) {
            $this->actingAs($user)
                ->post(route('admin.membership-applications.documents.review', [$application, $document]), [
                    'action' => 'receive',
                ])->assertRedirect(route('admin.membership-applications.show', $application));
        }

        $paymentResponse = $this->actingAs($user)
            ->post(route('admin.membership-applications.payment.store', $application), [
                'payment_method' => 'cash',
                'reference_no' => 'OR-1001',
                'amount_paid' => 350.00,
                'paid_at' => now()->toDateTimeString(),
            ]);

        $paymentResponse->assertRedirect(route('admin.membership-applications.show', $application));

        $farmer->refresh();
        $application->refresh();

        $this->assertSame(ApplicationStatus::APPROVED, $application->status);
        $this->assertNotNull($application->reviewed_by);
        $this->assertNotNull($application->reviewed_at);
        $this->assertNotNull($application->approved_at);
        $this->assertSame(FarmerStatus::ACTIVE, $farmer->status);
        $this->assertSame(MembershipStatus::ACTIVE, $farmer->membership_status);
        $this->assertDatabaseHas('payment_assessments', [
            'membership_application_id' => $application->id,
            'status' => AssessmentStatus::PAID->value,
        ]);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('membership_ledgers', 1);
    }

    public function test_admin_can_create_walk_in_application_as_active(): void
    {
        $user = $this->makeAdminUser();
        $lookups = $this->makeLookups();
        $this->makeFeeSchedule();

        $payload = $this->walkInPayload($lookups);
        $payload['status'] = FarmerStatus::ACTIVE->value;

        $response = $this->actingAs($user)->post(route('admin.membership-applications.store'), $payload);

        $application = MembershipApplication::query()->with(['farmer', 'documents', 'paymentAssessments.payments'])->first();

        $response->assertRedirect(route('admin.membership-applications.show', $application));
        $this->assertNotNull($application);
        $this->assertSame(ApplicationStatus::APPROVED, $application->status);
        $this->assertSame(FarmerStatus::ACTIVE, $application->farmer->fresh()->status);
        $this->assertSame(MembershipStatus::ACTIVE, $application->farmer->fresh()->membership_status);
        $this->assertTrue($application->documents->every(fn ($document) => $document->verification_status === DocumentVerificationStatus::VERIFIED));
        $this->assertDatabaseHas('payment_assessments', [
            'membership_application_id' => $application->id,
            'status' => AssessmentStatus::PAID->value,
        ]);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('membership_ledgers', 1);
    }

    public function test_rejected_application_can_create_reapplication_without_duplicate_farmer(): void
    {
        $user = $this->makeAdminUser();
        $lookups = $this->makeLookups();
        $this->makeFeeSchedule();

        $farmer = Farmer::query()->create([
            'farmer_code' => 'FRM-' . now()->format('Y') . '-00001',
            'first_name' => 'Juan',
            'middle_name' => 'Santos',
            'last_name' => 'Dela Cruz',
            'birth_date' => now()->subYears(30)->toDateString(),
            'mobile_number' => '09171234567',
            'email' => 'juan-original@example.test',
            'address' => 'Sitio Uno',
            'barangay_id' => $lookups['barangay']->id,
            'association_id' => $lookups['association']->id,
            'member_type_id' => $lookups['memberType']->id,
            'status' => FarmerStatus::PENDING->value,
            'membership_status' => MembershipStatus::PENDING_APPLICATION->value,
            'registered_at' => now(),
            'remarks' => 'Original farmer note',
        ]);

        $rejectedApplication = MembershipApplication::query()->create([
            'farmer_id' => $farmer->id,
            'application_no' => 'APP-' . now()->format('Y') . '-REJECT1',
            'source' => 'walk_in',
            'status' => ApplicationStatus::REJECTED->value,
            'submitted_at' => now()->subDay(),
            'reviewed_at' => now()->subHours(12),
            'remarks' => 'Original office note.',
            'rejection_reason' => MembershipApplicationRejectionReason::MISSING_DOCUMENTS->value,
            'rejection_details' => 'Missing valid documents.',
        ]);

        $createResponse = $this->actingAs($user)->get(route('admin.membership-applications.create', [
            'reapply_from_application' => $rejectedApplication->id,
        ]));

        $createResponse->assertOk();
        $createResponse->assertSee('Create a replacement membership application.');
        $createResponse->assertSee($rejectedApplication->application_no);
        $createResponse->assertSee('Missing Documents');
        $createResponse->assertSee('Missing valid documents.');
        $createResponse->assertSee('Juan');
        $createResponse->assertSee('Dela Cruz');

        $payload = $this->walkInPayload($lookups);
        $payload['reapply_from_application_id'] = $rejectedApplication->id;
        $payload['mobile_number'] = '09179999999';
        $payload['email'] = 'juan-updated@example.test';
        $payload['remarks'] = 'Updated farmer note';
        $payload['application_remarks'] = 'Replacement walk-in application';

        $response = $this->actingAs($user)->post(route('admin.membership-applications.store'), $payload);

        $newApplication = MembershipApplication::query()
            ->whereKeyNot($rejectedApplication->id)
            ->latest('id')
            ->first();

        $response->assertRedirect(route('admin.membership-applications.show', $newApplication));
        $this->assertNotNull($newApplication);
        $this->assertSame($farmer->id, $newApplication->farmer_id);
        $this->assertDatabaseCount('farmers', 1);
        $this->assertDatabaseCount('membership_applications', 2);
        $this->assertSame('Replacement walk-in application', $newApplication->remarks);
        $this->assertSame(ApplicationStatus::REJECTED, $rejectedApplication->fresh()->status);
        $this->assertSame('09179999999', $farmer->fresh()->mobile_number);
        $this->assertSame('juan-updated@example.test', $farmer->fresh()->email);
        $this->assertSame('Updated farmer note', $farmer->fresh()->remarks);
    }
    private function makeAdminUser(): User
    {
        Role::findOrCreate(User::ROLE_ADMIN, 'web');
        Role::findOrCreate(User::ROLE_STAFF, 'web');

        $user = User::query()->create([
            'name' => 'Office Admin',
            'email' => 'admin' . uniqid() . '@example.test',
            'password' => 'secret123',
            'status' => User::STATUS_ACTIVE,
            'role' => User::ROLE_ADMIN,
        ]);

        $user->assignRole(User::ROLE_ADMIN);

        return $user;
    }

    private function makeStaffUser(): User
    {
        Role::findOrCreate(User::ROLE_ADMIN, 'web');
        Role::findOrCreate(User::ROLE_STAFF, 'web');

        $user = User::query()->create([
            'name' => 'Office Staff',
            'email' => 'staff' . uniqid() . '@example.test',
            'password' => 'secret123',
            'status' => User::STATUS_ACTIVE,
            'role' => User::ROLE_STAFF,
        ]);

        $user->assignRole(User::ROLE_STAFF);

        return $user;
    }

    private function makeLookups(): array
    {
        $memberType = MemberType::query()->create([
            'code' => MemberTypeCode::NM->value,
            'name' => MemberTypeCode::NM->label(),
            'is_new_member' => true,
            'is_senior' => false,
            'requires_membership_fee' => true,
            'mortuary_eligible' => true,
        ]);

        $barangay = Barangay::query()->create([
            'name' => 'San Roque',
            'code' => 'BRGY-' . uniqid(),
            'status' => 'Active',
        ]);

        $association = Association::query()->create([
            'barangay_id' => $barangay->id,
            'name' => 'San Roque Association',
            'code' => 'ASSOC-' . uniqid(),
            'status' => 'Active',
        ]);

        return compact('memberType', 'barangay', 'association');
    }

    private function makeFarmer(array $lookups): Farmer
    {
        return Farmer::query()->create([
            'farmer_code' => 'FRM-' . now()->format('Y') . '-' . rand(10000, 99999),
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'barangay_id' => $lookups['barangay']->id,
            'association_id' => $lookups['association']->id,
            'member_type_id' => $lookups['memberType']->id,
            'status' => FarmerStatus::PENDING->value,
            'membership_status' => MembershipStatus::PENDING_APPLICATION->value,
            'registered_at' => now(),
        ]);
    }

    private function walkInPayload(array $lookups): array
    {
        return [
            'source' => 'walk_in',
            'status' => FarmerStatus::PENDING->value,
            'first_name' => 'Juan',
            'middle_name' => 'Santos',
            'last_name' => 'Dela Cruz',
            'birth_date' => now()->subYears(30)->toDateString(),
            'sex' => 'male',
            'civil_status' => 'single',
            'mobile_number' => '09171234567',
            'email' => 'juan' . uniqid() . '@example.test',
            'address' => 'Sitio Uno',
            'barangay_id' => $lookups['barangay']->id,
            'association_id' => $lookups['association']->id,
            'registered_at' => now()->toDateString(),
            'remarks' => 'Farmer record note',
            'application_remarks' => 'Walk-in office application',
        ];
    }

    private function makeFeeSchedule(): void
    {
        FeeSchedule::query()->create([
            'member_type_id' => MemberType::query()->value('id'),
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







