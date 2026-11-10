<?php

namespace Database\Factories;

use App\Enums\TechnologyCategory;
use App\Enums\TechnologyStatus;
use App\Models\Technology;
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
            'category' => TechnologyCategory::Framework,
            'description' => fake()->sentence(),
            'status' => TechnologyStatus::Active,
        ];
    }
}
