<?php

namespace Tests\Feature;

use App\Models\Distribution;
use App\Models\Report;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncomingReportFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $pemantau;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pemantau = User::factory()->create();
        $this->pemantau->role = User::ROLE_PEMANTAU;
        $this->pemantau->save();
    }

    private function reportAt(School $school, string $date, array $attributes = [], string $status = Report::STATUS_DIKIRIM): Report
    {
        $distribution = Distribution::factory()->create(['school_id' => $school->id, 'distribution_date' => $date]);
        $report = Report::factory()->create(['distribution_id' => $distribution->id, 'incident_date' => $date, ...$attributes]);
        $report->status = $status;
        $report->save();

        return $report;
    }

    private function row(Report $report): string
    {
        return '<td>#'.$report->id.'</td>';
    }

    public function test_keyword_searches_symptoms_description_school_and_number(): void
    {
        $harapan = School::factory()->create(['name' => 'SMP Harapan Uji']);
        $other = School::factory()->create(['name' => 'SD Lain']);
        $bySymptom = $this->reportAt($other, '2026-10-20', ['symptoms' => 'Ruam kulit']);
        $bySchool = $this->reportAt($harapan, '2026-10-21');
        $unrelated = $this->reportAt($other, '2026-10-22');

        $this->actingAs($this->pemantau)->get('/laporan-masuk?q=ruam')
            ->assertSee($this->row($bySymptom), false)
            ->assertDontSee($this->row($bySchool), false);

        $this->actingAs($this->pemantau)->get('/laporan-masuk?q=Harapan+Uji')
            ->assertSee($this->row($bySchool), false)
            ->assertDontSee($this->row($unrelated), false);

        $this->actingAs($this->pemantau)->get('/laporan-masuk?q=%23'.$unrelated->id)
            ->assertSee($this->row($unrelated), false);
    }

    public function test_status_school_and_date_filters_combine(): void
    {
        $school = School::factory()->create();
        $match = $this->reportAt($school, '2026-10-15', [], Report::STATUS_DITINJAU);
        $wrongStatus = $this->reportAt($school, '2026-10-16');
        $wrongDate = $this->reportAt($school, '2026-09-01', [], Report::STATUS_DITINJAU);
        $wrongSchool = $this->reportAt(School::factory()->create(), '2026-10-15', [], Report::STATUS_DITINJAU);

        $this->actingAs($this->pemantau)
            ->get('/laporan-masuk?'.http_build_query([
                'status' => Report::STATUS_DITINJAU,
                'school_id' => $school->id,
                'date_from' => '2026-10-01',
                'date_to' => '2026-10-31',
            ]))
            ->assertOk()
            ->assertSee($this->row($match), false)
            ->assertDontSee($this->row($wrongStatus), false)
            ->assertDontSee($this->row($wrongDate), false)
            ->assertDontSee($this->row($wrongSchool), false);
    }

    public function test_pagination_keeps_active_filters(): void
    {
        $school = School::factory()->create();
        foreach (range(1, 12) as $day) {
            $this->reportAt($school, sprintf('2026-10-%02d', $day), [], Report::STATUS_DITINJAU);
        }
        $this->reportAt($school, '2026-10-20'); // status lain, tidak ikut

        $response = $this->actingAs($this->pemantau)->get('/laporan-masuk?status=ditinjau&school_id='.$school->id);
        $response->assertSee('12 laporan')
            ->assertSee('status=ditinjau&amp;school_id='.$school->id.'&amp;page=2', false);

        $this->actingAs($this->pemantau)->get('/laporan-masuk?status=ditinjau&school_id='.$school->id.'&page=2')
            ->assertOk()
            ->assertSee('12 laporan')
            ->assertSee('value="ditinjau" selected', false);
    }

    public function test_invalid_filters_are_rejected(): void
    {
        $this->actingAs($this->pemantau)
            ->from('/laporan-masuk')
            ->get('/laporan-masuk?status=terbukti&date_from=2026-10-10&date_to=2026-10-01')
            ->assertRedirect('/laporan-masuk')
            ->assertSessionHasErrors(['status', 'date_to']);
    }

    public function test_empty_result_message_when_filter_matches_nothing(): void
    {
        $this->reportAt(School::factory()->create(), '2026-10-10');

        $this->actingAs($this->pemantau)->get('/laporan-masuk?q=tidak-ada-yang-cocok')
            ->assertSee('Tidak ada laporan yang sesuai dengan filter.');
    }
}
