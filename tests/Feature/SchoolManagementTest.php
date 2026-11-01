<?php

namespace Tests\Feature;

use App\Models\Distribution;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolManagementTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->role = $role;
        $user->save();

        return $user;
    }

    private function validPayload(): array
    {
        return [
            'name' => 'SD Contoh',
            'level' => 'SD',
            'address' => 'Jl. Contoh No. 1',
            'district' => 'Coblong',
        ];
    }

    public function test_coordinator_can_create_school(): void
    {
        $this->actingAs($this->userWithRole(User::ROLE_KOORDINATOR))
            ->post('/sekolah', $this->validPayload())
            ->assertRedirect('/sekolah');

        $this->assertDatabaseHas('schools', ['name' => 'SD Contoh']);
    }

    public function test_coordinator_can_search_school_list(): void
    {
        School::factory()->create(['name' => 'SMP Harapan']);
        School::factory()->create(['name' => 'SD Pelita Ilmu']);

        $this->actingAs($this->userWithRole(User::ROLE_KOORDINATOR))
            ->get('/sekolah?q=Harapan')
            ->assertOk()
            ->assertSee('SMP Harapan')
            ->assertDontSee('SD Pelita Ilmu');
    }

    public function test_pelapor_and_pemantau_cannot_access_school_management(): void
    {
        foreach ([User::ROLE_PELAPOR, User::ROLE_PEMANTAU] as $role) {
            $user = $this->userWithRole($role);

            $this->actingAs($user)->get('/sekolah')->assertForbidden();
            $this->actingAs($user)->post('/sekolah', $this->validPayload())->assertForbidden();
        }

        $this->assertDatabaseCount('schools', 0);
    }

    public function test_school_with_distributions_cannot_be_deleted(): void
    {
        $distribution = Distribution::factory()->create();

        $this->actingAs($this->userWithRole(User::ROLE_KOORDINATOR))
            ->delete('/sekolah/'.$distribution->school_id)
            ->assertSessionHas('error');

        $this->assertDatabaseHas('schools', ['id' => $distribution->school_id]);
    }

    public function test_school_without_distributions_can_be_deleted(): void
    {
        $school = School::factory()->create();

        $this->actingAs($this->userWithRole(User::ROLE_KOORDINATOR))
            ->delete('/sekolah/'.$school->id)
            ->assertRedirect('/sekolah');

        $this->assertDatabaseMissing('schools', ['id' => $school->id]);
    }

    public function test_duplicate_school_name_is_rejected(): void
    {
        School::factory()->create(['name' => 'SD Contoh']);

        $this->actingAs($this->userWithRole(User::ROLE_KOORDINATOR))
            ->post('/sekolah', $this->validPayload())
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('schools', 1);
    }
}
