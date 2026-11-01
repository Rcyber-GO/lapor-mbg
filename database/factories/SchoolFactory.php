<?php

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<School>
 */
class SchoolFactory extends Factory
{
    public function definition(): array
    {
        $level = fake()->randomElement(School::LEVELS);

        return [
            'name' => $level.' '.fake()->unique()->lastName(),
            'level' => $level,
            'address' => fake()->streetAddress(),
            'district' => fake()->city(),
        ];
    }
}
