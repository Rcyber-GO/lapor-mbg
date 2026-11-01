<?php

namespace Tests\Feature;

use App\Models\Distribution;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolDistributionEndpointTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->role = $role;
        $user->save();

        return $user;
    }

    public function test_returns_only_past_distributions_of_selected_school(): void
    {
        $school = School::factory()->create();
        $newer = Distribution::factory()->for($school)->create(['distribution_date' => now()->subDay(), 'menu' => 'Menu baru']);
        $older = Distribution::factory()->for($school)->create(['distribution_date' => now()->subDays(5)]);
        Distribution::factory()->for($school)->create(['distribution_date' => now()->addDays(2)]);
        Distribution::factory()->create(['distribution_date' => now()->subDay()]);

        $this->actingAs($this->userWithRole(User::ROLE_PELAPOR))
            ->getJson('/sekolah/'.$school->id.'/distribusi')
            ->assertOk()
            ->assertExactJson([
                [
                    'id' => $newer->id,
                    'date' => $newer->distribution_date->toDateString(),
                    'label' => $newer->distribution_date->translatedFormat('d M Y').' · Menu baru',
                ],
                [
                    'id' => $older->id,
                    'date' => $older->distribution_date->toDateString(),
                    'label' => $older->distribution_date->translatedFormat('d M Y').' · '.$older->menu,
                ],
            ]);
    }

    public function test_guest_gets_unauthorized(): void
    {
        $school = School::factory()->create();

        $this->getJson('/sekolah/'.$school->id.'/distribusi')->assertUnauthorized();
    }

    public function test_non_pelapor_gets_forbidden(): void
    {
        $school = School::factory()->create();

        foreach ([User::ROLE_PEMANTAU, User::ROLE_KOORDINATOR] as $role) {
            $this->actingAs($this->userWithRole($role))
                ->getJson('/sekolah/'.$school->id.'/distribusi')
                ->assertForbidden();
        }
    }

    public function test_unknown_school_gets_not_found(): void
    {
        $this->actingAs($this->userWithRole(User::ROLE_PELAPOR))
            ->getJson('/sekolah/999999/distribusi')
            ->assertNotFound();
    }
}
