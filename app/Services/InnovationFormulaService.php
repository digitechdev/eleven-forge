<?php

namespace App\Services;

use App\Enums\FormulaStatus;
use App\Models\Innovation;
use App\Models\InnovationFormula;
use App\Models\InnovationItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Versioning and costing of innovation formulas (versioned bills of materials).
 * Version numbering and activation lock the innovation row so concurrent requests stay consistent.
 */
class InnovationFormulaService
{
    /**
     * The formula new lines go into when the innovation has none yet: a version 1 draft.
     */
    public function firstFormula(Innovation $innovation): InnovationFormula
    {
        return DB::transaction(function () use ($innovation): InnovationFormula {
            $this->lockInnovation($innovation);

            return $innovation->formulas()->orderBy('version')->first()
                ?? $innovation->formulas()->create(['version' => 1, 'yield_quantity' => 1, 'status' => FormulaStatus::Draft]);
        });
    }

    /**
     * Start a new draft version, copying the yield and lines of the source formula when given.
     */
    public function createVersion(Innovation $innovation, ?InnovationFormula $source = null): InnovationFormula
    {
        return DB::transaction(function () use ($innovation, $source): InnovationFormula {
            $this->lockInnovation($innovation);

            $formula = $innovation->formulas()->create([
                'version' => (int) $innovation->formulas()->max('version') + 1,
                'yield_quantity' => $source->yield_quantity ?? 1,
                'status' => FormulaStatus::Draft,
                'notes' => $source ? __('Copied from version :version.', ['version' => $source->version]) : null,
            ]);

            $source?->items()->orderBy('sort_order')->orderBy('id')->get()->each(
                fn (InnovationItem $line) => $formula->items()->create([
                    ...$line->only(['inventory_item_id', 'unit_id', 'quantity_required', 'usage_description', 'specification_notes', 'is_optional', 'sort_order']),
                    'innovation_id' => $innovation->id,
                ]),
            );

            return $formula;
        });
    }

    /**
     * Make a draft the innovation's only active formula, archiving the previous one,
     * and store the estimated unit cost on the formula and the innovation.
     *
     * @throws ValidationException when the formula is not a draft or has no lines
     */
    public function activate(InnovationFormula $formula): InnovationFormula
    {
        return DB::transaction(function () use ($formula): InnovationFormula {
            $innovation = $this->lockInnovation($formula->innovation);
            $formula = InnovationFormula::query()->lockForUpdate()->findOrFail($formula->id);

            if ($formula->status !== FormulaStatus::Draft) {
                throw ValidationException::withMessages(['formula' => __('Only draft formulas can be activated.')]);
            }

            if ($formula->items()->doesntExist()) {
                throw ValidationException::withMessages(['formula' => __('Add at least one line before activating this formula.')]);
            }

            $innovation->formulas()
                ->where('status', FormulaStatus::Active)
                ->whereKeyNot($formula->id)
                ->update(['status' => FormulaStatus::Archived, 'updated_at' => now()]);

            $formula->update(['status' => FormulaStatus::Active, 'activated_at' => now()]);

            $this->refreshCosting($formula);

            return $formula;
        });
    }

    /**
     * Recalculate the estimated unit cost of an active formula and copy it, with the
     * suggested selling price, onto its innovation. Drafts and archived formulas are left alone.
     */
    public function refreshCosting(InnovationFormula $formula): void
    {
        if ($formula->status !== FormulaStatus::Active) {
            return;
        }

        $unitCost = $this->estimatedUnitCost($formula);
        $formula->update(['estimated_unit_cost' => $unitCost]);

        $innovation = $formula->innovation;
        $innovation->forceFill([
            'estimated_unit_cost' => $unitCost,
            'suggested_selling_price' => self::suggestedSellingPrice($unitCost, $innovation->markup_percentage),
        ])->save();
    }

    /**
     * Cost of building one unit: required quantity × current item cost over all non-optional lines, divided by the yield.
     * Unpriced items count as zero.
     */
    public function estimatedUnitCost(InnovationFormula $formula): float
    {
        $total = $formula->items()
            ->where('is_optional', false)
            ->with(['inventoryItem' => fn ($query) => $query->withTrashed()])
            ->get()
            ->sum(fn (InnovationItem $line): float => (float) $line->quantity_required * (float) ($line->inventoryItem->unit_cost ?? 0));

        $yield = (float) $formula->yield_quantity;

        return $yield > 0 ? round($total / $yield, 2) : 0.0;
    }

    /**
     * Unit cost plus markup, or null when there is no cost or no markup.
     */
    public static function suggestedSellingPrice(float|string|null $unitCost, float|string|null $markupPercentage): ?float
    {
        if ($unitCost === null || $markupPercentage === null) {
            return null;
        }

        return round((float) $unitCost * (1 + (float) $markupPercentage / 100), 2);
    }

    /**
     * Lock the innovation row for the rest of the transaction.
     */
    private function lockInnovation(Innovation $innovation): Innovation
    {
        return Innovation::query()->withTrashed()->lockForUpdate()->findOrFail($innovation->id);
    }
}
