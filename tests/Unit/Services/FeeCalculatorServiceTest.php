<?php

namespace Tests\Unit\Services;

use App\Services\Membership\FeeCalculatorService;
use App\Services\Membership\MemberTypeResolverService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FeeCalculatorServiceTest extends TestCase
{
    #[DataProvider('applicationFeeMatrixProvider')]
    public function test_it_applies_schedule_based_application_dues(
        string $memberType,
        float $membershipFee,
        float $annualDue,
        float $mortuaryFee,
        float $total,
        bool $mortuaryEligible,
    ): void {
        $service = new FeeCalculatorService(new MemberTypeResolverService());

        $result = $service->calculateApplication([
            'member_type' => $memberType,
            'fee_schedule' => [
                'membership_fee' => 100.0,
                'annual_due' => 100.0,
                'mortuary_fee' => 150.0,
            ],
        ]);

        $this->assertSame($memberType, $result['member_type']);
        $this->assertSame($membershipFee, $result['membership_fee']);
        $this->assertSame($annualDue, $result['annual_due']);
        $this->assertSame($mortuaryFee, $result['mortuary_fee']);
        $this->assertSame($total, $result['total']);
        $this->assertSame($mortuaryEligible, $result['mortuary_eligible']);
    }

    #[DataProvider('renewalFeeMatrixProvider')]
    public function test_it_excludes_membership_fee_from_schedule_based_renewal_dues(
        string $memberType,
        float $annualDue,
        float $mortuaryFee,
        float $total,
        bool $mortuaryEligible,
    ): void {
        $service = new FeeCalculatorService(new MemberTypeResolverService());

        $result = $service->calculateRenewal([
            'member_type' => $memberType,
            'fee_schedule' => [
                'membership_fee' => 100.0,
                'annual_due' => 100.0,
                'mortuary_fee' => 150.0,
            ],
        ]);

        $this->assertSame($memberType, $result['member_type']);
        $this->assertSame(0.0, $result['membership_fee']);
        $this->assertSame($annualDue, $result['annual_due']);
        $this->assertSame($mortuaryFee, $result['mortuary_fee']);
        $this->assertSame($total, $result['total']);
        $this->assertSame($mortuaryEligible, $result['mortuary_eligible']);
    }

    public function test_it_excludes_membership_fee_from_custom_schedule_renewal_dues(): void
    {
        $service = new FeeCalculatorService(new MemberTypeResolverService());

        $result = $service->calculateRenewal([
            'member_type' => 'NM',
            'fee_schedule' => [
                'membership_fee' => 125.0,
                'annual_due' => 90.0,
                'mortuary_fee' => 140.0,
            ],
        ]);

        $this->assertSame(0.0, $result['membership_fee']);
        $this->assertSame(90.0, $result['annual_due']);
        $this->assertSame(140.0, $result['mortuary_fee']);
        $this->assertSame(230.0, $result['total']);
    }

    public static function applicationFeeMatrixProvider(): array
    {
        return [
            'NM' => ['NM', 100.0, 100.0, 150.0, 350.0, true],
            'OM' => ['OM', 0.0, 100.0, 150.0, 250.0, true],
            'NSC' => ['NSC', 100.0, 100.0, 0.0, 200.0, false],
            'OSC' => ['OSC', 0.0, 100.0, 0.0, 100.0, false],
        ];
    }

    public static function renewalFeeMatrixProvider(): array
    {
        return [
            'NM' => ['NM', 100.0, 150.0, 250.0, true],
            'OM' => ['OM', 100.0, 150.0, 250.0, true],
            'NSC' => ['NSC', 100.0, 0.0, 100.0, false],
            'OSC' => ['OSC', 100.0, 0.0, 100.0, false],
        ];
    }
}
