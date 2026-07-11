<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\FarmerStatus;
use App\Enums\MembershipStatus;
use App\Models\Barangay;
use App\Models\Farmer;
use App\Models\FeeSchedule;
use App\Models\MemberType;
use App\Models\MembershipApplication;
use App\Models\RenewalRequest;
use App\Services\Payments\PaymentAssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentAssessmentSourceRelationTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_relation_uses_membership_application_id(): void
    {
        [$farmer, $memberType] = $this->makeFarmer();
        $this->makeFeeSchedule();

        $application = MembershipApplication::query()->create([
            'farmer_id' => $farmer->id,
            'application_no' => 'APP-' . now()->format('Y') . '-REL001',
            'source' => 'walk_in',
            'status' => ApplicationStatus::APPROVED->value,
            'submitted_at' => now(),
            'approved_at' => now(),
        ]);

        $assessment = app(PaymentAssessmentService::class)->createForApplication($application->load('farmer.memberType'));

        $this->assertSame($application->id, $assessment->membership_application_id);
        $this->assertNull($assessment->renewal_request_id);
        $this->assertTrue($application->paymentAssessments()->whereKey($assessment->id)->exists());
        $this->assertSame('Application', $assessment->source_label);
        $this->assertSame($application->application_no, $assessment->source_reference);
        $this->assertSame('Application ' . $application->application_no, $assessment->source_display);
        $this->assertDatabaseHas('payment_assessments', [
            'id' => $assessment->id,
            'membership_application_id' => $application->id,
        ]);
    }

    public function test_renewal_relation_uses_renewal_request_id(): void
    {
        [$farmer, $memberType] = $this->makeFarmer('OM', 'Old Member', false);
        $this->makeFeeSchedule(0);

        $renewal = RenewalRequest::query()->create([
            'farmer_id' => $farmer->id,
            'year' => now()->year,
            'source' => 'walk_in',
            'status' => 'approved',
            'submitted_at' => now(),
            'approved_at' => now(),
        ]);

        $assessment = app(PaymentAssessmentService::class)->createForRenewal($renewal->load('farmer.memberType'));

        $this->assertSame($renewal->id, $assessment->renewal_request_id);
        $this->assertNull($assessment->membership_application_id);
        $this->assertTrue($renewal->paymentAssessments()->whereKey($assessment->id)->exists());
        $this->assertSame('Renewal', $assessment->source_label);
        $this->assertSame('Year ' . $renewal->year, $assessment->source_reference);
        $this->assertSame('Renewal Year ' . $renewal->year, $assessment->source_display);
        $this->assertDatabaseHas('payment_assessments', [
            'id' => $assessment->id,
            'renewal_request_id' => $renewal->id,
        ]);
    }

    private function makeFarmer(string $memberTypeCode = 'NM', string $memberTypeName = 'New Member', bool $requiresMembershipFee = true): array
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
            'requires_membership_fee' => $requiresMembershipFee,
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

    private function makeFeeSchedule(float $membershipFee = 100): void
    {
        FeeSchedule::query()->create([
            'year' => now()->year,
            'membership_fee' => $membershipFee,
            'annual_due' => 100,
            'mortuary_fee' => 150,
            'renewal_deadline' => now()->startOfYear()->addDays(44)->toDateString(),
            'is_active' => true,
            'effective_from' => now()->startOfYear()->toDateString(),
        ]);
    }
}
