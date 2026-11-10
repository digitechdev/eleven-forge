<?php

namespace Database\Factories;

use App\Enums\FormulaStatus;
use App\Models\Innovation;
use App\Models\InnovationFormula;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InnovationFormula>
 */
class InnovationFormulaFactory extends Factory
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
            'version' => fn (array $attributes): int => (int) InnovationFormula::query()->where('innovation_id', $attributes['innovation_id'])->max('version') + 1,
            'yield_quantity' => 1,
            'status' => FormulaStatus::Draft,
            'notes' => null,
            'activated_at' => null,
            'estimated_unit_cost' => null,
        ];
    }

    /**
     * The innovation's current formula.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => FormulaStatus::Active,
            'activated_at' => now(),
        ]);
    }

    /**
     * A superseded formula.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => FormulaStatus::Archived,
            'activated_at' => now()->subMonth(),
        ]);
    }
}
