<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\ProductionRun;
use App\Models\ProductionRunInput;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductionRunInput>
 */
class ProductionRunInputFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'production_run_id' => ProductionRun::factory(),
            'inventory_item_id' => InventoryItem::factory(),
            'unit_id' => Unit::factory(),
            'location_id' => fn (array $attributes): int => ProductionRun::query()->whereKey($attributes['production_run_id'])->value('location_id'),
            'formula_quantity' => 1,
            'consumed_quantity' => 1,
            'unit_cost' => 10,
            'line_cost' => 10,
        ];
    }
}
