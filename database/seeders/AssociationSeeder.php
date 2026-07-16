<?php

namespace Database\Seeders;

use App\Models\Association;
use App\Models\Barangay;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssociationSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
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

            $this->mergeDuplicateAssociations();
        });
    }

    private function nextAssociationCode(): string
    {
        $max = (int) Association::query()
            ->selectRaw("MAX(CAST(SUBSTRING(code, 6) AS UNSIGNED)) as max_code")
            ->where('code', 'like', 'ASSOC%')
            ->value('max_code');

        return 'ASSOC' . str_pad((string) ($max + 1), 2, '0', STR_PAD_LEFT);
    }

    private function mergeDuplicateAssociations(): void
    {
        $duplicateBarangayIds = Association::query()
            ->select('barangay_id')
            ->groupBy('barangay_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('barangay_id');

        foreach ($duplicateBarangayIds as $barangayId) {
            $associations = Association::query()
                ->where('barangay_id', $barangayId)
                ->orderBy('id')
                ->get();

            $primary = $associations->shift();

            if (! $primary) {
                continue;
            }

            foreach ($associations as $duplicate) {
                DB::table('farmers')
                    ->where('association_id', $duplicate->id)
                    ->update(['association_id' => $primary->id]);

                $duplicate->delete();
            }
        }
    }
}
