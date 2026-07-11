<?php

namespace Tests\Feature\Admin;

use App\Enums\FarmerStatus;
use App\Enums\MembershipStatus;
use App\Models\Association;
use App\Models\Barangay;
use App\Models\Farmer;
use App\Models\MemberType;
use App\Models\MortuaryClaim;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MortuaryClaimWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_mortuary_claim_marks_farmer_as_deceased(): void
    {
        $admin = $this->makeAdminUser();
        $farmer = $this->makeFarmer();
        $ledgerId = $this->makeMembershipLedger($farmer);

        $claim = MortuaryClaim::query()->create([
            'farmer_id' => $farmer->id,
            'membership_ledger_id' => $ledgerId,
            'claim_reference' => 'MC-' . now()->format('Ymd') . '-TEST01',
            'claim_amount' => 150.00,
            'claim_date' => now()->toDateString(),
            'claimer_name' => 'Maria Rivera',
            'claimer_relationship' => 'Spouse',
            'claimer_contact_number' => '09170000001',
            'claimer_address' => 'Sitio Uno',
            'claimer_valid_id_received' => true,
            'proof_of_relationship_received' => true,
            'death_certificate_disk' => 'public',
            'death_certificate_path' => 'mortuary-claims/test-certificate.pdf',
            'death_certificate_original_name' => 'test-certificate.pdf',
            'death_certificate_mime_type' => 'application/pdf',
            'status' => 'pending',
            'filed_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.mortuary-claims.review', $claim), [
            'action' => 'approve',
        ]);

        $response->assertRedirect(route('admin.mortuary-claims.show', $claim));

        $claim->refresh();
        $farmer->refresh();

        $this->assertSame('released', $claim->status);
        $this->assertSame($admin->id, $claim->approved_by);
        $this->assertSame($admin->id, $claim->released_by);
        $this->assertSame(FarmerStatus::DECEASED, $farmer->status);
    }

    private function makeAdminUser(): User
    {
        Role::findOrCreate(User::ROLE_ADMIN, 'web');

        $user = User::query()->create([
            'name' => 'Mortuary Admin',
            'email' => 'mortuary-admin-' . uniqid() . '@example.test',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        $user->assignRole(User::ROLE_ADMIN);

        return $user;
    }

    private function makeFarmer(): Farmer
    {
        $barangay = Barangay::query()->create([
            'name' => 'San Miguel',
            'code' => 'BRGY-' . uniqid(),
            'status' => 'active',
        ]);

        $association = Association::query()->create([
            'barangay_id' => $barangay->id,
            'name' => 'San Miguel Association',
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
            'farmer_code' => 'FRM-' . now()->format('Y') . '-90001',
            'first_name' => 'Mateo',
            'last_name' => 'Rivera',
            'birth_date' => '1980-04-12',
            'barangay_id' => $barangay->id,
            'association_id' => $association->id,
            'member_type_id' => $memberType->id,
            'status' => FarmerStatus::ACTIVE->value,
            'membership_status' => MembershipStatus::ACTIVE->value,
            'registered_at' => now()->subYears(3),
            'activated_at' => now()->subYears(3),
            'last_renewal_year' => now()->year,
        ]);
    }

    private function makeMembershipLedger(Farmer $farmer): int
    {
        return DB::table('membership_ledgers')->insertGetId([
            'farmer_id' => $farmer->id,
            'year' => now()->year,
            'membership_application_id' => null,
            'renewal_request_id' => null,
            'member_type_snapshot' => 'OM',
            'membership_fee' => 0,
            'annual_due' => 100,
            'mortuary_fee' => 150,
            'total_amount_due' => 250,
            'amount_paid' => 250,
            'paid_at' => now(),
            'payment_status' => 'paid',
            'mortuary_eligible' => true,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
