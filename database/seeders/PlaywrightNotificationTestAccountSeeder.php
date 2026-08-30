<?php

namespace Database\Seeders;

use App\Models\Farmer;
use App\Models\FarmerProfile;
use App\Models\Association;
use App\Models\Barangay;
use App\Models\MemberType;
use App\Models\MortuaryClaim;
use App\Models\User;
use App\Services\Farmers\LegacyMembershipRecorderService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class PlaywrightNotificationTestAccountSeeder extends Seeder
{
    public function run(): void
    {
        $barangay = Barangay::query()->where('name', 'Abanon')->firstOrFail();
        $association = Association::query()->where('barangay_id', $barangay->id)->firstOrFail();
        $oldMember = MemberType::query()->where('code', 'OM')->firstOrFail();

        $farmers = collect(range(1, 3))->map(function (int $sequence) use ($association, $barangay, $oldMember): Farmer {
            $farmer = Farmer::query()->updateOrCreate(
                ['farmer_code' => sprintf('PW-2026-%05d', $sequence)],
                [
                    'barangay_id' => $barangay->id,
                    'association_id' => $association->id,
                    'member_type_id' => $oldMember->id,
                    'membership_status' => 'active',
                    'record_origin' => 'playwright_test',
                    'registered_at' => now(),
                    'activated_at' => now(),
                    'inactive_at' => null,
                    'inactive_reason' => null,
                ],
            );

            FarmerProfile::query()->updateOrCreate(
                ['farmer_id' => $farmer->id],
                [
                    'first_name' => 'Playwright',
                    'last_name' => 'Test Farmer ' . $sequence,
                    'sex' => 'male',
                    'birth_date' => '1990-01-01',
                    'address' => 'Automated test record, Abanon',
                    'mobile_number' => null,
                ],
            );

            return $farmer;
        });

        $farmer = $farmers->first();

        // This isolated farmer is used only by the mortuary-claim browser test.
        // Re-seeding removes its previous test claim and restores it to an active state.
        $mortuaryFarmer = Farmer::query()->updateOrCreate(
            ['farmer_code' => 'PW-2026-00004'],
            [
                'barangay_id' => $barangay->id,
                'association_id' => $association->id,
                'member_type_id' => $oldMember->id,
                'membership_status' => 'active',
                'record_origin' => 'playwright_test',
                'registered_at' => now(),
                'activated_at' => now(),
                'inactive_at' => null,
                'inactive_reason' => null,
            ],
        );

        FarmerProfile::query()->updateOrCreate(
            ['farmer_id' => $mortuaryFarmer->id],
            [
                'first_name' => 'Playwright Mortuary',
                'last_name' => 'Test Farmer',
                'sex' => 'male',
                'birth_date' => '1990-01-01',
                'address' => 'Automated mortuary-claim test record, Abanon',
                'mobile_number' => null,
            ],
        );

        MortuaryClaim::query()
            ->whereHas('membershipLedger.membershipTransaction', fn ($query) => $query->where('farmer_id', $mortuaryFarmer->id))
            ->delete();

        app(LegacyMembershipRecorderService::class)->syncForOldRecord($mortuaryFarmer, null);

        Role::findOrCreate(User::ROLE_FARMER, 'web');

        $user = User::query()->updateOrCreate(
            ['email' => 'playwright-notify-test@anitech.local'],
            [
                'name' => 'Playwright Test Farmer',
                'password' => Hash::make('PlaywrightNotificationTestOnly!2026'),
                'farmer_id' => $farmer->id,
                'role' => User::ROLE_FARMER,
                'status' => User::STATUS_ACTIVE,
            ],
        );

        $user->syncRoles([User::ROLE_FARMER]);
    }
}
