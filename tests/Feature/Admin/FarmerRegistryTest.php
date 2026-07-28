<?php

namespace Tests\Feature\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\AssessmentStatus;
use App\Enums\DocumentVerificationStatus;
use App\Enums\FarmerStatus;
use App\Enums\MemberTypeCode;
use App\Enums\MembershipStatus;
use App\Enums\PaymentStatus;
use App\Models\Association;
use App\Models\Barangay;
use App\Models\Farmer;
use App\Models\FeeSchedule;
use App\Models\MemberType;
use App\Models\MembershipApplication;
use App\Models\MembershipLedger;
use App\Models\Payment;
use App\Models\PaymentAssessment;
use App\Models\User;
use App\Services\Documents\FarmerDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FarmerRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_registry_index_lists_farmer_records_and_supports_search(): void
    {
        [$barangay] = $this->seedLookups();

        $juan = Farmer::create([
            'farmer_code' => 'FRM-2026-00001',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'barangay_id' => $barangay->id,
            'member_type_id' => MemberType::query()->where('code', MemberTypeCode::OM->value)->value('id'),
            'status' => FarmerStatus::PENDING->value,
            'registered_at' => now(),
        ]);

        MembershipLedger::create([
            'farmer_id' => $juan->id,
            'year' => now()->year,
            'member_type_snapshot' => MemberTypeCode::OM->value,
            'membership_fee' => 0,
            'annual_due' => 100,
            'mortuary_fee' => 150,
            'total_amount_due' => 250,
            'amount_paid' => 250,
            'paid_at' => now(),
            'payment_status' => PaymentStatus::PAID->value,
            'mortuary_eligible' => true,
            'status' => 'approved',
        ]);

        Farmer::create([
            'farmer_code' => 'FRM-2026-00002',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'barangay_id' => $barangay->id,
            'member_type_id' => MemberType::query()->where('code', MemberTypeCode::NM->value)->value('id'),
            'status' => FarmerStatus::ACTIVE->value,
            'registered_at' => now(),
        ]);

        $response = $this->get(route('admin.farmers.index', ['search' => 'Juan']));

        $response->assertOk();
        $response->assertSee('Juan Dela Cruz');
        $response->assertDontSee('Maria Santos');
        $response->assertSee($juan->farmer_code);
    }

    public function test_registry_index_filters_by_status(): void
    {
        [$barangay] = $this->seedLookups();

        Farmer::create([
            'farmer_code' => 'FRM-2026-01001',
            'first_name' => 'Active',
            'last_name' => 'Farmer',
            'barangay_id' => $barangay->id,
            'member_type_id' => MemberType::query()->where('code', MemberTypeCode::NM->value)->value('id'),
            'status' => FarmerStatus::ACTIVE->value,
            'registered_at' => now(),
        ]);

        Farmer::create([
            'farmer_code' => 'FRM-2026-01002',
            'first_name' => 'Inactive',
            'last_name' => 'Farmer',
            'barangay_id' => $barangay->id,
            'member_type_id' => MemberType::query()->where('code', MemberTypeCode::OM->value)->value('id'),
            'status' => FarmerStatus::INACTIVE->value,
            'inactive_at' => now(),
            'inactive_reason' => 'No renewal',
            'registered_at' => now(),
        ]);

        Farmer::create([
            'farmer_code' => 'FRM-2026-01003',
            'first_name' => 'Deceased',
            'last_name' => 'Farmer',
            'barangay_id' => $barangay->id,
            'member_type_id' => MemberType::query()->where('code', MemberTypeCode::OM->value)->value('id'),
            'status' => FarmerStatus::DECEASED->value,
            'inactive_at' => now(),
            'inactive_reason' => 'Deceased record',
            'registered_at' => now(),
        ]);

        $response = $this->get(route('admin.farmers.index', ['status' => FarmerStatus::INACTIVE->value]));

        $response->assertOk();
        $response->assertSee('Inactive Farmer');
        $response->assertDontSee('Active Farmer');
        $response->assertDontSee('Deceased Farmer');
    }


    public function test_registry_index_only_lists_farmers_with_current_approved_and_paid_membership_ledgers(): void
    {
        [$barangay] = $this->seedLookups();

        $existingFarmer = Farmer::create([
            'farmer_code' => 'FRM-2026-00021',
            'first_name' => 'Existing',
            'last_name' => 'Member',
            'barangay_id' => $barangay->id,
            'member_type_id' => MemberType::query()->where('code', MemberTypeCode::OM->value)->value('id'),
            'status' => FarmerStatus::ACTIVE->value,
            'registered_at' => now(),
        ]);

        MembershipLedger::create([
            'farmer_id' => $existingFarmer->id,
            'year' => now()->year,
            'member_type_snapshot' => MemberTypeCode::OM->value,
            'membership_fee' => 0,
            'annual_due' => 100,
            'mortuary_fee' => 150,
            'total_amount_due' => 250,
            'amount_paid' => 250,
            'paid_at' => now(),
            'payment_status' => PaymentStatus::PAID->value,
            'mortuary_eligible' => true,
            'status' => 'approved',
        ]);

        $approvedFarmer = Farmer::create([
            'farmer_code' => 'FRM-2026-00022',
            'first_name' => 'Approved',
            'last_name' => 'Applicant',
            'barangay_id' => $barangay->id,
            'member_type_id' => MemberType::query()->where('code', MemberTypeCode::NM->value)->value('id'),
            'status' => FarmerStatus::ACTIVE->value,
            'registered_at' => now(),
        ]);

        $application = MembershipApplication::create([
            'farmer_id' => $approvedFarmer->id,
            'application_no' => 'APP-2026-APPROVED1',
            'source' => 'walk_in',
            'status' => ApplicationStatus::APPROVED->value,
            'submitted_at' => now(),
            'approved_at' => now(),
        ]);

        MembershipLedger::create([
            'farmer_id' => $approvedFarmer->id,
            'year' => now()->year,
            'membership_application_id' => $application->id,
            'member_type_snapshot' => MemberTypeCode::NM->value,
            'membership_fee' => 100,
            'annual_due' => 100,
            'mortuary_fee' => 150,
            'total_amount_due' => 350,
            'amount_paid' => 350,
            'paid_at' => now(),
            'payment_status' => PaymentStatus::PAID->value,
            'mortuary_eligible' => true,
            'status' => 'approved',
        ]);

        $pendingFarmer = Farmer::create([
            'farmer_code' => 'FRM-2026-00023',
            'first_name' => 'Pending',
            'last_name' => 'Applicant',
            'barangay_id' => $barangay->id,
            'member_type_id' => MemberType::query()->where('code', MemberTypeCode::NM->value)->value('id'),
            'status' => FarmerStatus::PENDING->value,
            'registered_at' => now(),
        ]);

        MembershipApplication::create([
            'farmer_id' => $pendingFarmer->id,
            'application_no' => 'APP-2026-PENDING1',
            'source' => 'walk_in',
            'status' => ApplicationStatus::APPROVED->value,
            'submitted_at' => now(),
            'approved_at' => now(),
        ]);

        $unpaidRenewalFarmer = Farmer::create([
            'farmer_code' => 'FRM-2026-00024',
            'first_name' => 'Renewal',
            'last_name' => 'Pending',
            'barangay_id' => $barangay->id,
            'member_type_id' => MemberType::query()->where('code', MemberTypeCode::OM->value)->value('id'),
            'status' => FarmerStatus::ACTIVE->value,
            'registered_at' => now(),
        ]);

        MembershipLedger::create([
            'farmer_id' => $unpaidRenewalFarmer->id,
            'year' => now()->year,
            'member_type_snapshot' => MemberTypeCode::OM->value,
            'membership_fee' => 0,
            'annual_due' => 100,
            'mortuary_fee' => 150,
            'total_amount_due' => 250,
            'amount_paid' => 0,
            'paid_at' => null,
            'payment_status' => PaymentStatus::PENDING->value,
            'mortuary_eligible' => true,
            'status' => 'pending',
        ]);

        $response = $this->get(route('admin.farmers.index'));

        $response->assertOk();
        $response->assertSee('Existing Member');
        $response->assertSee('Approved Applicant');
        $response->assertDontSee('Pending Applicant');
        $response->assertDontSee('Renewal Pending');
    }

    public function test_duplicate_check_endpoint_returns_potential_matches(): void
    {
        [$barangay] = $this->seedLookups();

        Farmer::create([
            'farmer_code' => 'FRM-2026-00001',
            'first_name' => 'Jose',
            'last_name' => 'Villanueva',
            'birth_date' => '1980-01-15',
            'mobile_number' => '09171234567',
            'barangay_id' => $barangay->id,
            'member_type_id' => MemberType::query()->where('code', MemberTypeCode::OM->value)->value('id'),
            'status' => FarmerStatus::PENDING->value,
            'registered_at' => now(),
        ]);

        $response = $this->getJson(route('admin.farmers.duplicate-check', [
            'first_name' => 'Jose',
            'last_name' => 'Villanueva',
            'birth_date' => '1980-01-15',
            'barangay_id' => $barangay->id,
        ]));

        $response->assertOk();
        $response->assertJsonCount(1, 'matches');
        $response->assertJsonFragment([
            'farmer_code' => 'FRM-2026-00001',
            'full_name' => 'Jose Villanueva',
        ]);
    }

    public function test_store_blocks_potential_duplicate_without_override(): void
    {
        [$barangay, $association] = $this->seedLookups();
        $memberType = MemberType::query()->where('code', MemberTypeCode::NM->value)->firstOrFail();

        Farmer::create([
            'farmer_code' => 'FRM-2026-00001',
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'birth_date' => '1990-05-10',
            'barangay_id' => $barangay->id,
            'association_id' => $association->id,
            'member_type_id' => $memberType->id,
            'status' => FarmerStatus::PENDING->value,
            'registered_at' => now(),
        ]);

        $response = $this->from(route('admin.farmers.create'))->post(route('admin.farmers.store'), [
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'birth_date' => '1990-05-10',
            'barangay_id' => $barangay->id,
            'association_id' => $association->id,
            'member_type_id' => $memberType->id,
            'status' => FarmerStatus::PENDING->value,
        ]);

        $response->assertRedirect(route('admin.farmers.create'));
        $response->assertSessionHasErrors('duplicate_check');
        $response->assertSessionHas('duplicate_matches');
        $this->assertDatabaseCount('farmers', 1);
    }

    public function test_store_allows_duplicate_override_when_confirmed(): void
    {
        [$barangay, $association] = $this->seedLookups();
        $memberType = MemberType::query()->where('code', MemberTypeCode::NM->value)->firstOrFail();

        Farmer::create([
            'farmer_code' => 'FRM-2026-00001',
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'birth_date' => '1990-05-10',
            'barangay_id' => $barangay->id,
            'association_id' => $association->id,
            'member_type_id' => $memberType->id,
            'status' => FarmerStatus::PENDING->value,
            'registered_at' => now(),
        ]);

        $response = $this->post(route('admin.farmers.store'), [
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'birth_date' => '1990-05-10',
            'barangay_id' => $barangay->id,
            'association_id' => $association->id,
            'member_type_id' => $memberType->id,
            'status' => FarmerStatus::PENDING->value,
            'confirm_duplicate_override' => 1,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('farmers', 2);
    }

    public function test_show_displays_farmer_lifecycle_hub_details(): void
    {
        [$barangay, $association] = $this->seedLookups();
        $memberType = MemberType::query()->where('code', MemberTypeCode::NM->value)->firstOrFail();

        $farmer = Farmer::create([
            'farmer_code' => 'FRM-2026-00009',
            'first_name' => 'Ramon',
            'last_name' => 'Aguilar',
            'birth_date' => now()->subYears(34)->toDateString(),
            'barangay_id' => $barangay->id,
            'association_id' => $association->id,
            'member_type_id' => $memberType->id,
            'status' => FarmerStatus::ACTIVE->value,
            'membership_status' => MembershipStatus::PENDING_PAYMENT->value,
            'registered_at' => now()->subDays(5),
        ]);

        $feeSchedule = FeeSchedule::create([
            'year' => now()->year,
            'membership_fee' => 100,
            'annual_due' => 100,
            'mortuary_fee' => 150,
            'renewal_deadline' => now()->startOfYear()->addDays(44)->toDateString(),
            'is_active' => true,
            'effective_from' => now()->startOfYear()->toDateString(),
        ]);

        $application = MembershipApplication::create([
            'farmer_id' => $farmer->id,
            'application_no' => 'APP-2026-RAMON1',
            'source' => 'walk_in',
            'status' => ApplicationStatus::APPROVED->value,
            'submitted_at' => now()->subDays(4),
            'approved_at' => now()->subDays(3),
        ]);

        $assessment = PaymentAssessment::create([
            'farmer_id' => $farmer->id,
            'membership_application_id' => $application->id,
            'membership_application_id' => $application->id,
            'fee_schedule_id' => $feeSchedule->id,
            'member_type_snapshot' => 'NM',
            'membership_fee' => 100,
            'annual_due' => 100,
            'mortuary_fee' => 150,
            'total_amount_due' => 350,
            'status' => AssessmentStatus::PAID->value,
        ]);

        Payment::create([
            'payment_assessment_id' => $assessment->id,
            'payment_method' => 'cash',
            'reference_no' => 'OR-2001',
            'amount_paid' => 350,
            'paid_at' => now()->subDays(2),
            'status' => PaymentStatus::PAID->value,
            'receipt_no' => 'RCPT-2001',
        ]);

        MembershipLedger::create([
            'farmer_id' => $farmer->id,
            'year' => now()->year,
            'membership_application_id' => $application->id,
            'member_type_snapshot' => 'NM',
            'membership_fee' => 100,
            'annual_due' => 100,
            'mortuary_fee' => 150,
            'total_amount_due' => 350,
            'amount_paid' => 350,
            'paid_at' => now()->subDays(2),
            'payment_status' => PaymentStatus::PAID->value,
            'mortuary_eligible' => true,
            'status' => 'approved',
        ]);

        $response = $this->get(route('admin.farmers.show', $farmer));

        $response->assertOk();
        $response->assertSee('Farmer Record Hub');
        $response->assertSee('Recent Membership Applications');
        $response->assertSee('Recent Assessments And Payments');
        $response->assertSee('Membership Ledger History');
        $response->assertSee('APP-2026-RAMON1');
        $response->assertSee('PHP 350.00');
        $response->assertSee('Ramon Aguilar');
    }

    public function test_store_creates_farmer_with_manual_member_type(): void
    {
        [$barangay, $association] = $this->seedLookups();
        $memberType = MemberType::query()->where('code', MemberTypeCode::NSC->value)->firstOrFail();

        $response = $this->post(route('admin.farmers.store'), [
            'first_name' => 'Pedro',
            'last_name' => 'Ramirez',
            'birth_date' => now()->subYears(65)->toDateString(),
            'barangay_id' => $barangay->id,
            'association_id' => $association->id,
            'member_type_id' => $memberType->id,
            'status' => FarmerStatus::PENDING->value,
        ]);

        $farmer = Farmer::query()->first();

        $response->assertRedirect(route('admin.farmers.show', $farmer));
        $this->assertNotNull($farmer);
        $this->assertSame($memberType->id, $farmer->member_type_id);
        $this->assertStringStartsWith('FRM-' . now()->format('Y') . '-', $farmer->farmer_code);
        $this->assertSame('Pedro', $farmer->first_name);
    }

    public function test_update_changes_farmer_registry_fields(): void
    {
        [$barangay, $association, $otherBarangay, $otherAssociation] = $this->seedLookups();
        $om = MemberType::query()->where('code', MemberTypeCode::OM->value)->firstOrFail();
        $osc = MemberType::query()->where('code', MemberTypeCode::OSC->value)->firstOrFail();

        $farmer = Farmer::create([
            'farmer_code' => 'FRM-2026-00001',
            'first_name' => 'Lina',
            'last_name' => 'Garcia',
            'barangay_id' => $barangay->id,
            'association_id' => $association->id,
            'member_type_id' => $om->id,
            'status' => FarmerStatus::PENDING->value,
            'registered_at' => now(),
        ]);

        $response = $this->put(route('admin.farmers.update', $farmer), [
            'first_name' => 'Lina',
            'last_name' => 'Garcia',
            'barangay_id' => $otherBarangay->id,
            'association_id' => $otherAssociation->id,
            'member_type_id' => $osc->id,
            'status' => FarmerStatus::ACTIVE->value,
        ]);

        $response->assertRedirect(route('admin.farmers.show', $farmer));

        $farmer->refresh();

        $this->assertSame($otherBarangay->id, $farmer->barangay_id);
        $this->assertSame($otherAssociation->id, $farmer->association_id);
        $this->assertSame($osc->id, $farmer->member_type_id);
        $this->assertSame(FarmerStatus::ACTIVE, $farmer->status);
        $this->assertNotNull($farmer->activated_at);
    }

    public function test_update_preserves_existing_pending_membership_stage(): void
    {
        [$barangay, $association, $otherBarangay, $otherAssociation] = $this->seedLookups();
        $memberType = MemberType::query()->where('code', MemberTypeCode::NM->value)->firstOrFail();

        $farmer = Farmer::create([
            'farmer_code' => 'FRM-2026-00031',
            'first_name' => 'Pilar',
            'last_name' => 'Reyes',
            'barangay_id' => $barangay->id,
            'association_id' => $association->id,
            'member_type_id' => $memberType->id,
            'status' => FarmerStatus::PENDING->value,
            'membership_status' => MembershipStatus::PENDING_PAYMENT->value,
            'registered_at' => now(),
        ]);

        $response = $this->put(route('admin.farmers.update', $farmer), [
            'first_name' => 'Pilar',
            'last_name' => 'Reyes',
            'barangay_id' => $otherBarangay->id,
            'association_id' => $otherAssociation->id,
            'member_type_id' => $memberType->id,
            'status' => FarmerStatus::PENDING->value,
        ]);

        $response->assertRedirect(route('admin.farmers.show', $farmer));
        $this->assertSame(MembershipStatus::PENDING_PAYMENT, $farmer->fresh()->membership_status);
    }

    public function test_setting_farmer_status_to_active_manually_syncs_membership_workflow(): void
    {
        $user = $this->makeAdminUser();
        [$barangay, $association] = $this->seedLookups();
        $memberType = MemberType::query()->where('code', MemberTypeCode::NM->value)->firstOrFail();

        FeeSchedule::create([
            'year' => now()->year,
            'membership_fee' => 100,
            'annual_due' => 100,
            'mortuary_fee' => 150,
            'renewal_deadline' => now()->startOfYear()->addDays(44)->toDateString(),
            'is_active' => true,
            'effective_from' => now()->startOfYear()->toDateString(),
        ]);

        $farmer = Farmer::create([
            'farmer_code' => 'FRM-2026-00032',
            'first_name' => 'Mario',
            'last_name' => 'Flores',
            'barangay_id' => $barangay->id,
            'association_id' => $association->id,
            'member_type_id' => $memberType->id,
            'status' => FarmerStatus::PENDING->value,
            'membership_status' => MembershipStatus::PENDING_DOCUMENTS->value,
            'registered_at' => now(),
        ]);

        $application = MembershipApplication::create([
            'farmer_id' => $farmer->id,
            'application_no' => 'APP-2026-MANUAL1',
            'source' => 'walk_in',
            'status' => ApplicationStatus::SUBMITTED->value,
            'submitted_at' => now(),
        ]);

        app(FarmerDocumentService::class)->ensureApplicationChecklist($application);

        $response = $this->actingAs($user)->put(route('admin.farmers.update', $farmer), [
            'first_name' => 'Mario',
            'last_name' => 'Flores',
            'barangay_id' => $barangay->id,
            'association_id' => $association->id,
            'member_type_id' => $memberType->id,
            'status' => FarmerStatus::ACTIVE->value,
        ]);

        $response->assertRedirect(route('admin.farmers.show', $farmer));

        $farmer->refresh();
        $application->refresh();

        $this->assertSame(FarmerStatus::ACTIVE, $farmer->status);
        $this->assertSame(MembershipStatus::ACTIVE, $farmer->membership_status);
        $this->assertSame($application->id, $farmer->source_application_id);
        $this->assertSame(ApplicationStatus::APPROVED, $application->status);
        $this->assertNotNull($application->approved_at);
        $this->assertTrue($application->documents()->get()->every(function ($document) use ($user): bool {
            return $document->is_received
                && $document->verification_status === DocumentVerificationStatus::VERIFIED
                && $document->verified_by === $user->id;
        }));
        $this->assertDatabaseHas('payment_assessments', [
            'membership_application_id' => $application->id,
            'status' => AssessmentStatus::PAID->value,
        ]);
        $this->assertDatabaseHas('payments', [
            'payment_assessment_id' => PaymentAssessment::query()->where('membership_application_id', $application->id)->value('id'),
            'status' => PaymentStatus::PAID->value,
        ]);
        $this->assertDatabaseHas('membership_ledgers', [
            'farmer_id' => $farmer->id,
            'membership_application_id' => $application->id,
            'payment_status' => PaymentStatus::PAID->value,
        ]);
    }

    public function test_store_rejects_association_from_different_barangay(): void
    {
        [$barangay, , $otherBarangay, $otherAssociation] = $this->seedLookups();
        $memberType = MemberType::query()->where('code', MemberTypeCode::NM->value)->firstOrFail();

        $response = $this->from(route('admin.farmers.create'))->post(route('admin.farmers.store'), [
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'barangay_id' => $barangay->id,
            'association_id' => $otherAssociation->id,
            'member_type_id' => $memberType->id,
            'status' => FarmerStatus::PENDING->value,
        ]);

        $response->assertRedirect(route('admin.farmers.create'));
        $response->assertSessionHasErrors('association_id');
        $this->assertDatabaseCount('farmers', 0);
    }

    public function test_store_requires_manual_member_type_selection(): void
    {
        [$barangay] = $this->seedLookups();

        $response = $this->from(route('admin.farmers.create'))->post(route('admin.farmers.store'), [
            'first_name' => 'Jose',
            'last_name' => 'Villanueva',
            'barangay_id' => $barangay->id,
            'status' => FarmerStatus::PENDING->value,
        ]);

        $response->assertRedirect(route('admin.farmers.create'));
        $response->assertSessionHasErrors('member_type_id');
        $this->assertDatabaseCount('farmers', 0);
    }


    public function test_destroy_deletes_farmer_without_linked_activity(): void
    {
        [$barangay, $association] = $this->seedLookups();
        $memberType = MemberType::query()->where('code', MemberTypeCode::NM->value)->firstOrFail();

        $farmer = Farmer::create([
            'farmer_code' => 'FRM-2026-00011',
            'first_name' => 'Delete',
            'last_name' => 'Candidate',
            'barangay_id' => $barangay->id,
            'association_id' => $association->id,
            'member_type_id' => $memberType->id,
            'status' => FarmerStatus::PENDING->value,
            'registered_at' => now(),
        ]);

        $response = $this->delete(route('admin.farmers.destroy', $farmer));

        $response->assertRedirect(route('admin.farmers.index'));
        $this->assertDatabaseMissing('farmers', ['id' => $farmer->id]);
    }

    public function test_destroy_blocks_farmer_with_membership_activity(): void
    {
        [$barangay, $association] = $this->seedLookups();
        $memberType = MemberType::query()->where('code', MemberTypeCode::NM->value)->firstOrFail();

        $farmer = Farmer::create([
            'farmer_code' => 'FRM-2026-00012',
            'first_name' => 'Locked',
            'last_name' => 'Record',
            'barangay_id' => $barangay->id,
            'association_id' => $association->id,
            'member_type_id' => $memberType->id,
            'status' => FarmerStatus::PENDING->value,
            'registered_at' => now(),
        ]);

        MembershipApplication::create([
            'farmer_id' => $farmer->id,
            'application_no' => 'APP-2026-LOCKED1',
            'source' => 'walk_in',
            'status' => ApplicationStatus::SUBMITTED->value,
            'submitted_at' => now(),
        ]);

        $response = $this->from(route('admin.farmers.index'))->delete(route('admin.farmers.destroy', $farmer));

        $response->assertRedirect(route('admin.farmers.index'));
        $response->assertSessionHasErrors('farmer');
        $this->assertDatabaseHas('farmers', ['id' => $farmer->id]);
    }
    private function seedLookups(): array
    {
        foreach (MemberTypeCode::cases() as $code) {
            MemberType::query()->create([
                'code' => $code->value,
                'name' => $code->label(),
                'is_new_member' => $code->isNewMember(),
                'is_senior' => $code->isSenior(),
                'requires_membership_fee' => ! $code->isNewMember() ? false : true,
                'mortuary_eligible' => ! $code->isSenior(),
            ]);
        }

        $barangay = Barangay::query()->create([
            'name' => 'San Roque',
            'code' => 'BRGY-001',
            'status' => 'active',
        ]);

        $association = Association::query()->create([
            'barangay_id' => $barangay->id,
            'name' => 'San Roque Growers',
            'code' => 'ASSOC-001',
            'status' => 'active',
        ]);

        $otherBarangay = Barangay::query()->create([
            'name' => 'Poblacion',
            'code' => 'BRGY-002',
            'status' => 'active',
        ]);

        $otherAssociation = Association::query()->create([
            'barangay_id' => $otherBarangay->id,
            'name' => 'Poblacion Farmers',
            'code' => 'ASSOC-002',
            'status' => 'active',
        ]);

        return [$barangay, $association, $otherBarangay, $otherAssociation];
    }

    private function makeAdminUser(): User
    {
        Role::findOrCreate(User::ROLE_ADMIN, 'web');

        $user = User::query()->create([
            'name' => 'Office Admin',
            'email' => 'admin' . uniqid() . '@example.test',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        $user->assignRole(User::ROLE_ADMIN);

        return $user;
    }
}



