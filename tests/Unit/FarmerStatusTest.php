<?php

namespace Tests\Unit;

use App\Enums\FarmerStatus;
use App\Enums\MembershipStatus;
use App\Models\Farmer;
use Tests\TestCase;

class FarmerStatusTest extends TestCase
{
    public function test_inactive_date_marks_farmer_inactive_even_without_reason(): void
    {
        $farmer = new Farmer([
            'membership_status' => MembershipStatus::ACTIVE->value,
            'inactive_at' => now(),
            'inactive_reason' => null,
        ]);

        $this->assertSame(FarmerStatus::INACTIVE, $farmer->status);
    }

    public function test_deceased_reason_takes_priority_over_inactive_status(): void
    {
        $farmer = new Farmer([
            'membership_status' => MembershipStatus::ACTIVE->value,
            'inactive_at' => now(),
            'inactive_reason' => 'Deceased record',
        ]);

        $this->assertSame(FarmerStatus::DECEASED, $farmer->status);
    }

    public function test_active_membership_without_inactive_markers_is_active(): void
    {
        $farmer = new Farmer([
            'membership_status' => MembershipStatus::ACTIVE->value,
            'inactive_at' => null,
            'inactive_reason' => null,
        ]);

        $this->assertSame(FarmerStatus::ACTIVE, $farmer->status);
    }
}
