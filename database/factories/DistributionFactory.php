<?php

namespace Database\Factories;

use App\Models\Distribution;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Distribution>
 */
class DistributionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'distribution_date' => fake()->unique()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'menu' => 'Nasi, ayam goreng, sayur bayam, pisang',
            'provider_name' => 'SPPG '.fake()->lastName(),
            'portion_count' => fake()->numberBetween(100, 600),
        ];
    }
}
