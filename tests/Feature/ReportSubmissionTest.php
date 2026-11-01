<?php

namespace Tests\Feature;

use App\Models\Distribution;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->role = $role;
        $user->save();

        return $user;
    }

    private function payloadFor(Distribution $distribution, array $overrides = []): array
    {
        return [
            'school_id' => $distribution->school_id,
            'distribution_id' => $distribution->id,
            'reporter_relation' => 'orang_tua',
            'incident_date' => $distribution->distribution_date->toDateString(),
            'affected_count' => 5,
            'symptoms' => 'Mual, sakit perut',
            'description' => 'Beberapa jam setelah makan siang anak saya mengeluh mual dan sakit perut.',
            ...$overrides,
        ];
    }

    public function test_create_form_loads_distributions_via_fetch_instead_of_html(): void
    {
        $distribution = Distribution::factory()->create(['menu' => 'Menu yang tidak boleh ada di HTML']);

        $this->actingAs($this->userWithRole(User::ROLE_PELAPOR))
            ->get('/laporan/buat')
            ->assertOk()
            ->assertSee($distribution->school->name)
            ->assertDontSee('Menu yang tidak boleh ada di HTML')
            ->assertSee('data-url-template="'.url('/sekolah/__SCHOOL__/distribusi').'"', false)
            ->assertSee('js/report-form.js');
    }

    public function test_pelapor_can_submit_valid_report(): void
    {
        $pelapor = $this->userWithRole(User::ROLE_PELAPOR);
        $distribution = Distribution::factory()->create(['distribution_date' => now()->subDays(2)]);

        $response = $this->actingAs($pelapor)->post('/laporan', $this->payloadFor($distribution));

        $report = Report::sole();
        $response->assertRedirect('/laporan/'.$report->id);
        $this->assertSame($pelapor->id, $report->user_id);
        $this->assertSame($distribution->school_id, $report->school_id);
        $this->assertSame(Report::STATUS_DIKIRIM, $report->status);
    }

    public function test_owner_and_status_cannot_be_injected_from_request(): void
    {
        $pelapor = $this->userWithRole(User::ROLE_PELAPOR);
        $otherUser = $this->userWithRole(User::ROLE_PELAPOR);
        $distribution = Distribution::factory()->create(['distribution_date' => now()->subDay()]);

        $this->actingAs($pelapor)->post('/laporan', $this->payloadFor($distribution, [
            'user_id' => $otherUser->id,
            'status' => 'selesai',
        ]));

        $report = Report::sole();
        $this->assertSame($pelapor->id, $report->user_id);
        $this->assertSame(Report::STATUS_DIKIRIM, $report->status);
    }

    public function test_invalid_report_is_rejected_and_not_saved(): void
    {
        $distribution = Distribution::factory()->create(['distribution_date' => now()->subDay()]);

        $this->actingAs($this->userWithRole(User::ROLE_PELAPOR))
            ->post('/laporan', $this->payloadFor($distribution, [
                'affected_count' => 0,
                'symptoms' => '',
                'description' => 'terlalu pendek',
            ]))
            ->assertSessionHasErrors(['affected_count', 'symptoms', 'description']);

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_distribution_from_another_school_is_rejected(): void
    {
        $distribution = Distribution::factory()->create(['distribution_date' => now()->subDay()]);
        $otherDistribution = Distribution::factory()->create(['distribution_date' => now()->subDay()]);

        $this->actingAs($this->userWithRole(User::ROLE_PELAPOR))
            ->post('/laporan', $this->payloadFor($distribution, ['distribution_id' => $otherDistribution->id]))
            ->assertSessionHasErrors('distribution_id');

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_incident_date_before_distribution_is_rejected(): void
    {
        $distribution = Distribution::factory()->create(['distribution_date' => now()->subDays(2)]);

        $this->actingAs($this->userWithRole(User::ROLE_PELAPOR))
            ->post('/laporan', $this->payloadFor($distribution, ['incident_date' => now()->subDays(3)->toDateString()]))
            ->assertSessionHasErrors('incident_date');

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_future_incident_date_is_rejected(): void
    {
        $distribution = Distribution::factory()->create(['distribution_date' => now()->subDay()]);

        $this->actingAs($this->userWithRole(User::ROLE_PELAPOR))
            ->post('/laporan', $this->payloadFor($distribution, ['incident_date' => now()->addDay()->toDateString()]))
            ->assertSessionHasErrors('incident_date');

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_only_pelapor_can_submit_report(): void
    {
        $distribution = Distribution::factory()->create(['distribution_date' => now()->subDay()]);

        foreach ([User::ROLE_PEMANTAU, User::ROLE_KOORDINATOR] as $role) {
            $user = $this->userWithRole($role);

            $this->actingAs($user)->get('/laporan/buat')->assertForbidden();
            $this->actingAs($user)->post('/laporan', $this->payloadFor($distribution))->assertForbidden();
        }

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_guest_cannot_submit_report(): void
    {
        $distribution = Distribution::factory()->create(['distribution_date' => now()->subDay()]);

        $this->post('/laporan', $this->payloadFor($distribution))->assertRedirect('/login');

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_pelapor_cannot_open_report_of_another_pelapor(): void
    {
        $report = Report::factory()->create();

        $this->actingAs($this->userWithRole(User::ROLE_PELAPOR))
            ->get('/laporan/'.$report->id)
            ->assertNotFound();

        $this->actingAs($report->user)
            ->get('/laporan/'.$report->id)
            ->assertOk()
            ->assertSee($report->symptoms);
    }
}
