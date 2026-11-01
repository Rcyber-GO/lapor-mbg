<?php

namespace Database\Factories;

use App\Models\Distribution;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'distribution_id' => Distribution::factory(),
            // Sekolah selalu mengikuti distribusi agar data tetap konsisten.
            'school_id' => fn (array $attributes) => Distribution::find($attributes['distribution_id'])->school_id,
            'reporter_relation' => 'orang_tua',
            'incident_date' => fn (array $attributes) => Distribution::find($attributes['distribution_id'])->distribution_date,
            'affected_count' => fake()->numberBetween(1, 20),
            'symptoms' => 'Mual, sakit perut',
            'description' => 'Beberapa jam setelah makan siang, anak mengeluh mual dan sakit perut.',
        ];
    }
}
