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
     * Contoh laporan fiktif dalam berbagai status agar setiap alur dan business
     * rule dapat langsung dicoba:
     * - Rina: Perlu Dilengkapi (BR-01: boleh diperbaiki), Sedang Ditinjau
     *   (BR-01: tidak boleh diubah), dan Selesai Dipantau pada distribusi lama
     *   SMP Harapan (BR-05: laporan baru untuk distribusi itu boleh, untuk
     *   distribusi terbaru ditolak karena masih ada laporan aktif).
     * - Agus: Ditandai untuk Diteruskan (BR-04: koordinator dapat mencatat
     *   penerusan), Dikirim (siap ditinjau), dan Sudah Diteruskan.
     * Laporan Rina dan Agus pada distribusi terbaru SMP Harapan saling terkait.
     */
    public function run(): void
    {
        $rina = User::where('email', 'rina@example.test')->firstOrFail();
        $agus = User::where('email', 'agus@example.test')->firstOrFail();
        $dedi = User::where('email', 'dedi@example.test')->firstOrFail();
        $sari = User::where('email', 'sari@example.test')->firstOrFail();

        $reports = [
            [
                'user' => $rina, 'distribution' => $this->distributionOf('SMP Harapan'),
                'relation' => 'orang_tua', 'affected' => 6, 'symptoms' => 'Mual, sakit perut',
                'description' => 'Anak saya makan siang dari program MBG pukul 11.30. Sekitar pukul 14.00 ia mengeluh mual dan sakit perut. Ia mendengar beberapa teman sekelas mengalami keluhan serupa.',
                'evidence' => ['png', 'foto-menu-makan-siang.png'],
                'status' => Report::STATUS_PERLU_DILENGKAPI,
                'notes' => [
                    [Report::STATUS_DITINJAU, 'Laporan mulai ditinjau.'],
                    [Report::STATUS_PERLU_DILENGKAPI, 'Mohon lengkapi jumlah teman sekelas yang mengalami keluhan serupa dan apakah anak sudah diperiksa di fasilitas kesehatan.'],
                ],
            ],
            [
                'user' => $agus, 'distribution' => $this->distributionOf('SMP Harapan'),
                'relation' => 'guru', 'affected' => 10, 'symptoms' => 'Mual, muntah, pusing',
                'description' => 'Setelah jam makan siang, beberapa siswa kelas 8 meminta izin ke UKS karena mual dan pusing. Dua siswa sempat muntah.',
                'evidence' => ['pdf', 'catatan-uks.pdf'],
                'status' => Report::STATUS_PERLU_DITERUSKAN,
                'notes' => [
                    [Report::STATUS_DITINJAU, 'Laporan mulai ditinjau.'],
                    [Report::STATUS_PERLU_DITERUSKAN, 'Terdapat laporan lain pada distribusi yang sama dan catatan UKS terlampir. Perlu diteruskan kepada sekolah dan SPPG.'],
                ],
            ],
            [
                'user' => $agus, 'distribution' => $this->distributionOf('SD Tunas Bangsa'),
                'relation' => 'orang_tua', 'affected' => 2, 'symptoms' => 'Diare',
                'description' => 'Anak saya mengalami diare pada malam hari setelah mengonsumsi makanan MBG di sekolah. Adiknya yang bersekolah di tempat yang sama juga mengeluh hal serupa.',
                'evidence' => ['png', 'foto-sisa-makanan.png'],
                'status' => Report::STATUS_DIKIRIM,
            ],
            [
                'user' => $rina, 'distribution' => $this->distributionOf('SD Pelita Ilmu'),
                'relation' => 'orang_tua', 'affected' => 3, 'symptoms' => 'Sakit perut',
                'description' => 'Keponakan saya yang bersekolah di SD Pelita Ilmu mengeluh sakit perut setelah makan siang, begitu juga dua temannya.',
                'evidence' => ['png', 'foto-kemasan.png'],
                'status' => Report::STATUS_DITINJAU,
                'notes' => [
                    [Report::STATUS_DITINJAU, 'Laporan mulai ditinjau, menunggu kabar dari wali kelas.'],
                ],
            ],
            [
                'user' => $agus, 'distribution' => $this->distributionOf('SMP Cendekia'),
                'relation' => 'guru', 'affected' => 15, 'symptoms' => 'Mual, demam ringan',
                'description' => 'Sekitar lima belas siswa dari tiga kelas mengeluh mual dan sebagian demam ringan sore hari setelah makan siang.',
                'evidence' => ['pdf', 'daftar-siswa-uks.pdf'],
                'status' => Report::STATUS_DITERUSKAN,
                'notes' => [
                    [Report::STATUS_DITINJAU, 'Laporan mulai ditinjau.'],
                    [Report::STATUS_PERLU_DITERUSKAN, 'Jumlah siswa terdampak cukup banyak, perlu diteruskan kepada sekolah.'],
                ],
                'forwarding' => ['sekolah', 'Kepala Sekolah SMP Cendekia', 'Disampaikan langsung kepada kepala sekolah untuk ditindaklanjuti bersama SPPG.'],
            ],
            [
                'user' => $rina, 'distribution' => $this->distributionOf('SMP Harapan', 1),
                'relation' => 'orang_tua', 'affected' => 1, 'symptoms' => 'Pusing',
                'description' => 'Anak saya sempat pusing setelah makan siang, tetapi membaik setelah beristirahat di UKS.',
                'evidence' => ['png', 'foto-menu-pekan-lalu.png'],
                'status' => Report::STATUS_SELESAI,
                'notes' => [
                    [Report::STATUS_DITINJAU, 'Laporan mulai ditinjau.'],
                    [Report::STATUS_SELESAI, 'Keluhan sudah membaik dan tidak ada laporan serupa pada distribusi ini. Tidak perlu tindak lanjut.'],
                ],
            ],
        ];

        foreach ($reports as $index => $data) {
            $distribution = $data['distribution'];
            [$type, $evidenceName] = $data['evidence'];

            $report = $data['user']->reports()->firstOrNew(['distribution_id' => $distribution->id]);
            $report->fill([
                'school_id' => $distribution->school_id,
                'reporter_relation' => $data['relation'],
                'incident_date' => $distribution->distribution_date,
                'affected_count' => $data['affected'],
                'symptoms' => $data['symptoms'],
                'description' => $data['description'],
            ]);
            $report->evidence_path = $this->storeDummyEvidence($index + 1, $type);
            $report->evidence_name = $evidenceName;
            // Seeder tepercaya menetapkan status langsung; di aplikasi status hanya berubah lewat ReportWorkflow.
            $report->status = $data['status'];
            $report->save();

            foreach ($data['notes'] ?? [] as [$status, $note]) {
                $report->notes()->firstOrCreate(['note' => $note], ['user_id' => $dedi->id, 'status' => $status]);
            }

            if (isset($data['forwarding'])) {
                [$recipientType, $recipientName, $note] = $data['forwarding'];
                $report->forwardings()->firstOrCreate(['recipient_name' => $recipientName], [
                    'user_id' => $sari->id,
                    'recipient_type' => $recipientType,
                    'forwarded_at' => now()->toDateString(),
                    'note' => $note,
                ]);
            }
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

    /**
     * Distribusi suatu sekolah, diurutkan dari yang terbaru ($offset 0 = terbaru).
     */
    private function distributionOf(string $schoolName, int $offset = 0): Distribution
    {
        return School::where('name', $schoolName)->firstOrFail()
            ->distributions()
            ->latest('distribution_date')
            ->skip($offset)
            ->firstOrFail();
    }
}
