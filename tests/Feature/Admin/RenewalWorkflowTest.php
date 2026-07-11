<?php

namespace Tests\Feature\Admin;

use App\Models\Association;
use App\Models\Barangay;
use App\Models\FeeSchedule;
use App\Models\Farmer;
use App\Models\MemberType;
use App\Models\RenewalRequest;
use App\Models\User;
use App\Services\Membership\RenewalRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RenewalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_walk_in_renewal_can_be_created_and_completed_after_payment(): void
    {
        $admin = $this->makeAdminUser();
        $farmer = $this->makeFarmer();
        $this->makeFeeSchedule();

        $response = $this->actingAs($admin)->post(route('admin.renewals.store'), [
            'farmer_id' => $farmer->id,
            'year' => now()->year,
            'documents' => [
                'previous_membership_id' => ['is_received' => true],
                'cedula' => ['is_received' => true],
                'office_membership_form' => ['is_received' => true],
            ],
        ]);

        $renewal = RenewalRequest::query()->with('documents')->firstOrFail();

        $response->assertRedirect(route('admin.renewals.show', $renewal));
        $this->assertSame('walk_in', $renewal->source);
        $this->assertCount(3, $renewal->documents);

        $this->actingAs($admin)->post(route('admin.renewals.payment.store', $renewal), [
            'payment_method' => 'cash',
            'amount_paid' => 250,
            'paid_at' => now()->toDateTimeString(),
            'receipt_no' => 'OR-1001',
        ])->assertRedirect(route('admin.renewals.show', $renewal));

        $renewal->refresh();
        $farmer->refresh();

        $this->assertSame('completed', $renewal->status->value);
        $this->assertSame(now()->year, $farmer->last_renewal_year);
        $this->assertDatabaseHas('membership_ledgers', [
            'renewal_request_id' => $renewal->id,
            'farmer_id' => $farmer->id,
            'payment_status' => 'paid',
        ]);
        $this->assertDatabaseHas('payment_assessments', [
            'renewal_request_id' => $renewal->id,
        ]);
    }

    public function test_walk_in_renewal_submission_does_not_create_admin_notification(): void
    {
        $this->makeAdminUser();
        $farmer = $this->makeFarmer();
        $this->makeFeeSchedule();

        $renewal = app(RenewalRequestService::class)->create([
            'farmer_id' => $farmer->id,
            'year' => now()->year,
            'source' => 'walk_in',
        ]);

        $this->assertSame('walk_in', $renewal->source);
        $this->assertDatabaseCount('notifications', 0);
        $this->assertDatabaseCount('notification_recipients', 0);
    }

    private function makeAdminUser(): User
    {
        Role::findOrCreate(User::ROLE_ADMIN, 'web');

        $user = User::query()->create([
            'name' => 'Renewal Admin',
            'email' => 'renewal-admin@example.test',
            'password' => 'secret12345',
            'status' => 'active',
        ]);

        $user->syncRoles([User::ROLE_ADMIN]);

        return $user;
    }

    private function makeFarmer(): Farmer
    {
        $barangay = Barangay::query()->create([
            'name' => 'San Isidro',
            'code' => 'BRGY-' . uniqid(),
            'status' => 'active',
        ]);

        $association = Association::query()->create([
            'barangay_id' => $barangay->id,
            'name' => 'San Isidro Association',
            'code' => 'ASSOC-' . uniqid(),
            'status' => 'active',
        ]);

        $memberType = MemberType::query()->create([
            'code' => 'OM',
            'name' => 'Old Member',
            'is_new_member' => false,
            'is_senior' => false,
            'requires_membership_fee' => false,
            'mortuary_eligible' => true,
        ]);

        return Farmer::query()->create([
            'farmer_code' => 'FRM-2026-00001',
            'first_name' => 'Mateo',
            'last_name' => 'Rivera',
            'birth_date' => '1988-06-12',
            'barangay_id' => $barangay->id,
            'association_id' => $association->id,
            'member_type_id' => $memberType->id,
            'status' => 'active',
            'membership_status' => 'active',
            'registered_at' => now()->subYears(2),
            'activated_at' => now()->subYears(2),
            'last_renewal_year' => now()->year - 1,
        ]);
    }

    private function makeFeeSchedule(): void
    {
        FeeSchedule::query()->create([
            'year' => now()->year,
            'membership_fee' => 0,
            'annual_due' => 100,
            'mortuary_fee' => 150,
            'renewal_deadline' => now()->startOfYear()->addDays(44)->toDateString(),
            'is_active' => true,
            'effective_from' => now()->startOfYear()->toDateString(),
        ]);
    }
}

