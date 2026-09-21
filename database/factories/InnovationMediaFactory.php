<?php

namespace Database\Factories;

use App\InnovationMediaType;
use App\Models\Innovation;
use App\Models\InnovationMedia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InnovationMedia>
 */
class InnovationMediaFactory extends Factory
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
            'media_type' => InnovationMediaType::Image,
            'file_path' => 'innovations/example.jpg',
            'is_cover' => false,
            'is_public' => false,
            'sort_order' => 0,
        ];
    }
}
