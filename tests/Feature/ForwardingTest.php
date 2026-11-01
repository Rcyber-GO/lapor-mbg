<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForwardingTest extends TestCase
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

    private function payload(array $overrides = []): array
    {
        return [
            'recipient_type' => 'sppg',
            'recipient_name' => 'SPPG Sejahtera Mandiri',
            'forwarded_at' => now()->toDateString(),
            'note' => 'Disampaikan melalui pertemuan komite sekolah.',
            ...$overrides,
        ];
    }

    public function test_coordinator_records_forwarding_and_status_becomes_forwarded(): void
    {
        $koordinator = $this->userWithRole(User::ROLE_KOORDINATOR);
        $report = $this->reportWithStatus(Report::STATUS_PERLU_DITERUSKAN);

        $this->actingAs($koordinator)
            ->post('/laporan/'.$report->id.'/penerusan', $this->payload())
            ->assertRedirect('/laporan/'.$report->id);

        $this->assertSame(Report::STATUS_DITERUSKAN, $report->fresh()->status);
        $this->assertDatabaseHas('forwardings', [
            'report_id' => $report->id,
            'user_id' => $koordinator->id,
            'recipient_type' => 'sppg',
            'recipient_name' => 'SPPG Sejahtera Mandiri',
        ]);
    }

    public function test_additional_forwarding_allowed_after_forwarded(): void
    {
        $koordinator = $this->userWithRole(User::ROLE_KOORDINATOR);
        $report = $this->reportWithStatus(Report::STATUS_DITERUSKAN);

        $this->actingAs($koordinator)
            ->post('/laporan/'.$report->id.'/penerusan', $this->payload(['recipient_type' => 'faskes', 'recipient_name' => 'Puskesmas Sukamaju']))
            ->assertSessionHas('success');

        $this->assertSame(1, $report->forwardings()->count());
        $this->assertSame(Report::STATUS_DITERUSKAN, $report->fresh()->status);
    }

    public function test_forwarding_rejected_before_monitor_marks_report(): void
    {
        $koordinator = $this->userWithRole(User::ROLE_KOORDINATOR);

        foreach ([Report::STATUS_DIKIRIM, Report::STATUS_DITINJAU, Report::STATUS_SELESAI] as $status) {
            $report = $this->reportWithStatus($status);

            $this->actingAs($koordinator)
                ->post('/laporan/'.$report->id.'/penerusan', $this->payload())
                ->assertSessionHas('error');

            $this->assertSame($status, $report->fresh()->status);
        }

        $this->assertDatabaseCount('forwardings', 0);
    }

    public function test_forwarding_date_cannot_be_in_future(): void
    {
        $koordinator = $this->userWithRole(User::ROLE_KOORDINATOR);
        $report = $this->reportWithStatus(Report::STATUS_PERLU_DITERUSKAN);

        $this->actingAs($koordinator)
            ->post('/laporan/'.$report->id.'/penerusan', $this->payload(['forwarded_at' => now()->addDay()->toDateString()]))
            ->assertSessionHasErrors(['forwarded_at' => 'Tanggal diteruskan tidak boleh melebihi hari ini.']);

        $this->assertDatabaseCount('forwardings', 0);
    }

    public function test_only_coordinator_may_record_forwarding(): void
    {
        $report = $this->reportWithStatus(Report::STATUS_PERLU_DITERUSKAN);

        foreach ([$report->user, $this->userWithRole(User::ROLE_PEMANTAU)] as $user) {
            $this->actingAs($user)
                ->post('/laporan/'.$report->id.'/penerusan', $this->payload())
                ->assertForbidden();
        }

        $this->assertSame(Report::STATUS_PERLU_DITERUSKAN, $report->fresh()->status);
        $this->assertDatabaseCount('forwardings', 0);
    }

    public function test_reporter_sees_forwarding_without_internal_note(): void
    {
        $koordinator = $this->userWithRole(User::ROLE_KOORDINATOR);
        $report = $this->reportWithStatus(Report::STATUS_PERLU_DITERUSKAN);
        $this->actingAs($koordinator)->post('/laporan/'.$report->id.'/penerusan', $this->payload());

        $this->actingAs($report->user)->get('/laporan/'.$report->id)
            ->assertOk()
            ->assertSee('SPPG Sejahtera Mandiri')
            ->assertDontSee('Disampaikan melalui pertemuan komite sekolah.')
            ->assertDontSee('Catat Penerusan');

        $this->actingAs($koordinator)->get('/laporan/'.$report->id)->assertSee('Disampaikan melalui pertemuan komite sekolah.');
    }
}
