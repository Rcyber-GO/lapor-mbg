<?php

namespace Database\Seeders;

use App\Models\Distribution;
use App\Models\School;
use Illuminate\Database\Seeder;

class DistributionSeeder extends Seeder
{
    /**
     * Tiga distribusi per sekolah pada tanggal berbeda dalam dua minggu terakhir,
     * relatif terhadap hari seeding agar data selalu terlihat baru.
     */
    public function run(): void
    {
        $menus = [
            'Nasi, ayam goreng, sayur bayam, pisang',
            'Nasi, telur balado, tumis kangkung, jeruk',
            'Nasi, ikan tongkol, sayur sop, semangka',
            'Nasi, tempe bacem, sayur asem, pepaya',
        ];

        $providers = ['SPPG Sejahtera Mandiri', 'SPPG Berkah Bersama'];

        foreach (School::orderBy('id')->get() as $index => $school) {
            foreach ([1, 5, 10] as $offset => $daysAgo) {
                Distribution::updateOrCreate(
                    [
                        'school_id' => $school->id,
                        'distribution_date' => now()->subDays($daysAgo + ($index % 2))->toDateString(),
                    ],
                    [
                        'menu' => $menus[($index + $offset) % count($menus)],
                        'provider_name' => $providers[$index % count($providers)],
                        'portion_count' => 150 + ($index * 40),
                    ],
                );
            }
        }
    }
}
