<?php

namespace Database\Seeders;

use App\Enums\MembershipStatus;
use App\Models\Barangay;
use App\Models\Farmer;
use App\Models\MemberType;
use App\Models\Query;
use App\Models\QueryCategory;
use App\Models\QueryResponse;
use App\Models\User;
use Illuminate\Database\Seeder;

class SampleQuerySeeder extends Seeder
{
    public function run(): void
    {
        $category = QueryCategory::query()->updateOrCreate(
            ['code' => 'CROP-BLIGHT'],
            [
                'name' => 'Crop Blight Concern',
                'description' => 'Farmer reports related to visible crop disease or blight symptoms.',
                'status' => 'Active',
            ],
        );

        $farmer = Farmer::query()
            ->with('profile')
            ->whereHas('profile')
            ->first();

        if ($farmer === null) {
            $barangay = Barangay::query()->firstOrFail();
            $memberType = MemberType::query()->firstOrFail();

            $farmer = Farmer::query()->create([
                'farmer_code' => 'AF-2024-0892',
                'barangay_id' => $barangay->id,
                'association_id' => null,
                'member_type_id' => $memberType->id,
                'membership_status' => MembershipStatus::ACTIVE,
                'record_origin' => 'Seeder',
                'registered_at' => now()->subMonths(8),
                'activated_at' => now()->subMonths(8),
            ]);

            $farmer->profile()->create([
                'first_name' => 'Federico',
                'middle_name' => null,
                'last_name' => 'Santos',
                'suffix' => null,
                'sex' => 'Male',
                'civil_status' => 'Married',
                'birth_date' => '1987-05-14',
                'address' => 'San Roque Proper',
                'mobile_number' => '09171234567',
            ]);
        }

        $query = Query::query()->updateOrCreate(
            [
                'farmer_id' => $farmer->id,
                'subject' => 'Crop Blight in Sector 4',
            ],
            [
                'category_id' => $category->id,
                'message' => 'Good morning. Brown spots are spreading across the rice leaves in Sector 4 after the recent rain. I already isolated the affected rows and need advice on whether this looks like leaf blight and what treatment to apply next.',
                'status' => 'In Progress',
                'archived_at' => null,
                'created_at' => now()->subHours(3),
            ],
        );

        $responder = User::query()
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_STAFF])
            ->orderByRaw("case when role = ? then 0 else 1 end", [User::ROLE_STAFF])
            ->first();

        QueryResponse::query()->updateOrCreate(
            [
                'query_id' => $query->id,
                'message' => 'Hello Mr. Santos. We have received your report. Based on the symptoms and the current weather pattern, this looks consistent with an early blight case. Avoid additional high-nitrogen fertilizer for now and keep the affected area separated while our team prepares the next recommendation.',
            ],
            [
                'responded_by' => $responder?->id,
                'responded_at' => now()->subHours(2),
            ],
        );
    }
}
