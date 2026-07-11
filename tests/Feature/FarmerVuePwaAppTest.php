<?php

namespace Tests\Feature;

use App\Enums\MembershipStatus;
use App\Models\Association;
use App\Models\Barangay;
use App\Models\Farmer;
use App\Models\FarmerProfile;
use App\Models\MemberType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FarmerVuePwaAppTest extends TestCase
{
    use RefreshDatabase;

    public function test_farmer_vue_pwa_shell_is_publicly_available_for_guests(): void
    {
        $response = $this->get(route('farmer.pwa.app'));

        $response
            ->assertOk()
            ->assertSee('AniTech Farmer PWA')
            ->assertSee('"authenticated":false', false)
            ->assertSee('"apiBase":"https:\\/\\/localhost:8000\\/api\\/farmer"', false)
            ->assertSee('"appBase":"https:\\/\\/localhost:8000\\/farmer\\/app"', false);
    }

    public function test_farmer_vue_pwa_shell_includes_authenticated_user_context(): void
    {
        [$farmer, $user] = $this->createFarmerMember();

        $response = $this->actingAs($user, 'farmer_pwa')
            ->get('/farmer/app/dashboard');

        $response
            ->assertOk()
            ->assertSee('"authenticated":true', false)
            ->assertSee($user->email, false)
            ->assertSee($user->name, false);
    }

    private function createFarmerMember(
        string $email = 'farmer.vue@example.test',
        string $mobileNumber = '09170000077',
        string $firstName = 'Marco',
        string $lastName = 'Ramos',
    ): array {
        $barangay = Barangay::query()->create([
            'name' => 'San Mateo ' . strtoupper(substr(md5($email), 0, 4)),
            'code' => 'BRGY-' . strtoupper(substr(md5($email), 0, 6)),
            'status' => 'Active',
        ]);

        $association = Association::query()->create([
            'barangay_id' => $barangay->id,
            'code' => 'ASC-' . strtoupper(substr(md5($email . '-assoc'), 0, 6)),
            'name' => 'Association ' . strtoupper(substr(md5($email), 0, 4)),
            'status' => 'Active',
        ]);

        $memberType = MemberType::query()->create([
            'code' => 'REG-' . strtoupper(substr(md5($email . '-member'), 0, 4)),
            'name' => 'Regular Member ' . strtoupper(substr(md5($email), 0, 4)),
            'requires_membership_fee' => true,
            'mortuary_eligible' => true,
            'status' => 'Active',
        ]);

        $farmer = Farmer::query()->create([
            'farmer_code' => 'FRM-' . strtoupper(substr(md5($email . '-farmer'), 0, 8)),
            'barangay_id' => $barangay->id,
            'association_id' => $association->id,
            'member_type_id' => $memberType->id,
            'membership_status' => MembershipStatus::ACTIVE,
            'record_origin' => 'mobile',
            'registered_at' => now()->subYear(),
            'activated_at' => now()->subMonths(11),
        ]);

        FarmerProfile::query()->create([
            'farmer_id' => $farmer->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'sex' => 'Male',
            'civil_status' => 'Single',
            'address' => 'Purok 3',
            'mobile_number' => $mobileNumber,
            'birth_date' => '1994-07-18',
        ]);

        $user = User::factory()->create([
            'name' => $firstName . ' ' . $lastName,
            'email' => $email,
            'farmer_id' => $farmer->id,
            'status' => User::STATUS_ACTIVE,
            'role' => User::ROLE_FARMER,
        ]);

        return [$farmer, $user];
    }
}
