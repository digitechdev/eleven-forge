<?php

namespace Database\Factories;

use App\Models\Innovation;
use App\Models\InnovationTechnology;
use App\Models\Technology;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InnovationTechnology>
 */
class InnovationTechnologyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'innovation_id' => Innovation::factory(),
            'technology_id' => Technology::factory(),
            'version' => null,
            'purpose' => fake()->sentence(),
        ];
    }
}
