<?php

namespace Database\Seeders;

use App\Models\Distribution;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReportSeeder extends Seeder
{
    /**
     * Contoh laporan fiktif. Rina dan Agus melapor distribusi terbaru SMP Harapan
     * (dua laporan pada distribusi yang sama), Agus juga melapor sekolah lain.
     */
    public function run(): void
    {
        $rina = User::where('email', 'rina@example.test')->firstOrFail();
        $agus = User::where('email', 'agus@example.test')->firstOrFail();

        $harapan = $this->latestDistributionOf('SMP Harapan');
        $tunasBangsa = $this->latestDistributionOf('SD Tunas Bangsa');

        $reports = [
            [$rina, $harapan, 'orang_tua', 6, 'Mual, sakit perut',
                'Anak saya makan siang dari program MBG pukul 11.30. Sekitar pukul 14.00 ia mengeluh mual dan sakit perut. Ia mendengar beberapa teman sekelas mengalami keluhan serupa.'],
            [$agus, $harapan, 'guru', 10, 'Mual, muntah, pusing',
                'Setelah jam makan siang, beberapa siswa kelas 8 meminta izin ke UKS karena mual dan pusing. Dua siswa sempat muntah.'],
            [$agus, $tunasBangsa, 'orang_tua', 2, 'Diare',
                'Anak saya mengalami diare pada malam hari setelah mengonsumsi makanan MBG di sekolah. Adiknya yang bersekolah di tempat yang sama juga mengeluh hal serupa.'],
        ];

        foreach ($reports as [$user, $distribution, $relation, $affected, $symptoms, $description]) {
            $user->reports()->updateOrCreate(
                ['distribution_id' => $distribution->id],
                [
                    'school_id' => $distribution->school_id,
                    'reporter_relation' => $relation,
                    'incident_date' => $distribution->distribution_date,
                    'affected_count' => $affected,
                    'symptoms' => $symptoms,
                    'description' => $description,
                ],
            );
        }
    }

    private function latestDistributionOf(string $schoolName): Distribution
    {
        return School::where('name', $schoolName)->firstOrFail()
            ->distributions()
            ->latest('distribution_date')
            ->firstOrFail();
    }
}
