<?php

namespace Database\Seeders;

use App\Models\FeeSchedule;
use App\Models\MemberType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FeeScheduleSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $year = now()->year;
        $memberTypes = MemberType::query()->get();

        foreach ($memberTypes as $memberType) {
            FeeSchedule::query()->updateOrCreate(
                [
                    'member_type_id' => $memberType->id,
                    'year' => $year,
                ],
                [
                    'member_type_id' => $memberType->id,
                    'year' => $year,
                    'membership_fee' => $memberType->requires_membership_fee ? 100.00 : 0.00,
                    'annual_due' => 100.00,
                    'mortuary_fee' => $memberType->mortuary_eligible ? 150.00 : 0.00,
                    'renewal_deadline' => now()->endOfYear()->toDateString(),
                    'is_active' => true,
                    'effective_from' => now()->startOfYear()->toDateString(),
                    'effective_to' => null,
                ],
            );
        }

        $existingSchedules = FeeSchedule::query()->orderByDesc('year')->get();

        if ($existingSchedules->isEmpty()) {
            return;
        }

        if (! $existingSchedules->contains(fn (FeeSchedule $schedule): bool => $schedule->is_active)) {
            $existingSchedules->first()?->forceFill([
                'is_active' => true,
            ])->save();
        }
    }
}
