<?php

namespace Database\Seeders;

use App\Models\Association;
use App\Models\Barangay;
use Illuminate\Database\Seeder;

class AssociationSeeder extends Seeder
{
    public function run(): void
    {
        $barangays = Barangay::query()
            ->orderBy('id')
            ->get();

        foreach ($barangays as $barangay) {
            $association = Association::query()->firstOrNew([
                'barangay_id' => $barangay->id,
            ]);

            if (! $association->exists) {
                $association->code = $this->nextAssociationCode();
            }

            $association->name = $barangay->name . ' ASSOCIATION';
            $association->status = 'Active';
            $association->save();
        }
    }

    private function nextAssociationCode(): string
    {
        $max = (int) Association::query()
            ->selectRaw("MAX(CAST(SUBSTRING(code, 6) AS UNSIGNED)) as max_code")
            ->where('code', 'like', 'ASSOC%')
            ->value('max_code');

        return 'ASSOC' . str_pad((string) ($max + 1), 2, '0', STR_PAD_LEFT);
    }
}
