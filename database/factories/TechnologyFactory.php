<?php

namespace Database\Factories;

use App\Models\Technology;
use App\TechnologyStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Technology>
 */
class TechnologyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'category' => 'Framework',
            'description' => fake()->sentence(),
            'status' => TechnologyStatus::Active,
        ];
    }
}
