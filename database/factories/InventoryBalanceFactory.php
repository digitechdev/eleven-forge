<?php

namespace Database\Factories;

use App\Models\InventoryBalance;
use App\Models\InventoryItem;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryBalance>
 */
class InventoryBalanceFactory extends Factory
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
            'location_id' => Location::factory(),
            'on_hand_quantity' => 0,
            'reserved_quantity' => 0,
        ];
    }
}
