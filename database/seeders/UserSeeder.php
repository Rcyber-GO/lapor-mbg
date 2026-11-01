<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Akun demo setiap peran. Dua pelapor disiapkan agar pengujian ownership
     * (pelapor A mencoba membuka laporan pelapor B) dapat langsung dilakukan.
     */
    public function run(): void
    {
        $accounts = [
            ['Rina Wulandari', 'rina@example.test', User::ROLE_PELAPOR],
            ['Agus Setiawan', 'agus@example.test', User::ROLE_PELAPOR],
            ['Dedi Kurniawan', 'dedi@example.test', User::ROLE_PEMANTAU],
            ['Sari Handayani', 'sari@example.test', User::ROLE_KOORDINATOR],
        ];

        foreach ($accounts as [$name, $email, $role]) {
            $user = User::updateOrCreate(['email' => $email], [
                'name' => $name,
                'password' => 'password',
            ]);

            // Role di-assign langsung karena sengaja tidak fillable.
            $user->role = $role;
            $user->save();
        }
    }
}
