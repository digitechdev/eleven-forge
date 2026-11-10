<?php

namespace Database\Factories;

use App\Enums\InnovationMediaType;
use App\Models\InventoryItem;
use App\Models\InventoryItemMedia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryItemMedia>
 */
class InventoryItemMediaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inventory_item_id' => InventoryItem::factory(),
            'media_type' => InnovationMediaType::Image,
            'file_path' => 'inventory-items/sample.jpg',
            'original_name' => 'sample.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'sort_order' => 0,
        ];
    }
}
