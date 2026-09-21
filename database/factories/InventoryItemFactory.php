<?php

namespace Database\Factories;

use App\InventoryItemStatus;
use App\InventoryItemType;
use App\Models\InventoryItem;
use App\Models\ItemCategory;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'item_code' => fake()->unique()->bothify('ITM-#####'),
            'category_id' => ItemCategory::factory(),
            'default_unit_id' => Unit::factory(),
            'name' => fake()->words(3, true),
            'item_type' => InventoryItemType::Component,
            'quantity_on_hand' => 0,
            'minimum_stock' => 0,
            'status' => InventoryItemStatus::Active,
        ];
    }
}
