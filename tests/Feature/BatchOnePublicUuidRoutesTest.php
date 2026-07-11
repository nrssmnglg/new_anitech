<?php

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\Farmer;
use App\Models\MemberType;
use App\Models\MembershipApplication;
use App\Models\Query;
use App\Models\RenewalRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchOnePublicUuidRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_batch_one_models_generate_uuid_based_urls(): void
    {
        $farmer = $this->makeFarmer();
        $query = Query::query()->create([
            'farmer_id' => $farmer->id,
            'subject' => 'Need help',
            'message' => 'Question body',
            'status' => 'open',
            'submitted_at' => now(),
        ]);
        $renewal = RenewalRequest::query()->create([
            'farmer_id' => $farmer->id,
            'year' => now()->year,
            'source' => 'walk_in',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
        $application = MembershipApplication::query()->create([
            'farmer_id' => $farmer->id,
            'application_no' => 'APP-' . now()->format('Y') . '-00001',
            'source' => 'walk_in',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->assertStringEndsWith('/queries/' . $query->uuid, route('admin.queries.show', $query));
        $this->assertStringEndsWith('/farmer/queries/' . $query->uuid, route('farmer.pwa.queries.show', $query));
        $this->assertStringEndsWith('/renewals/' . $renewal->uuid, route('admin.renewals.show', $renewal));
        $this->assertStringEndsWith('/membership-applications/' . $application->uuid, route('admin.membership-applications.show', $application));
    }

    private function makeFarmer(): Farmer
    {
        $memberType = MemberType::query()->create([
            'code' => 'OM',
            'name' => 'Old Member',
            'is_new_member' => false,
            'is_senior' => false,
            'requires_membership_fee' => false,
            'mortuary_eligible' => true,
        ]);
        $barangay = Barangay::query()->create([
            'name' => 'San Roque',
            'code' => 'BRGY-001',
            'status' => 'active',
        ]);

        return Farmer::query()->create([
            'farmer_code' => 'FRM-' . now()->format('Y') . '-00001',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'barangay_id' => $barangay->id,
            'member_type_id' => $memberType->id,
            'status' => 'pending',
            'registered_at' => now(),
        ]);
    }
}
