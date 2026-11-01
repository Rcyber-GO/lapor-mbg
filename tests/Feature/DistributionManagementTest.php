<?php

namespace Tests\Feature;

use App\Models\Distribution;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DistributionManagementTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->role = $role;
        $user->save();

        return $user;
    }

    private function payloadFor(School $school, string $date = '2026-10-20'): array
    {
        return [
            'school_id' => $school->id,
            'distribution_date' => $date,
            'menu' => 'Nasi, telur balado, tumis kangkung, jeruk',
            'provider_name' => 'SPPG Contoh',
            'portion_count' => 200,
        ];
    }

    public function test_coordinator_can_create_distribution(): void
    {
        $school = School::factory()->create();

        $this->actingAs($this->userWithRole(User::ROLE_KOORDINATOR))
            ->post('/distribusi', $this->payloadFor($school))
            ->assertRedirect('/distribusi');

        $this->assertDatabaseHas('distributions', ['school_id' => $school->id, 'provider_name' => 'SPPG Contoh']);
    }

    public function test_second_distribution_for_same_school_and_date_is_rejected(): void
    {
        $school = School::factory()->create();
        Distribution::factory()->for($school)->create(['distribution_date' => '2026-10-20']);

        $this->actingAs($this->userWithRole(User::ROLE_KOORDINATOR))
            ->post('/distribusi', $this->payloadFor($school, '2026-10-20'))
            ->assertSessionHasErrors('distribution_date');

        $this->assertDatabaseCount('distributions', 1);
    }

    public function test_same_date_is_allowed_for_different_school(): void
    {
        Distribution::factory()->create(['distribution_date' => '2026-10-20']);
        $otherSchool = School::factory()->create();

        $this->actingAs($this->userWithRole(User::ROLE_KOORDINATOR))
            ->post('/distribusi', $this->payloadFor($otherSchool, '2026-10-20'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('distributions', 2);
    }

    public function test_updating_distribution_keeps_its_own_date(): void
    {
        $distribution = Distribution::factory()->create(['distribution_date' => '2026-10-20']);

        $this->actingAs($this->userWithRole(User::ROLE_KOORDINATOR))
            ->put('/distribusi/'.$distribution->id, [
                ...$this->payloadFor($distribution->school, '2026-10-20'),
                'menu' => 'Menu diperbarui',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Menu diperbarui', $distribution->fresh()->menu);
    }

    public function test_pelapor_cannot_manage_distributions(): void
    {
        $school = School::factory()->create();
        $pelapor = $this->userWithRole(User::ROLE_PELAPOR);

        $this->actingAs($pelapor)->get('/distribusi')->assertForbidden();
        $this->actingAs($pelapor)->post('/distribusi', $this->payloadFor($school))->assertForbidden();

        $this->assertDatabaseCount('distributions', 0);
    }

    public function test_filter_by_school_is_kept_on_pagination_links(): void
    {
        $school = School::factory()->create();
        foreach (range(1, 12) as $day) {
            Distribution::factory()->for($school)->create(['distribution_date' => "2026-10-{$day}"]);
        }

        $this->actingAs($this->userWithRole(User::ROLE_KOORDINATOR))
            ->get('/distribusi?school_id='.$school->id)
            ->assertOk()
            ->assertSee('school_id='.$school->id.'&amp;page=2', false);
    }
}
