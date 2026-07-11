<?php

namespace Database\Seeders;

use App\Enums\FarmerStatus;
use App\Enums\MemberTypeCode;
use App\Enums\MembershipStatus;
use App\Enums\PaymentStatus;
use App\Models\Association;
use App\Models\Barangay;
use App\Models\Farmer;
use App\Models\MemberType;
use App\Models\MembershipLedger;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class OldMemberWithout2026RenewalWithAccountSeederTwo extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $barangay = Barangay::query()->updateOrCreate(
                ['code' => 'BRGY02'],
                [
                    'name' => 'AGDAO',
                    'status' => 'active',
                ],
            );

            $association = Association::query()->updateOrCreate(
                ['code' => 'ASSOC02'],
                [
                    'barangay_id' => $barangay->id,
                    'name' => 'AGDAO FARMERS ASSOCIATION',
                    'status' => 'active',
                ],
            );

            $memberType = MemberType::query()->updateOrCreate(
                ['code' => MemberTypeCode::OM->value],
                [
                    'name' => MemberTypeCode::OM->label(),
                    'is_new_member' => false,
                    'is_senior' => false,
                    'requires_membership_fee' => false,
                    'mortuary_eligible' => true,
                ],
            );

            $farmer = Farmer::query()->updateOrCreate(
                ['farmer_code' => 'FRM-2025-00005'],
                [
                    'first_name' => 'Ramon',
                    'middle_name' => 'Castillo',
                    'last_name' => 'Mercado',
                    'suffix' => null,
                    'sex' => 'Male',
                    'birth_date' => '1972-07-09',
                    'civil_status' => 'Married',
                    'mobile_number' => '09175556677',
                    'email' => 'ramonmercado@gmail.com',
                    'address' => 'Purok 2, Agdao, San Carlos City, Pangasinan',
                    'barangay_id' => $barangay->id,
                    'association_id' => $association->id,
                    'member_type_id' => $memberType->id,
                    'is_registry_record' => true,
                    'record_origin' => 'legacy',
                    'source_application_id' => null,
                    'status' => FarmerStatus::ACTIVE->value,
                    'membership_status' => MembershipStatus::ACTIVE->value,
                    'registered_at' => Carbon::create(2025, 1, 15, 8, 30, 0),
                    'activated_at' => Carbon::create(2025, 1, 15, 9, 15, 0),
                    'last_renewal_year' => 2025,
                    'inactive_at' => null,
                    'inactive_reason' => null,
                ],
            );

            MembershipLedger::query()
                ->where('farmer_id', $farmer->id)
                ->where('year', 2026)
                ->delete();

            $farmer->renewalRequests()
                ->where('year', 2026)
                ->delete();

            MembershipLedger::query()->updateOrCreate(
                [
                    'farmer_id' => $farmer->id,
                    'year' => 2025,
                ],
                [
                    'membership_application_id' => null,
                    'renewal_request_id' => null,
                    'member_type_snapshot' => MemberTypeCode::OM->value,
                    'membership_fee' => 0,
                    'annual_due' => 100,
                    'mortuary_fee' => 150,
                    'total_amount_due' => 250,
                    'amount_paid' => 250,
                    'paid_at' => Carbon::create(2025, 2, 12, 11, 0, 0),
                    'payment_status' => PaymentStatus::PAID->value,
                    'mortuary_eligible' => true,
                    'status' => 'approved',
                ],
            );

            Role::findOrCreate(User::ROLE_FARMER, 'web');

            $user = User::query()->updateOrCreate(
                ['email' => 'ramonmercado@gmail.com'],
                [
                    'name' => $farmer->full_name,
                    'password' => 'farmer12345',
                    'status' => 'active',
                    'must_change_password' => false,
                    'farmer_id' => $farmer->id,
                    'created_by' => null,
                    'last_login_at' => null,
                    'last_login_ip' => null,
                ],
            );

            if (! $user->hasRole(User::ROLE_FARMER)) {
                $user->syncRoles([User::ROLE_FARMER]);
            }
        });
    }
}
