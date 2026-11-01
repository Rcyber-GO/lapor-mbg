<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_protected_page(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_demo_account_can_login_and_logout(): void
    {
        $this->seed(UserSeeder::class);

        $this->post('/login', ['email' => 'dedi@example.test', 'password' => 'password'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticated();

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_login_with_wrong_password_is_rejected(): void
    {
        $this->seed(UserSeeder::class);

        $this->post('/login', ['email' => 'rina@example.test', 'password' => 'salah'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_registration_always_creates_pelapor_even_if_role_is_sent(): void
    {
        $this->post('/daftar', [
            'name' => 'Warga Baru',
            'email' => 'warga@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => User::ROLE_KOORDINATOR,
        ])->assertRedirect('/dashboard');

        $this->assertSame(User::ROLE_PELAPOR, User::where('email', 'warga@example.test')->value('role'));
    }
}
