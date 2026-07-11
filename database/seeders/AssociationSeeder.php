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

        foreach ($barangays as $index => $barangay) {
            Association::query()->updateOrCreate(
                ['barangay_id' => $barangay->id],
                [
                    'name' => $barangay->name . ' ASSOCIATION',
                    'code' => 'ASSOC' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                    'status' => 'active',
                ],
            );
        }
    }
}
