<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReportAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Report $report;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Report::EVIDENCE_DISK);
        $path = UploadedFile::fake()->image('bukti.png')->store(Report::EVIDENCE_DIRECTORY, Report::EVIDENCE_DISK);

        $owner = User::factory()->create(['name' => 'Pemilik Laporan', 'email' => 'pemilik.rahasia@example.test']);
        $this->report = Report::factory()->for($owner)->create(['evidence_path' => $path]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->role = $role;
        $user->save();

        return $user;
    }

    public static function staffRoles(): array
    {
        return [
            'pemantau' => [User::ROLE_PEMANTAU],
            'koordinator' => [User::ROLE_KOORDINATOR],
        ];
    }

    #[DataProvider('staffRoles')]
    public function test_staff_can_open_incoming_list_detail_and_evidence(string $role): void
    {
        $staff = $this->userWithRole($role);

        $this->actingAs($staff)->get('/laporan-masuk')
            ->assertOk()
            ->assertSee('<td>#'.$this->report->id.'</td>', false)
            ->assertSee('Pemilik Laporan');
        $this->actingAs($staff)->get('/laporan/'.$this->report->id)
            ->assertOk()
            ->assertSee('Pemilik Laporan');
        $this->actingAs($staff)->get('/laporan/'.$this->report->id.'/bukti')->assertOk();
    }

    #[DataProvider('staffRoles')]
    public function test_reporter_email_is_never_shown_to_staff(string $role): void
    {
        $staff = $this->userWithRole($role);

        $this->actingAs($staff)->get('/laporan-masuk')->assertDontSee('pemilik.rahasia@example.test');
        $this->actingAs($staff)->get('/laporan/'.$this->report->id)->assertDontSee('pemilik.rahasia@example.test');
    }

    public function test_pelapor_cannot_open_incoming_list(): void
    {
        $this->actingAs($this->report->user)->get('/laporan-masuk')->assertForbidden();
    }

    public function test_guest_is_redirected_from_incoming_list(): void
    {
        $this->get('/laporan-masuk')->assertRedirect('/login');
    }

    public function test_other_pelapor_gets_not_found_for_detail_and_evidence(): void
    {
        $other = User::factory()->create();

        $this->actingAs($other)->get('/laporan/'.$this->report->id)->assertNotFound();
        $this->actingAs($other)->get('/laporan/'.$this->report->id.'/bukti')->assertNotFound();
    }

    public function test_owner_detail_does_not_show_staff_only_reporter_row(): void
    {
        $this->actingAs($this->report->user)->get('/laporan/'.$this->report->id)
            ->assertOk()
            ->assertDontSee('<dt>Pelapor</dt>', false)
            ->assertSee('Kembali ke Laporan Saya');
    }

    public function test_navigation_shows_incoming_menu_only_for_staff(): void
    {
        $this->actingAs($this->report->user)->get('/dashboard')->assertDontSee('Laporan Masuk');
        $this->actingAs($this->userWithRole(User::ROLE_PEMANTAU))->get('/dashboard')->assertSee('Laporan Masuk');
    }
}
