<?php

namespace Tests\Unit\Services;

use App\Models\FeeSchedule;
use App\Services\Membership\FeeCalculatorService;
use App\Services\Membership\MemberTypeResolverService;
use App\Services\Payments\PaymentAssessmentService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class PaymentAssessmentServiceTest extends TestCase
{
    public function test_same_year_legacy_new_member_includes_membership_fee(): void
    {
        $assessment = $this->calculateRenewalFees(true);

        $this->assertSame(100.0, $assessment['membership_fee']);
        $this->assertSame(100.0, $assessment['annual_due']);
        $this->assertSame(150.0, $assessment['mortuary_fee']);
        $this->assertSame(350.0, $assessment['total']);
    }

    public function test_regular_new_member_renewal_excludes_membership_fee(): void
    {
        $assessment = $this->calculateRenewalFees(false);

        $this->assertSame(0.0, $assessment['membership_fee']);
        $this->assertSame(100.0, $assessment['annual_due']);
        $this->assertSame(150.0, $assessment['mortuary_fee']);
        $this->assertSame(250.0, $assessment['total']);
    }

    private function calculateRenewalFees(bool $includeMembershipFee): array
    {
        $service = new PaymentAssessmentService(
            new FeeCalculatorService(new MemberTypeResolverService()),
        );
        $method = new ReflectionMethod(PaymentAssessmentService::class, 'calculateRenewalFees');
        $feeSchedule = new FeeSchedule([
            'membership_fee' => 100,
            'annual_due' => 100,
            'mortuary_fee' => 150,
        ]);

        return $method->invoke($service, 'NM', $feeSchedule, $includeMembershipFee);
    }
}
