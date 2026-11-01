<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportReviewTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->role = $role;
        $user->save();

        return $user;
    }

    private function reportWithStatus(string $status): Report
    {
        $report = Report::factory()->create();
        $report->status = $status;
        $report->save();

        return $report;
    }

    public function test_pemantau_can_follow_valid_review_flow(): void
    {
        $pemantau = $this->userWithRole(User::ROLE_PEMANTAU);
        $report = Report::factory()->create();

        $this->actingAs($pemantau)
            ->post('/laporan/'.$report->id.'/status', ['to_status' => Report::STATUS_DITINJAU])
            ->assertRedirect('/laporan/'.$report->id);
        $this->assertSame(Report::STATUS_DITINJAU, $report->fresh()->status);

        $this->actingAs($pemantau)
            ->post('/laporan/'.$report->id.'/status', ['to_status' => Report::STATUS_PERLU_DITERUSKAN, 'note' => 'Beberapa laporan serupa pada distribusi ini.'])
            ->assertRedirect('/laporan/'.$report->id);

        $report->refresh();
        $this->assertSame(Report::STATUS_PERLU_DITERUSKAN, $report->status);
        $this->assertDatabaseHas('report_notes', [
            'report_id' => $report->id,
            'user_id' => $pemantau->id,
            'status' => Report::STATUS_PERLU_DITERUSKAN,
            'note' => 'Beberapa laporan serupa pada distribusi ini.',
        ]);
    }

    public function test_skipping_review_is_rejected_without_changes(): void
    {
        $pemantau = $this->userWithRole(User::ROLE_PEMANTAU);
        $report = Report::factory()->create();

        $this->actingAs($pemantau)
            ->post('/laporan/'.$report->id.'/status', ['to_status' => Report::STATUS_PERLU_DITERUSKAN, 'note' => 'Langsung diteruskan.'])
            ->assertSessionHas('error', 'Laporan berstatus "Dikirim" tidak dapat diubah menjadi "Ditandai untuk Diteruskan".');

        $this->assertSame(Report::STATUS_DIKIRIM, $report->fresh()->status);
        $this->assertDatabaseCount('report_notes', 0);
    }

    public function test_pemantau_cannot_set_coordinator_or_reporter_statuses(): void
    {
        $pemantau = $this->userWithRole(User::ROLE_PEMANTAU);
        $report = $this->reportWithStatus(Report::STATUS_PERLU_DITERUSKAN);

        $this->actingAs($pemantau)
            ->post('/laporan/'.$report->id.'/status', ['to_status' => Report::STATUS_DITERUSKAN, 'note' => 'x'])
            ->assertSessionHas('error');

        $this->assertSame(Report::STATUS_PERLU_DITERUSKAN, $report->fresh()->status);
    }

    public function test_final_report_cannot_change_status_or_get_notes(): void
    {
        $pemantau = $this->userWithRole(User::ROLE_PEMANTAU);
        $report = $this->reportWithStatus(Report::STATUS_SELESAI);

        $this->actingAs($pemantau)
            ->post('/laporan/'.$report->id.'/status', ['to_status' => Report::STATUS_DITINJAU])
            ->assertSessionHas('error');
        $this->actingAs($pemantau)
            ->post('/laporan/'.$report->id.'/catatan', ['note' => 'Catatan terlambat'])
            ->assertSessionHas('error', 'Laporan berstatus "Selesai Dipantau" sudah tidak dapat diubah.');

        $this->assertSame(Report::STATUS_SELESAI, $report->fresh()->status);
        $this->assertDatabaseCount('report_notes', 0);
    }

    public function test_note_is_required_for_decisive_transitions(): void
    {
        $pemantau = $this->userWithRole(User::ROLE_PEMANTAU);
        $report = $this->reportWithStatus(Report::STATUS_DITINJAU);

        foreach ([Report::STATUS_PERLU_DILENGKAPI, Report::STATUS_PERLU_DITERUSKAN, Report::STATUS_SELESAI] as $status) {
            $this->actingAs($pemantau)
                ->post('/laporan/'.$report->id.'/status', ['to_status' => $status, 'note' => ''])
                ->assertSessionHas('error');
        }

        $this->assertSame(Report::STATUS_DITINJAU, $report->fresh()->status);
    }

    public function test_unknown_status_value_is_rejected_by_validation(): void
    {
        $pemantau = $this->userWithRole(User::ROLE_PEMANTAU);
        $report = Report::factory()->create();

        $this->actingAs($pemantau)
            ->post('/laporan/'.$report->id.'/status', ['to_status' => 'terbukti_keracunan'])
            ->assertSessionHasErrors('to_status');

        $this->assertSame(Report::STATUS_DIKIRIM, $report->fresh()->status);
    }

    public function test_pemantau_can_add_note_without_changing_status(): void
    {
        $pemantau = $this->userWithRole(User::ROLE_PEMANTAU);
        $report = $this->reportWithStatus(Report::STATUS_DITINJAU);

        $this->actingAs($pemantau)
            ->post('/laporan/'.$report->id.'/catatan', ['note' => 'Menunggu konfirmasi dari pihak sekolah.'])
            ->assertRedirect('/laporan/'.$report->id);

        $this->assertDatabaseHas('report_notes', ['report_id' => $report->id, 'status' => null]);
        $this->assertSame(Report::STATUS_DITINJAU, $report->fresh()->status);
    }

    public function test_only_pemantau_may_call_review_endpoints(): void
    {
        $report = Report::factory()->create();

        foreach ([$report->user, $this->userWithRole(User::ROLE_KOORDINATOR)] as $user) {
            $this->actingAs($user)
                ->post('/laporan/'.$report->id.'/status', ['to_status' => Report::STATUS_DITINJAU])
                ->assertForbidden();
            $this->actingAs($user)
                ->post('/laporan/'.$report->id.'/catatan', ['note' => 'Catatan tidak sah'])
                ->assertForbidden();
        }

        $this->assertSame(Report::STATUS_DIKIRIM, $report->fresh()->status);
        $this->assertDatabaseCount('report_notes', 0);
    }

    public function test_reporter_sees_notes_without_monitor_name(): void
    {
        $pemantau = $this->userWithRole(User::ROLE_PEMANTAU);
        $pemantau->forceFill(['name' => 'Nama Pemantau Rahasia'])->save();
        $report = $this->reportWithStatus(Report::STATUS_DITINJAU);

        $this->actingAs($pemantau)->post('/laporan/'.$report->id.'/status', [
            'to_status' => Report::STATUS_PERLU_DILENGKAPI,
            'note' => 'Mohon lengkapi jam konsumsi makanan.',
        ]);

        $this->actingAs($report->user)->get('/laporan/'.$report->id)
            ->assertOk()
            ->assertSee('Mohon lengkapi jam konsumsi makanan.')
            ->assertSee('Pemantau Komunitas')
            ->assertDontSee('Nama Pemantau Rahasia')
            ->assertDontSee('Tindakan Peninjauan');

        $this->actingAs($pemantau)->get('/laporan/'.$report->id)->assertSee('Nama Pemantau Rahasia');
    }

    public function test_detail_page_offers_only_valid_actions(): void
    {
        $pemantau = $this->userWithRole(User::ROLE_PEMANTAU);
        $report = Report::factory()->create();

        $this->actingAs($pemantau)->get('/laporan/'.$report->id)
            ->assertSee('value="'.Report::STATUS_DITINJAU.'"', false)
            ->assertDontSee('value="'.Report::STATUS_PERLU_DITERUSKAN.'"', false);
    }
}
