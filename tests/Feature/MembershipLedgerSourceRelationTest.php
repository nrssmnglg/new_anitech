<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\FarmerStatus;
use App\Enums\MembershipStatus;
use App\Models\Barangay;
use App\Models\Farmer;
use App\Models\MembershipApplication;
use App\Models\MembershipLedger;
use App\Models\MemberType;
use App\Models\RenewalRequest;
use App\Services\Membership\MembershipLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipLedgerSourceRelationTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_relation_uses_membership_application_id(): void
    {
        [$farmer] = $this->makeFarmer();

        $application = MembershipApplication::query()->create([
            'farmer_id' => $farmer->id,
            'application_no' => 'APP-' . now()->format('Y') . '-LED001',
            'source' => 'walk_in',
            'status' => ApplicationStatus::APPROVED->value,
            'submitted_at' => now(),
            'approved_at' => now(),
        ]);

        $payload = app(MembershipLedgerService::class)->buildFromSource(
            'application',
            [
                'id' => $application->id,
                'farmer_id' => $farmer->id,
                'year' => now()->year,
                'status' => 'approved',
                'member_type' => 'NM',
            ],
            [
                'member_type' => 'NM',
                'membership_fee' => 100,
                'annual_due' => 100,
                'mortuary_fee' => 150,
                'amount_due' => 350,
                'mortuary_eligible' => true,
            ],
            [
                'paid_amount' => 350,
                'paid_at' => now(),
                'payment_status' => 'paid',
            ],
        );

        $ledger = MembershipLedger::query()->create($payload);

        $this->assertSame($application->id, $ledger->membership_application_id);
        $this->assertNull($ledger->renewal_request_id);
        $this->assertTrue($application->membershipLedgers()->whereKey($ledger->id)->exists());
        $this->assertSame('Application', $ledger->source_label);
        $this->assertSame($application->application_no, $ledger->fresh()->load('membershipApplication')->source_reference);
        $this->assertSame('Application ' . $application->application_no, $ledger->fresh()->load('membershipApplication')->source_display);
        $this->assertDatabaseHas('membership_ledgers', [
            'id' => $ledger->id,
            'membership_application_id' => $application->id,
        ]);
    }

    public function test_renewal_relation_uses_renewal_request_id(): void
    {
        [$farmer] = $this->makeFarmer('OM', 'Old Member');

        $renewal = RenewalRequest::query()->create([
            'farmer_id' => $farmer->id,
            'year' => now()->year,
            'source' => 'walk_in',
            'status' => 'approved',
            'submitted_at' => now(),
            'approved_at' => now(),
        ]);

        $payload = app(MembershipLedgerService::class)->buildFromSource(
            'renewal',
            [
                'id' => $renewal->id,
                'farmer_id' => $farmer->id,
                'year' => $renewal->year,
                'status' => 'approved',
                'member_type' => 'OM',
            ],
            [
                'member_type' => 'OM',
                'membership_fee' => 0,
                'annual_due' => 100,
                'mortuary_fee' => 150,
                'amount_due' => 250,
                'mortuary_eligible' => true,
            ],
            [
                'paid_amount' => 250,
                'paid_at' => now(),
                'payment_status' => 'paid',
            ],
        );

        $ledger = MembershipLedger::query()->create($payload);

        $this->assertSame($renewal->id, $ledger->renewal_request_id);
        $this->assertNull($ledger->membership_application_id);
        $this->assertTrue($renewal->membershipLedgers()->whereKey($ledger->id)->exists());
        $this->assertSame('Renewal', $ledger->source_label);
        $this->assertSame('Year ' . $renewal->year, $ledger->fresh()->load('renewalRequest')->source_reference);
        $this->assertSame('Renewal Year ' . $renewal->year, $ledger->fresh()->load('renewalRequest')->source_display);
        $this->assertDatabaseHas('membership_ledgers', [
            'id' => $ledger->id,
            'renewal_request_id' => $renewal->id,
        ]);
    }

    private function makeFarmer(string $memberTypeCode = 'NM', string $memberTypeName = 'New Member'): array
    {
        $barangay = Barangay::query()->create([
            'name' => 'San Roque',
            'code' => 'BRGY-' . uniqid(),
            'status' => 'active',
        ]);

        $memberType = MemberType::query()->create([
            'code' => $memberTypeCode,
            'name' => $memberTypeName,
            'is_new_member' => $memberTypeCode === 'NM',
            'is_senior' => false,
            'requires_membership_fee' => $memberTypeCode === 'NM',
            'mortuary_eligible' => true,
        ]);

        $farmer = Farmer::query()->create([
            'farmer_code' => 'FRM-' . now()->format('Y') . '-' . rand(10000, 99999),
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'barangay_id' => $barangay->id,
            'member_type_id' => $memberType->id,
            'status' => FarmerStatus::ACTIVE->value,
            'membership_status' => MembershipStatus::ACTIVE->value,
            'registered_at' => now(),
        ]);

        return [$farmer, $memberType];
    }
}
