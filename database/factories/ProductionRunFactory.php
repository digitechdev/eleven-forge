<?php

namespace Database\Factories;

use App\Models\BuildOrder;
use App\Models\ProductionRun;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductionRun>
 */
class ProductionRunFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'run_number' => fake()->unique()->bothify('PR-TEST-#####'),
            'build_order_id' => BuildOrder::factory(),
            'innovation_id' => fn (array $attributes): int => BuildOrder::query()->whereKey($attributes['build_order_id'])->value('innovation_id'),
            'innovation_formula_id' => fn (array $attributes): int => BuildOrder::query()->whereKey($attributes['build_order_id'])->value('innovation_formula_id'),
            'location_id' => fn (array $attributes): int => BuildOrder::query()->whereKey($attributes['build_order_id'])->value('location_id'),
            'output_quantity' => 1,
            'total_input_cost' => 100,
            'unit_production_cost' => 100,
            'markup_percentage' => null,
            'suggested_selling_price' => null,
            'completed_by' => User::factory(),
            'completed_at' => now(),
            'notes' => null,
        ];
    }
}
