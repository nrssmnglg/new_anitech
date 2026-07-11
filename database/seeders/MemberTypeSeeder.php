<?php

namespace Database\Seeders;

use App\Enums\MemberTypeCode;
use App\Models\MemberType;
use Illuminate\Database\Seeder;

class MemberTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (MemberTypeCode::cases() as $code) {
            MemberType::query()->updateOrCreate(
                ['code' => $code->value],
                [
                    'name' => $code->label(),
                    'requires_membership_fee' => $code->isNewMember(),
                    'mortuary_eligible' => ! $code->isSenior(),
                    'status' => 'Active',
                ],
            );
        }
    }
}
