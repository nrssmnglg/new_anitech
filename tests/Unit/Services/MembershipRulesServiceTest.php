<?php

namespace Tests\Unit\Services;

use App\Services\Documents\DocumentRequirementService;
use App\Services\Membership\FeeCalculatorService;
use App\Services\Membership\MembershipLedgerService;
use App\Services\Membership\MembershipStatusService;
use App\Services\Membership\MemberTypeResolverService;
use App\Services\Membership\RenewalService;
use App\Services\Membership\RenewalWorkflowService;
use App\Services\Payments\PaymentAssessmentService;
use PHPUnit\Framework\TestCase;

class MembershipRulesServiceTest extends TestCase
{
    public function test_age_sixty_or_above_means_senior(): void
    {
        $service = new MemberTypeResolverService();

        $this->assertTrue($service->isSeniorCitizen(['age' => 60]));
        $this->assertSame('NSC', $service->resolve(['age' => 60]));
    }

    public function test_no_renewal_in_five_years_means_inactive(): void
    {
        $service = new MembershipStatusService();

        $this->assertTrue($service->shouldTagInactive([
            'status' => 'active',
            'last_renewal_year' => 2021,
        ], 5, 2026));

        $this->assertFalse($service->shouldTagInactive([
            'status' => 'active',
            'last_renewal_year' => 2022,
        ], 5, 2026));
    }

    public function test_renewal_deadline_is_february_fourteen(): void
    {
        $renewalService = new RenewalService(
            new RenewalWorkflowService(),
            new PaymentAssessmentService(new FeeCalculatorService(new MemberTypeResolverService()), new DocumentRequirementService()),
            new MembershipLedgerService(),
        );

        $this->assertFalse($renewalService->isLate(['submitted_at' => '2026-02-14 09:00:00']));
        $this->assertTrue($renewalService->isLate(['submitted_at' => '2026-02-15 09:00:00']));
    }

    public function test_membership_ledger_service_normalizes_paid_at_to_database_datetime(): void
    {
        $service = new MembershipLedgerService();

        $ledger = $service->buildFromSource(
            'application',
            [
                'id' => 3,
                'farmer_id' => 3,
                'year' => 2026,
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
                'paid_at' => '2026-03-27T02:39:00.000000Z',
                'payment_status' => 'paid',
            ],
        );

        $this->assertSame(3, $ledger['membership_application_id']);
        $this->assertNull($ledger['renewal_request_id']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $ledger['paid_at']);
        $this->assertStringNotContainsString('T', $ledger['paid_at']);
        $this->assertStringNotContainsString('Z', $ledger['paid_at']);
    }
}
