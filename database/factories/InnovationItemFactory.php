<?php

namespace Database\Factories;

use App\Models\Innovation;
use App\Models\InnovationItem;
use App\Models\InventoryItem;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InnovationItem>
 */
class InnovationItemFactory extends Factory
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
            'inventory_item_id' => InventoryItem::factory(),
            'unit_id' => Unit::factory(),
            'quantity_required' => 1,
            'is_optional' => false,
            'sort_order' => 0,
        ];
    }
}
