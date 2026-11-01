<?php

namespace Tests\Feature;

use App\Models\Distribution;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EvidenceUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $pelapor;

    private Distribution $distribution;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Report::EVIDENCE_DISK);

        $this->pelapor = User::factory()->create();
        $this->pelapor->role = User::ROLE_PELAPOR;
        $this->pelapor->save();

        $this->distribution = Distribution::factory()->create(['distribution_date' => now()->subDay()]);
    }

    private function submitWith(?UploadedFile $evidence)
    {
        return $this->actingAs($this->pelapor)->post('/laporan', array_filter([
            'school_id' => $this->distribution->school_id,
            'distribution_id' => $this->distribution->id,
            'reporter_relation' => 'orang_tua',
            'incident_date' => $this->distribution->distribution_date->toDateString(),
            'affected_count' => 3,
            'symptoms' => 'Mual',
            'description' => 'Anak mengeluh mual beberapa jam setelah makan siang di sekolah.',
            'evidence' => $evidence,
        ]));
    }

    public function test_valid_image_is_stored_on_private_disk_with_random_name(): void
    {
        $this->submitWith(UploadedFile::fake()->image('foto makanan.jpg'))->assertSessionHasNoErrors();

        $report = Report::sole();
        $this->assertSame('foto makanan.jpg', $report->evidence_name);
        $this->assertStringStartsWith(Report::EVIDENCE_DIRECTORY.'/', $report->evidence_path);
        $this->assertStringNotContainsString('foto makanan', $report->evidence_path);
        Storage::disk(Report::EVIDENCE_DISK)->assertExists($report->evidence_path);
    }

    public function test_valid_pdf_is_accepted(): void
    {
        $this->submitWith(UploadedFile::fake()->create('surat-puskesmas.pdf', 300, 'application/pdf'))
            ->assertSessionHasNoErrors();

        Storage::disk(Report::EVIDENCE_DISK)->assertExists(Report::sole()->evidence_path);
    }

    public function test_file_larger_than_two_megabytes_is_rejected(): void
    {
        $this->submitWith(UploadedFile::fake()->image('besar.jpg')->size(3000))
            ->assertSessionHasErrors(['evidence' => 'Ukuran bukti pendukung maksimal 2 MB.']);

        $this->assertDatabaseCount('reports', 0);
        $this->assertEmpty(Storage::disk(Report::EVIDENCE_DISK)->allFiles());
    }

    public function test_disallowed_file_type_is_rejected(): void
    {
        $this->submitWith(UploadedFile::fake()->create('catatan.txt', 10, 'text/plain'))
            ->assertSessionHasErrors('evidence');

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_text_file_renamed_to_jpg_is_rejected_by_content_check(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'bukti');
        file_put_contents($path, 'ini hanya teks biasa, bukan gambar');
        $disguised = new UploadedFile($path, 'bukti.jpg', null, null, true);

        $this->submitWith($disguised)->assertSessionHasErrors('evidence');

        $this->assertDatabaseCount('reports', 0);
        $this->assertEmpty(Storage::disk(Report::EVIDENCE_DISK)->allFiles());
    }

    public function test_report_without_evidence_is_rejected(): void
    {
        $this->submitWith(null)->assertSessionHasErrors(['evidence' => 'Bukti pendukung wajib diisi.']);

        $this->assertDatabaseCount('reports', 0);
    }
}
