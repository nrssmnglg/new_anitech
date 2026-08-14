<?php

namespace Tests\Feature\Admin;

use App\Models\FeeSchedule;
use App\Models\MemberType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FeeScheduleSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_fee_configuration_searches_by_member_type_and_preserves_filter(): void
    {
        $admin = $this->makeAdminUser();
        $oldMember = $this->makeMemberType('OM', 'Old Member');
        $seniorMember = $this->makeMemberType('OSC', 'Old Senior Citizen');
        $this->makeFeeSchedule($oldMember, 2025, false);
        $this->makeFeeSchedule($seniorMember, 2026, true);

        $response = $this->actingAs($admin)->get(route('admin.fee-schedules.index', [
            'search' => 'Senior',
        ]));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/FeeSchedules/Index')
                ->where('filters.search', 'Senior')
                ->where('schedules.total', 1)
                ->where('schedules.data.0.memberType.code', 'OSC')
            );
    }

    private function makeAdminUser(): User
    {
        Role::findOrCreate(User::ROLE_ADMIN, 'web');

        $user = User::query()->create([
            'name' => 'Fee Administrator',
            'email' => 'fees@example.test',
            'password' => 'secret123',
            'status' => 'active',
        ]);
        $user->assignRole(User::ROLE_ADMIN);

        return $user;
    }

    private function makeMemberType(string $code, string $name): MemberType
    {
        return MemberType::query()->create([
            'code' => $code,
            'name' => $name,
            'requires_membership_fee' => false,
            'mortuary_eligible' => true,
            'status' => 'active',
        ]);
    }

    private function makeFeeSchedule(MemberType $memberType, int $year, bool $active): FeeSchedule
    {
        return FeeSchedule::query()->create([
            'member_type_id' => $memberType->id,
            'year' => $year,
            'membership_fee' => 0,
            'annual_due' => 100,
            'mortuary_fee' => 150,
            'renewal_deadline' => $year . '-12-31',
            'effective_from' => $year . '-01-01',
            'effective_to' => $year . '-12-31',
            'is_active' => $active,
        ]);
    }
}
