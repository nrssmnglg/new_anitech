<?php

namespace Tests\Feature\Api;

use App\Enums\MembershipStatus;
use App\Models\Association;
use App\Models\Barangay;
use App\Models\Farmer;
use App\Models\FarmerProfile;
use App\Models\MemberType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FarmerAuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_farmer_api_login_returns_expected_contract_for_active_member(): void
    {
        [$farmer, $user] = $this->createFarmerMember();

        $response = $this->postJson('/api/farmer/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Welcome back to AniTech Farmer Mobile.')
            ->assertJsonPath('data.account.email', $user->email)
            ->assertJsonPath('data.account.member_type', $farmer->memberType?->name)
            ->assertJsonPath('data.redirect_url', route('farmer.pwa.home'));
    }

    public function test_farmer_api_dashboard_requires_authenticated_farmer_session(): void
    {
        $this->getJson('/api/farmer/dashboard')->assertUnauthorized();
    }

    public function test_farmer_api_logout_returns_redirect_contract(): void
    {
        [$farmer, $user] = $this->createFarmerMember();

        $response = $this->actingAs($user, 'farmer_pwa')
            ->postJson('/api/farmer/logout');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'You have been logged out.')
            ->assertJsonPath('data.redirect_url', route('farmer.pwa.login'));
    }

    private function createFarmerMember(
        string $email = 'farmer.auth@example.test',
        string $mobileNumber = '09170000055',
        string $firstName = 'Lina',
        string $lastName = 'Mercado',
    ): array {
        $barangay = Barangay::query()->create([
            'name' => 'San Isidro ' . strtoupper(substr(md5($email), 0, 4)),
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
            'sex' => 'Female',
            'civil_status' => 'Single',
            'address' => 'Purok 2',
            'mobile_number' => $mobileNumber,
            'birth_date' => '1992-03-12',
        ]);

        $user = User::factory()->create([
            'name' => $firstName . ' ' . $lastName,
            'email' => $email,
            'password' => Hash::make('password'),
            'farmer_id' => $farmer->id,
            'status' => User::STATUS_ACTIVE,
            'role' => User::ROLE_FARMER,
        ]);

        return [$farmer, $user];
    }
}
