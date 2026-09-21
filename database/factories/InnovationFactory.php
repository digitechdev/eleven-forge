<?php

namespace Database\Factories;

use App\InnovationStatus;
use App\InnovationType;
use App\InnovationVisibility;
use App\Models\Innovation;
use App\Models\InnovationCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Innovation>
 */
class InnovationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'innovation_code' => fake()->unique()->bothify('INN-#####'),
            'category_id' => InnovationCategory::factory(),
            'name' => fake()->words(3, true),
            'slug' => fake()->unique()->slug(),
            'type' => InnovationType::Software,
            'status' => InnovationStatus::Concept,
            'visibility' => InnovationVisibility::Internal,
            'is_featured' => false,
            'created_by' => User::factory(),
        ];
    }
}
