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
    /**
     * Mundur satu hari tiap pemanggilan agar unique(school_id, distribution_date)
     * tidak bentrok secara acak saat satu sekolah diberi beberapa distribusi.
     */
    private static int $dayOffset = 0;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'distribution_date' => now()->subDays(self::$dayOffset++ % 365)->toDateString(),
            'menu' => 'Nasi, ayam goreng, sayur bayam, pisang',
            'provider_name' => 'SPPG '.fake()->lastName(),
            'portion_count' => fake()->numberBetween(100, 600),
        ];
    }
}
