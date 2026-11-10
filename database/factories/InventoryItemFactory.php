<?php

namespace Database\Factories;

use App\Enums\InventoryItemStatus;
use App\Enums\InventoryItemType;
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
            'reorder_level' => 0,
            'status' => InventoryItemStatus::Active,
        ];
    }

    /**
     * Indicate that the item is at or below its minimum stock.
     */
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes): array => [
            'quantity_on_hand' => 2,
            'minimum_stock' => 5,
        ]);
    }
}
