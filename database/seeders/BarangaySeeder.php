<?php

namespace Database\Seeders;

use App\Models\Barangay;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BarangaySeeder extends Seeder
{
    public function run(): void
    {
        $barangays = [
            'Abanon',
            'Agdao',
            'Anando',
            'Ano',
            'Antipangol',
            'Aponit',
            'Bacnar',
            'Balaya',
            'Balayong',
            'Baldog',
            'Balite Sur',
            'Balococ',
            'Bani',
            'Bega',
            'Bocboc',
            'Bugallon-Posadas Street',
            'Bogaoan',
            'Bolingit',
            'Bolosan',
            'Bonifacio',
            'Buenglat',
            'Burgos Padlan',
            'Cacaritan',
            'Caingal',
            'Calobaoan',
            'Calomboyan',
            'Capataan',
            'Caoayan-Kiling',
            'Cobol',
            'Coliling',
            'Cruz',
            'Doyong',
            'Gamata',
            'Guelew',
            'Ilang',
            'Inerangan',
            'Isla',
            'Libas',
            'Lilimasan',
            'Longos',
            'Lucban',
            'Mabalbalino',
            'Mabini',
            'Magtaking',
            'Malacañang',
            'Maliwara',
            'Mamarlao',
            'Manzon',
            'Matagdem',
            'Mestizo Norte',
            'Naguilayan',
            'Nilentap',
            'Padilla-Gomez',
            'Pagal',
            'Palaming',
            'Palaris',
            'Palospos',
            'Pangalangan',
            'Pangoloan',
            'Pangpang',
            'Paitan-Panoypoy',
            'Parayao',
            'Payapa',
            'Payar',
            'Perez Boulevard',
            'Polo',
            'Quezon Boulevard',
            'Quintong',
            'Rizal',
            'Roxas Boulevard',
            'Salinap',
            'San Juan',
            'San Pedro-Taloy',
            'Sapinit',
            'PNR Station Site',
            'Supo',
            'Talang',
            'Tamayo',
            'Tandoc',
            'Tarece',
            'Tarectec',
            'Tayambani',
            'Tebag',
            'Turac',
            'M. Soriano',
            'Tandang Sora',
        ];

        $legacyNames = [
            'ANTIPANGOL' => 'Antipangol',
            'APONIT' => 'Aponit',
            'BACNAR' => 'Bacnar',
            'BALAYA' => 'Balaya',
            'BALAYONG' => 'Balayong',
            'BALDOG' => 'Baldog',
            'BALITE SUR' => 'Balite Sur',
            'BALOCOC' => 'Balococ',
            'BANI' => 'Bani',
            'BEGA' => 'Bega',
            'BOBCOC' => 'Bocboc',
            'BOGAOAN' => 'Bogaoan',
            'BOLINGIT' => 'Bolingit',
            'BOLOSAN' => 'Bolosan',
            'BUENGLAT' => 'Buenglat',
            'CACARITAN' => 'Cacaritan',
            'CAINGAL' => 'Caingal',
            'CALOBAOAN' => 'Calobaoan',
            'CALOMBOYAN' => 'Calomboyan',
            'CAOAYAN' => 'Caoayan-Kiling',
            'KILING' => 'Caoayan-Kiling',
            'CAPATAAN' => 'Capataan',
            'COBOL' => 'Cobol',
            'COLILING' => 'Coliling',
            'CRUZ' => 'Cruz',
            'DOYONG' => 'Doyong',
            'GAMATA' => 'Gamata',
            'GUELEW' => 'Guelew',
            'ILANG' => 'Ilang',
            'INERANGAN' => 'Inerangan',
            'ISLA' => 'Isla',
            'LIBAS' => 'Libas',
            'LILIMASAN' => 'Lilimasan',
            'MABALBALINO' => 'Mabalbalino',
            'MAGTAKING' => 'Magtaking',
            'MALACANANG' => 'Malacañang',
            'MALIWARA' => 'Maliwara',
            'MAMARLAO' => 'Mamarlao',
            'MANZON' => 'Manzon',
            'MATACDEM' => 'Matagdem',
            'NAGUILAYAN' => 'Naguilayan',
            'NELINTAP' => 'Nilentap',
            'PADILLA' => 'Padilla-Gomez',
            'ABANON' => 'Abanon',
            'AGDAO' => 'Agdao',
            'ANANDO' => 'Anando',
            'ANO' => 'Ano',
        ];

        DB::transaction(function () use ($barangays, $legacyNames): void {
            foreach ($barangays as $index => $barangay) {
                Barangay::query()->updateOrCreate(
                    ['name' => $barangay],
                    [
                        'code' => 'BRGY' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                        'status' => 'Active',
                    ],
                );
            }

            foreach ($legacyNames as $legacyName => $canonicalName) {
                $legacy = Barangay::query()->where('name', $legacyName)->first();

                if (! $legacy) {
                    continue;
                }

                $canonical = Barangay::query()->where('name', $canonicalName)->first();

                if (! $canonical) {
                    $legacy->update(['name' => $canonicalName, 'status' => 'Active']);
                    continue;
                }

                if ($legacy->is($canonical)) {
                    $legacy->update(['status' => 'Active']);
                    continue;
                }

                DB::table('farmers')
                    ->where('barangay_id', $legacy->id)
                    ->update(['barangay_id' => $canonical->id]);

                DB::table('associations')
                    ->where('barangay_id', $legacy->id)
                    ->update(['barangay_id' => $canonical->id]);

                DB::table('advisories')
                    ->where('barangay_id', $legacy->id)
                    ->update(['barangay_id' => $canonical->id]);

                $legacy->delete();
            }
        });
    }
}
