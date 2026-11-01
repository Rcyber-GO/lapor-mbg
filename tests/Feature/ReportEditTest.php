<?php

namespace Tests\Feature;

use App\Models\Distribution;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportEditTest extends TestCase
{
    use RefreshDatabase;

    private Report $report;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Report::EVIDENCE_DISK);
        $path = UploadedFile::fake()->image('lama.png')->store(Report::EVIDENCE_DIRECTORY, Report::EVIDENCE_DISK);
        $this->report = Report::factory()->create(['evidence_path' => $path, 'evidence_name' => 'lama.png', 'affected_count' => 3]);
    }

    private function setStatus(string $status): void
    {
        $this->report->status = $status;
        $this->report->save();
    }

    private function payload(array $overrides = []): array
    {
        return [
            'school_id' => $this->report->school_id,
            'distribution_id' => $this->report->distribution_id,
            'reporter_relation' => 'guru',
            'incident_date' => $this->report->incident_date->toDateString(),
            'affected_count' => 12,
            'symptoms' => 'Mual, pusing',
            'description' => 'Kronologi yang sudah dilengkapi dengan jam konsumsi makanan.',
            ...$overrides,
        ];
    }

    public function test_owner_can_edit_report_while_submitted(): void
    {
        $this->actingAs($this->report->user)->get('/laporan/'.$this->report->id.'/ubah')
            ->assertOk()
            ->assertSee('value="Mual, sakit perut"', false);

        $this->actingAs($this->report->user)
            ->put('/laporan/'.$this->report->id, $this->payload())
            ->assertRedirect('/laporan/'.$this->report->id);

        $this->report->refresh();
        $this->assertSame(12, $this->report->affected_count);
        $this->assertSame('lama.png', $this->report->evidence_name);
        $this->assertSame(Report::STATUS_DIKIRIM, $this->report->status);
    }

    public function test_editing_incomplete_report_sends_it_back_for_review(): void
    {
        $this->setStatus(Report::STATUS_PERLU_DILENGKAPI);

        $this->actingAs($this->report->user)
            ->put('/laporan/'.$this->report->id, $this->payload())
            ->assertSessionHas('success', 'Laporan diperbarui dan dikirim ulang untuk ditinjau.');

        $this->assertSame(Report::STATUS_DIKIRIM, $this->report->fresh()->status);
    }

    public function test_report_under_review_cannot_be_edited(): void
    {
        $this->setStatus(Report::STATUS_DITINJAU);

        $this->actingAs($this->report->user)->get('/laporan/'.$this->report->id.'/ubah')
            ->assertRedirect('/laporan/'.$this->report->id)
            ->assertSessionHas('error');

        $this->actingAs($this->report->user)
            ->put('/laporan/'.$this->report->id, $this->payload())
            ->assertSessionHas('error', 'Laporan berstatus "Sedang Ditinjau" sedang atau sudah ditinjau sehingga tidak dapat diubah.');

        $this->report->refresh();
        $this->assertSame(3, $this->report->affected_count);
        $this->assertSame(Report::STATUS_DITINJAU, $this->report->status);
    }

    public function test_replacing_evidence_deletes_old_file(): void
    {
        $oldPath = $this->report->evidence_path;

        $this->actingAs($this->report->user)
            ->put('/laporan/'.$this->report->id, $this->payload(['evidence' => UploadedFile::fake()->create('baru.pdf', 100, 'application/pdf')]));

        $this->report->refresh();
        $this->assertSame('baru.pdf', $this->report->evidence_name);
        Storage::disk(Report::EVIDENCE_DISK)->assertMissing($oldPath);
        Storage::disk(Report::EVIDENCE_DISK)->assertExists($this->report->evidence_path);
    }

    public function test_rejected_edit_keeps_old_evidence_and_discards_new_file(): void
    {
        $this->setStatus(Report::STATUS_DITINJAU);
        $oldPath = $this->report->evidence_path;

        $this->actingAs($this->report->user)
            ->put('/laporan/'.$this->report->id, $this->payload(['evidence' => UploadedFile::fake()->image('baru.png')]));

        $this->assertSame($oldPath, $this->report->fresh()->evidence_path);
        $this->assertSame([$oldPath], Storage::disk(Report::EVIDENCE_DISK)->allFiles(Report::EVIDENCE_DIRECTORY));
    }

    public function test_edit_validates_distribution_belongs_to_school(): void
    {
        $otherDistribution = Distribution::factory()->create();

        $this->actingAs($this->report->user)
            ->put('/laporan/'.$this->report->id, $this->payload(['distribution_id' => $otherDistribution->id]))
            ->assertSessionHasErrors('distribution_id');

        $this->assertSame($this->report->distribution_id, $this->report->fresh()->distribution_id);
    }

    public function test_other_users_cannot_edit(): void
    {
        $otherPelapor = User::factory()->create();
        $pemantau = User::factory()->create();
        $pemantau->role = User::ROLE_PEMANTAU;
        $pemantau->save();

        $this->actingAs($otherPelapor)->get('/laporan/'.$this->report->id.'/ubah')->assertNotFound();
        $this->actingAs($otherPelapor)->put('/laporan/'.$this->report->id, $this->payload())->assertNotFound();
        $this->actingAs($pemantau)->put('/laporan/'.$this->report->id, $this->payload())->assertForbidden();

        $this->assertSame(3, $this->report->fresh()->affected_count);
    }

    public function test_edit_button_only_shown_while_editable(): void
    {
        $this->actingAs($this->report->user)->get('/laporan/'.$this->report->id)->assertSee('Ubah Laporan');

        $this->setStatus(Report::STATUS_DITINJAU);
        $this->actingAs($this->report->user)->get('/laporan/'.$this->report->id)->assertDontSee('Ubah Laporan');
    }
}
