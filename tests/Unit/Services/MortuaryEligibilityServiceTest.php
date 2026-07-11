<?php

namespace Tests\Unit\Services;

use App\Services\Mortuary\MortuaryEligibilityService;
use PHPUnit\Framework\TestCase;

class MortuaryEligibilityServiceTest extends TestCase
{
    public function test_seniors_are_not_eligible_for_mortuary(): void
    {
        $service = new MortuaryEligibilityService();

        $result = $service->evaluate(
            ['member_type' => 'OSC', 'birth_date' => '1950-01-01', 'status' => 'active'],
            ['member_type_snapshot' => 'OSC', 'mortuary_eligible' => false, 'payment_status' => 'paid', 'year' => (int) date('Y')],
            date('Y-m-d')
        );

        $this->assertFalse($result['eligible']);
        $this->assertContains('Seniors are not eligible for mortuary.', $result['reasons']);
    }
}
