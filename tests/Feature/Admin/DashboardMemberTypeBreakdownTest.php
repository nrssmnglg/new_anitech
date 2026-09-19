<?php

namespace Tests\Feature\Admin;

use App\Enums\MembershipStatus;
use App\Http\Controllers\Admin\DashboardController;
use App\Models\Barangay;
use App\Models\Farmer;
use App\Models\MemberType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardMemberTypeBreakdownTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_type_totals_exclude_pending_applications(): void
    {
        $type = MemberType::query()->create(['code' => 'NM', 'name' => 'New Member']);
        $barangay = Barangay::query()->create(['name' => 'San Roque', 'code' => 'SR', 'status' => 'Active']);

        foreach ([MembershipStatus::PENDING_APPLICATION, MembershipStatus::PENDING_PAYMENT, MembershipStatus::ACTIVE] as $index => $status) {
            Farmer::query()->create([
                'farmer_code' => 'FRM-2026-' . ($index + 1),
                'barangay_id' => $barangay->id,
                'member_type_id' => $type->id,
                'membership_status' => $status,
                'registered_at' => now(),
            ]);
        }

        $method = new \ReflectionMethod(DashboardController::class, 'memberTypeBreakdown');
        $breakdown = $method->invoke(app(DashboardController::class), now()->year, $barangay->id);

        $this->assertSame([['label' => 'New Member', 'total' => 1]], $breakdown);
    }
}
