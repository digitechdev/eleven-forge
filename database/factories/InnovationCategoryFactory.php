<?php

namespace Database\Factories;

use App\Models\InnovationCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InnovationCategory>
 */
class InnovationCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->sentence(),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
