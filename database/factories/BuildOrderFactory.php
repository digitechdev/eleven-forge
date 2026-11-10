<?php

namespace Database\Factories;

use App\Enums\BuildOrderStatus;
use App\Models\BuildOrder;
use App\Models\InnovationFormula;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BuildOrder>
 */
class BuildOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_number' => fake()->unique()->bothify('BO-TEST-#####'),
            'innovation_formula_id' => InnovationFormula::factory()->active(),
            'innovation_id' => fn (array $attributes): int => InnovationFormula::query()->whereKey($attributes['innovation_formula_id'])->value('innovation_id'),
            'location_id' => Location::factory(),
            'planned_quantity' => 5,
            'produced_quantity' => 0,
            'status' => BuildOrderStatus::Planned,
            'requested_by' => User::factory(),
            'notes' => null,
        ];
    }

    /**
     * A build order for the given formula, consuming parts at the given location.
     */
    public function forFormula(InnovationFormula $formula, ?Location $location = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'innovation_formula_id' => $formula->id,
            'innovation_id' => $formula->innovation_id,
            'location_id' => $location->id ?? $attributes['location_id'],
        ]);
    }

    /**
     * An order in the given status.
     */
    public function status(BuildOrderStatus $status): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => $status,
            'started_at' => in_array($status, [BuildOrderStatus::InProgress, BuildOrderStatus::Completed], true) ? now() : null,
            'completed_at' => $status === BuildOrderStatus::Completed ? now() : null,
        ]);
    }
}
