<?php

namespace Database\Seeders;

use App\Enums\MembershipStatus;
use App\Models\Association;
use App\Models\Barangay;
use App\Models\Farmer;
use App\Models\FarmerProfile;
use App\Models\MemberType;
use App\Models\User;
use Illuminate\Database\Seeder;

class FarmerPwaDemoAccountSeeder extends Seeder
{
    public function run(): void
    {
        $barangay = Barangay::query()->orderBy('id')->firstOrFail();
        $association = Association::query()
            ->where('barangay_id', $barangay->id)
            ->orderBy('id')
            ->firstOrFail();

        $memberType = MemberType::query()->firstOrCreate(
            ['code' => 'NM'],
            [
                'name' => 'New Member',
                'requires_membership_fee' => true,
                'mortuary_eligible' => true,
                'status' => 'Active',
            ],
        );

        $farmer = Farmer::query()->updateOrCreate(
            ['farmer_code' => 'FRM-DEMO-0001'],
            [
                'barangay_id' => $barangay->id,
                'association_id' => $association->id,
                'member_type_id' => $memberType->id,
                'membership_status' => MembershipStatus::ACTIVE,
                'record_origin' => 'Seeder',
                'registered_at' => now()->subYear(),
                'activated_at' => now()->subMonths(11),
                'inactive_at' => null,
                'inactive_reason' => null,
            ],
        );

        FarmerProfile::query()->updateOrCreate(
            ['farmer_id' => $farmer->id],
            [
                'first_name' => 'Demo',
                'middle_name' => 'Farmer',
                'last_name' => 'Member',
                'sex' => 'Male',
                'civil_status' => 'Single',
                'address' => 'Purok 1, ' . $barangay->name,
                'mobile_number' => '09171234567',
                'birth_date' => '1995-05-15',
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'farmer@anitech.test'],
            [
                'name' => 'Demo Farmer Member',
                'password' => 'farmer12345',
                'farmer_id' => $farmer->id,
                'role' => User::ROLE_FARMER,
                'status' => User::STATUS_ACTIVE,
            ],
        );
    }
}
