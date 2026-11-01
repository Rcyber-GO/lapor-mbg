<?php

namespace Tests\Feature;

use App\Models\Distribution;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * BR-05: satu pelapor hanya boleh punya satu laporan aktif per distribusi.
 */
class DuplicateReportRuleTest extends TestCase
{
    use RefreshDatabase;

    private User $pelapor;

    private Distribution $distribution;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Report::EVIDENCE_DISK);
        $this->pelapor = User::factory()->create();
        $this->distribution = Distribution::factory()->create();
    }

    private function payload(Distribution $distribution): array
    {
        return [
            'school_id' => $distribution->school_id,
            'distribution_id' => $distribution->id,
            'reporter_relation' => 'orang_tua',
            'incident_date' => $distribution->distribution_date->toDateString(),
            'affected_count' => 2,
            'symptoms' => 'Mual',
            'description' => 'Anak mengeluh mual beberapa jam setelah makan siang.',
            'evidence' => UploadedFile::fake()->image('bukti.png'),
        ];
    }

    private function existingReport(string $status): Report
    {
        $report = Report::factory()->for($this->pelapor)->create(['distribution_id' => $this->distribution->id]);
        $report->status = $status;
        $report->save();

        return $report;
    }

    public function test_second_active_report_for_same_distribution_is_rejected(): void
    {
        $existing = $this->existingReport(Report::STATUS_DITINJAU);

        $this->actingAs($this->pelapor)
            ->post('/laporan', $this->payload($this->distribution))
            ->assertSessionHasErrors(['distribution_id' => "Anda masih memiliki laporan aktif (#{$existing->id}) untuk distribusi ini. Silakan perbarui laporan tersebut."]);

        $this->assertSame(1, Report::count());
        // File yang sempat diunggah ikut dibuang.
        $this->assertSame([], Storage::disk(Report::EVIDENCE_DISK)->allFiles(Report::EVIDENCE_DIRECTORY));
    }

    public function test_new_report_allowed_after_previous_one_is_final(): void
    {
        $this->existingReport(Report::STATUS_SELESAI);

        $this->actingAs($this->pelapor)
            ->post('/laporan', $this->payload($this->distribution))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Report::count());
    }

    public function test_other_pelapor_may_report_same_distribution(): void
    {
        $this->existingReport(Report::STATUS_DIKIRIM);

        $this->actingAs(User::factory()->create())
            ->post('/laporan', $this->payload($this->distribution))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Report::count());
    }

    public function test_editing_report_onto_distribution_with_active_report_is_rejected(): void
    {
        $this->existingReport(Report::STATUS_DIKIRIM);
        $otherDistribution = Distribution::factory()->create(['school_id' => $this->distribution->school_id]);
        $second = Report::factory()->for($this->pelapor)->create(['distribution_id' => $otherDistribution->id]);

        $payload = $this->payload($this->distribution);
        unset($payload['evidence']);

        $this->actingAs($this->pelapor)
            ->put('/laporan/'.$second->id, $payload)
            ->assertSessionHasErrors('distribution_id');

        $this->assertSame($otherDistribution->id, $second->fresh()->distribution_id);
    }
}
