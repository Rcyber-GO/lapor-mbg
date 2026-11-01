<?php

namespace Tests\Feature;

use App\Models\Distribution;
use App\Models\Report;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecapTest extends TestCase
{
    use RefreshDatabase;

    private School $harapan;

    private School $tunas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->harapan = School::factory()->create(['name' => 'SMP Harapan Rekap']);
        $this->tunas = School::factory()->create(['name' => 'SD Tunas Rekap']);

        $this->report($this->harapan, '2026-10-10', Report::STATUS_DIKIRIM, 5);
        $this->report($this->harapan, '2026-10-11', Report::STATUS_PERLU_DITERUSKAN, 3);
        $this->report($this->harapan, '2026-10-12', Report::STATUS_DITERUSKAN, 10);
        $this->report($this->tunas, '2026-09-01', Report::STATUS_SELESAI, 2);
    }

    private function report(School $school, string $date, string $status, int $affected): void
    {
        $distribution = Distribution::factory()->create(['school_id' => $school->id, 'distribution_date' => $date]);
        $report = Report::factory()->create(['distribution_id' => $distribution->id, 'incident_date' => $date, 'affected_count' => $affected]);
        $report->status = $status;
        $report->save();
    }

    private function staff(string $role = User::ROLE_KOORDINATOR): User
    {
        $user = User::factory()->create();
        $user->role = $role;
        $user->save();

        return $user;
    }

    private function stat(string $key, int $value): string
    {
        return 'data-stat="'.$key.'">'.$value.'</span>';
    }

    public function test_summary_and_table_reflect_actual_data(): void
    {
        $this->actingAs($this->staff())->get('/rekap')
            ->assertOk()
            ->assertSee($this->stat('total', 4), false)
            ->assertSee($this->stat('in_progress', 2), false)
            ->assertSee($this->stat('to_forward', 1), false)
            ->assertSee($this->stat('forwarded', 1), false)
            ->assertSee($this->stat('affected', 20), false)
            ->assertSeeInOrder(['SMP Harapan Rekap', '<td>3</td>', '<td>2</td>', '<td>1</td>', '<td>1</td>', '<td>0</td>', '18 orang'], false)
            ->assertSeeInOrder(['SD Tunas Rekap', '<td>1</td>', '<td>0</td>', '<td>0</td>', '<td>0</td>', '<td>1</td>', '2 orang'], false);
    }

    public function test_filters_limit_recap(): void
    {
        $this->actingAs($this->staff(User::ROLE_PEMANTAU))
            ->get('/rekap?date_from=2026-10-01&date_to=2026-10-31')
            ->assertSee($this->stat('total', 3), false)
            ->assertDontSee('SD Tunas Rekap</td>', false);

        $this->actingAs($this->staff())
            ->get('/rekap?school_id='.$this->tunas->id)
            ->assertSee($this->stat('total', 1), false)
            ->assertSee($this->stat('affected', 2), false)
            ->assertDontSee('SMP Harapan Rekap</td>', false);
    }

    public function test_empty_filter_result(): void
    {
        $this->actingAs($this->staff())->get('/rekap?date_from=2020-01-01&date_to=2020-01-31')
            ->assertSee($this->stat('total', 0), false)
            ->assertSee('Tidak ada laporan pada filter ini.');
    }

    public function test_pelapor_and_guest_cannot_open_recap(): void
    {
        $this->actingAs(User::factory()->create())->get('/rekap')->assertForbidden();
        $this->post('/logout');
        $this->get('/rekap')->assertRedirect('/login');
    }
}
