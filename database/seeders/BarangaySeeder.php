<?php

namespace Database\Seeders;

use App\Models\Barangay;
use Illuminate\Database\Seeder;

class BarangaySeeder extends Seeder
{
    public function run(): void
    {
        $barangays = [
            'ABANON',
            'AGDAO',
            'ANANDO',
            'ANO',
            'APONIT',
            'ANTIPANGOL',
            'BALAYA',
            'BALAYONG',
            'BALDOG',
            'BALOCOC',
            'BACNAR',
            'BANI',
            'BALITE SUR',
            'BEGA',
            'BOGAOAN',
            'BOBCOC',
            'BOLOSAN',
            'BOLINGIT',
            'BUENGLAT',
            'CACARITAN',
            'CALOMBOYAN',
            'CALOBAOAN',
            'CAOAYAN',
            'KILING',
            'CAINGAL',
            'CAPATAAN',
            'COBOL',
            'COLILING',
            'CRUZ',
            'DOYONG',
            'GAMATA',
            'GUELEW',
            'INERANGAN',
            'ILANG',
            'ISLA',
            'LILIMASAN',
            'LIBAS',
            'MABALBALINO',
            'MAGTAKING',
            'MALACANANG',
            'MALIWARA',
            'MATACDEM',
            'MAMARLAO',
            'MANZON',
            'NELINTAP',
            'NAGUILAYAN',
            'PADILLA',
        ];

        foreach ($barangays as $index => $barangay) {
            Barangay::query()->updateOrCreate(
                ['name' => $barangay],
                [
                    'code' => 'BRGY' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                    'status' => 'active',
                ],
            );
        }
    }
}
