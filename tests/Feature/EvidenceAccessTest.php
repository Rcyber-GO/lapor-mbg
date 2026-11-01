<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EvidenceAccessTest extends TestCase
{
    use RefreshDatabase;

    private Report $report;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Report::EVIDENCE_DISK);
        $path = UploadedFile::fake()->image('bukti.png')->store(Report::EVIDENCE_DIRECTORY, Report::EVIDENCE_DISK);

        $this->report = Report::factory()->create([
            'evidence_path' => $path,
            'evidence_name' => 'foto menu.png',
        ]);
    }

    public function test_owner_can_view_evidence_inline(): void
    {
        $response = $this->actingAs($this->report->user)->get('/laporan/'.$this->report->id.'/bukti');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
    }

    public function test_detail_page_shows_evidence_link_and_preview(): void
    {
        $this->actingAs($this->report->user)
            ->get('/laporan/'.$this->report->id)
            ->assertOk()
            ->assertSee('foto menu.png')
            ->assertSee('src="'.route('reports.evidence', $this->report).'"', false);
    }

    public function test_other_pelapor_cannot_view_evidence(): void
    {
        $other = User::factory()->create();

        $this->actingAs($other)->get('/laporan/'.$this->report->id.'/bukti')->assertNotFound();
    }

    public function test_guest_cannot_view_evidence(): void
    {
        $this->get('/laporan/'.$this->report->id.'/bukti')->assertRedirect('/login');
    }

    public function test_missing_file_returns_not_found(): void
    {
        Storage::disk(Report::EVIDENCE_DISK)->delete($this->report->evidence_path);

        $this->actingAs($this->report->user)->get('/laporan/'.$this->report->id.'/bukti')->assertNotFound();
    }
}
