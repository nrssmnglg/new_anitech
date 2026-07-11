<?php

namespace Tests\Unit\Services;

use App\Services\Documents\DocumentRequirementService;
use App\Services\Membership\FeeCalculatorService;
use App\Services\Membership\MemberTypeResolverService;
use App\Services\Payments\PaymentAssessmentService;
use PHPUnit\Framework\TestCase;

class PaymentAssessmentServiceTest extends TestCase
{
    public function test_it_exposes_the_fixed_fee_breakdown_in_assessments(): void
    {
        $resolver = new MemberTypeResolverService();
        $service = new PaymentAssessmentService(
            new FeeCalculatorService($resolver),
            new DocumentRequirementService(),
        );

        $assessment = $service->assess('application', ['member_type' => 'NSC']);

        $this->assertSame(100.0, $assessment['membership_fee']);
        $this->assertSame(100.0, $assessment['annual_due']);
        $this->assertSame(0.0, $assessment['mortuary_fee']);
        $this->assertFalse($assessment['mortuary_eligible']);
        $this->assertSame(200.0, $assessment['amount_due']);
    }
}
