<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\InventoryItemPriceChange;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryItemPriceChange>
 */
class InventoryItemPriceChangeFactory extends Factory
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
            'previous_unit_cost' => 100,
            'new_unit_cost' => 120,
            'effective_at' => now(),
        ];
    }
}
