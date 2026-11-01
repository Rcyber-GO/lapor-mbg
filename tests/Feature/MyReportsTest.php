<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyReportsTest extends TestCase
{
    use RefreshDatabase;

    private function pelapor(): User
    {
        $user = User::factory()->create();
        $user->role = User::ROLE_PELAPOR;
        $user->save();

        return $user;
    }

    public function test_pelapor_only_sees_own_reports(): void
    {
        $pelapor = $this->pelapor();
        $own = Report::factory()->for($pelapor)->create();
        $other = Report::factory()->create();

        $this->actingAs($pelapor)
            ->get('/laporan')
            ->assertOk()
            ->assertSee('href="'.route('reports.show', $own).'"', false)
            ->assertDontSee('href="'.route('reports.show', $other).'"', false);
    }

    public function test_status_filter_is_kept_on_pagination_links(): void
    {
        $pelapor = $this->pelapor();
        Report::factory()->for($pelapor)->count(12)->create();

        $this->actingAs($pelapor)
            ->get('/laporan?status='.Report::STATUS_DIKIRIM)
            ->assertOk()
            ->assertSee('status='.Report::STATUS_DIKIRIM.'&amp;page=2', false);
    }
}
