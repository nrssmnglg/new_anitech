<?php

namespace Tests\Feature;

use App\Models\Association;
use App\Models\Barangay;
use App\Models\Farmer;
use App\Models\MemberType;
use App\Models\MembershipLedger;
use App\Models\MortuaryClaim;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchTwoPublicUuidRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_batch_two_models_generate_uuid_based_urls(): void
    {
        $farmer = $this->makeFarmer();
        $user = User::query()->create([
            'name' => 'Office Admin',
            'email' => 'admin@example.test',
            'password' => 'secret123',
            'status' => 'active',
        ]);
        $ledger = MembershipLedger::query()->create([
            'farmer_id' => $farmer->id,
            'year' => now()->year,
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
        ]);
        $claim = MortuaryClaim::query()->create([
            'farmer_id' => $farmer->id,
            'membership_ledger_id' => $ledger->id,
            'claim_reference' => 'MC-' . now()->format('Y') . '-00001',
            'claim_amount' => 5000,
            'claim_date' => now()->toDateString(),
            'status' => 'pending',
        ]);

        $this->assertStringEndsWith('/farmers/' . $farmer->uuid, route('admin.farmers.show', $farmer));
        $this->assertStringEndsWith('/users/' . $user->uuid, route('admin.users.show', $user));
        $this->assertStringEndsWith('/mortuary-claims/' . $claim->uuid, route('admin.mortuary-claims.show', $claim));
    }

    private function makeFarmer(): Farmer
    {
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
        $memberType = MemberType::query()->create([
            'code' => 'OM',
            'name' => 'Old Member',
            'is_new_member' => false,
            'is_senior' => false,
            'requires_membership_fee' => false,
            'mortuary_eligible' => true,
        ]);

        return Farmer::query()->create([
            'farmer_code' => 'FRM-' . now()->format('Y') . '-00001',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'barangay_id' => $barangay->id,
            'association_id' => $association->id,
            'member_type_id' => $memberType->id,
            'status' => 'active',
            'registered_at' => now(),
        ]);
    }
}
