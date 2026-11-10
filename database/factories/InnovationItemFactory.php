<?php

namespace Database\Factories;

use App\Models\Innovation;
use App\Models\InnovationFormula;
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
            'innovation_formula_id' => fn (array $attributes): int => InnovationFormula::query()
                ->where('innovation_id', $attributes['innovation_id'])
                ->orderByDesc('version')
                ->value('id') ?? InnovationFormula::factory()->create(['innovation_id' => $attributes['innovation_id']])->id,
            'inventory_item_id' => InventoryItem::factory(),
            'unit_id' => Unit::factory(),
            'quantity_required' => 1,
            'is_optional' => false,
            'sort_order' => 0,
        ];
    }

    /**
     * A line of the given formula.
     */
    public function forFormula(InnovationFormula $formula): static
    {
        return $this->state(fn (array $attributes): array => [
            'innovation_id' => $formula->innovation_id,
            'innovation_formula_id' => $formula->id,
        ]);
    }
}
