<?php

namespace Database\Seeders;

use App\Models\School;
use Illuminate\Database\Seeder;

class SchoolSeeder extends Seeder
{
    /**
     * Data sekolah fiktif untuk demo. Tidak memakai data sekolah nyata.
     */
    public function run(): void
    {
        $schools = [
            ['SMP Harapan', 'SMP', 'Jl. Cempaka No. 12', 'Coblong'],
            ['SD Tunas Bangsa', 'SD', 'Jl. Anggrek No. 4', 'Coblong'],
            ['SD Pelita Ilmu', 'SD', 'Jl. Flamboyan No. 21', 'Sukajadi'],
            ['SMP Cendekia', 'SMP', 'Jl. Teratai No. 8', 'Sukajadi'],
            ['SMA Nusantara', 'SMA', 'Jl. Kenari No. 15', 'Lengkong'],
            ['SMK Karya Mandiri', 'SMK', 'Jl. Merpati No. 30', 'Lengkong'],
        ];

        foreach ($schools as [$name, $level, $address, $district]) {
            School::updateOrCreate(['name' => $name], [
                'level' => $level,
                'address' => $address,
                'district' => $district,
            ]);
        }
    }
}
