<?php

namespace Tests\Feature;

use App\Models\Distribution;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RelatedReportsTest extends TestCase
{
    use RefreshDatabase;

    private Report $report;

    private Report $sameDistribution;

    private Report $sameSchool;

    protected function setUp(): void
    {
        parent::setUp();

        $distribution = Distribution::factory()->create();
        $otherDistribution = Distribution::factory()->create(['school_id' => $distribution->school_id]);

        $this->report = Report::factory()->create(['distribution_id' => $distribution->id, 'affected_count' => 4]);
        $this->sameDistribution = Report::factory()
            ->for(User::factory()->state(['name' => 'Pelapor Lain Rahasia']))
            ->create(['distribution_id' => $distribution->id, 'affected_count' => 6]);
        $this->sameSchool = Report::factory()->create(['distribution_id' => $otherDistribution->id]);
        Report::factory()->create(); // sekolah lain, tidak terkait
    }

    private function pemantau(): User
    {
        $user = User::factory()->create();
        $user->role = User::ROLE_PEMANTAU;
        $user->save();

        return $user;
    }

    public function test_staff_sees_related_reports_without_reporter_identity(): void
    {
        $response = $this->actingAs($this->pemantau())->get('/laporan/'.$this->report->id);

        $response->assertOk()
            ->assertSee('Keterkaitan Laporan')
            ->assertSee('<strong>1</strong> laporan lain pada distribusi yang sama', false)
            ->assertSee('total perkiraan 10 siswa terdampak', false)
            ->assertSee('<strong>2</strong> laporan lain dari', false)
            ->assertSee('<td>#'.$this->sameDistribution->id.'</td>', false)
            ->assertDontSee('<td>#'.$this->sameSchool->id.'</td>', false)
            ->assertDontSee('Pelapor Lain Rahasia');
    }

    public function test_reporter_does_not_see_related_reports(): void
    {
        $this->actingAs($this->report->user)->get('/laporan/'.$this->report->id)
            ->assertOk()
            ->assertDontSee('Keterkaitan Laporan')
            ->assertDontSee('<td>#'.$this->sameDistribution->id.'</td>', false);
    }
}
