<?php

namespace Database\Seeders;

use App\Models\Distribution;
use App\Models\Report;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

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
                'Anak saya makan siang dari program MBG pukul 11.30. Sekitar pukul 14.00 ia mengeluh mual dan sakit perut. Ia mendengar beberapa teman sekelas mengalami keluhan serupa.',
                'png', 'foto-menu-makan-siang.png'],
            [$agus, $harapan, 'guru', 10, 'Mual, muntah, pusing',
                'Setelah jam makan siang, beberapa siswa kelas 8 meminta izin ke UKS karena mual dan pusing. Dua siswa sempat muntah.',
                'pdf', 'catatan-uks.pdf'],
            [$agus, $tunasBangsa, 'orang_tua', 2, 'Diare',
                'Anak saya mengalami diare pada malam hari setelah mengonsumsi makanan MBG di sekolah. Adiknya yang bersekolah di tempat yang sama juga mengeluh hal serupa.',
                'png', 'foto-sisa-makanan.png'],
        ];

        foreach ($reports as $index => [$user, $distribution, $relation, $affected, $symptoms, $description, $type, $evidenceName]) {
            $report = $user->reports()->firstOrNew(['distribution_id' => $distribution->id]);
            $report->fill([
                'school_id' => $distribution->school_id,
                'reporter_relation' => $relation,
                'incident_date' => $distribution->distribution_date,
                'affected_count' => $affected,
                'symptoms' => $symptoms,
                'description' => $description,
            ]);

            $report->evidence_path = $this->storeDummyEvidence($index + 1, $type);
            $report->evidence_name = $evidenceName;
            $report->save();
        }
    }

    /**
     * File bukti dummy yang dibuat saat seeding (§9.11: tidak memakai foto atau
     * dokumen kejadian nyata).
     */
    private function storeDummyEvidence(int $number, string $type): string
    {
        $text = "Contoh bukti laporan #{$number} (data dummy)";
        $path = Report::EVIDENCE_DIRECTORY."/seed-{$number}.{$type}";

        Storage::disk(Report::EVIDENCE_DISK)->put($path, $type === 'pdf' ? $this->dummyPdf($text) : $this->dummyPng($text));

        return $path;
    }

    private function dummyPng(string $text): string
    {
        $image = imagecreatetruecolor(640, 360);
        imagefill($image, 0, 0, imagecolorallocate($image, 241, 245, 249));
        imagerectangle($image, 10, 10, 629, 349, imagecolorallocate($image, 148, 163, 184));
        imagestring($image, 5, 40, 160, $text, imagecolorallocate($image, 30, 41, 59));

        ob_start();
        imagepng($image);

        return ob_get_clean();
    }

    private function dummyPdf(string $text): string
    {
        $content = "BT /F1 16 Tf 72 760 Td ({$text}) Tj ET";
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            "<< /Length ".strlen($content)." >>\nstream\n{$content}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $i => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n{$object}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf."trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";
    }

    private function latestDistributionOf(string $schoolName): Distribution
    {
        return School::where('name', $schoolName)->firstOrFail()
            ->distributions()
            ->latest('distribution_date')
            ->firstOrFail();
    }
}
