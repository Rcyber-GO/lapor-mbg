<?php

namespace Tests\Feature;

use App\Models\Distribution;
use App\Models\Report;
use App\Models\ReportStatusLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportStatusObserverTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->role = $role;
        $user->save();

        return $user;
    }

    public function test_submitting_report_logs_initial_status_with_reporter(): void
    {
        Storage::fake(Report::EVIDENCE_DISK);
        $pelapor = User::factory()->create();
        $distribution = Distribution::factory()->create();

        $this->actingAs($pelapor)->post('/laporan', [
            'school_id' => $distribution->school_id,
            'distribution_id' => $distribution->id,
            'reporter_relation' => 'orang_tua',
            'incident_date' => $distribution->distribution_date->toDateString(),
            'affected_count' => 1,
            'symptoms' => 'Mual',
            'description' => 'Anak mengeluh mual setelah makan siang di sekolah.',
            'evidence' => UploadedFile::fake()->image('bukti.png'),
        ]);

        $this->assertDatabaseHas('report_status_logs', [
            'report_id' => Report::first()->id,
            'user_id' => $pelapor->id,
            'from_status' => null,
            'to_status' => Report::STATUS_DIKIRIM,
        ]);
    }

    public function test_status_change_logs_before_after_and_actor(): void
    {
        $pemantau = $this->userWithRole(User::ROLE_PEMANTAU);
        $report = Report::factory()->create();

        $this->actingAs($pemantau)->post('/laporan/'.$report->id.'/status', ['to_status' => Report::STATUS_DITINJAU]);

        $log = ReportStatusLog::where('report_id', $report->id)->latest('id')->first();
        $this->assertSame(Report::STATUS_DIKIRIM, $log->from_status);
        $this->assertSame(Report::STATUS_DITINJAU, $log->to_status);
        $this->assertSame($pemantau->id, $log->user_id);
        $this->assertNotNull($log->created_at);
    }

    public function test_edit_without_status_change_is_not_logged(): void
    {
        $report = Report::factory()->create();
        $before = ReportStatusLog::count();

        $report->affected_count = 99;
        $report->save();

        $this->assertSame($before, ReportStatusLog::count());
    }

    public function test_rejected_transition_leaves_no_log(): void
    {
        $pemantau = $this->userWithRole(User::ROLE_PEMANTAU);
        $report = Report::factory()->create();
        $before = ReportStatusLog::count();

        $this->actingAs($pemantau)->post('/laporan/'.$report->id.'/status', ['to_status' => Report::STATUS_SELESAI, 'note' => 'x']);

        $this->assertSame($before, ReportStatusLog::count());
    }

    public function test_log_stores_no_report_content_or_personal_data(): void
    {
        $this->assertSame(
            ['id', 'report_id', 'user_id', 'from_status', 'to_status', 'created_at'],
            Schema::getColumnListing('report_status_logs'),
        );
    }

    public function test_status_history_visible_to_staff_only(): void
    {
        $pemantau = $this->userWithRole(User::ROLE_PEMANTAU);
        $report = Report::factory()->create();
        $this->actingAs($pemantau)->post('/laporan/'.$report->id.'/status', ['to_status' => Report::STATUS_DITINJAU]);

        $this->actingAs($pemantau)->get('/laporan/'.$report->id)->assertSee('Riwayat Status');
        $this->actingAs($report->user)->get('/laporan/'.$report->id)->assertDontSee('Riwayat Status');
    }
}
