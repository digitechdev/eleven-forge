<?php

namespace App\Services;

use App\Enums\BuildOrderStatus;
use App\Enums\StockTransactionType;
use App\Exceptions\StockPostingException;
use App\Models\BuildOrder;
use App\Models\InnovationItem;
use App\Models\InventoryItem;
use App\Models\ProductionRun;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Completes production runs: builds units of an innovation against a build order by consuming
 * the non-optional parts of its formula from the order's location. Built units are not stocked;
 * the run records what was consumed and what it cost.
 */
class ProductionRunService
{
    public function __construct(private StockLedgerService $ledger) {}

    /**
     * Complete a run in one transaction: snapshot inputs, post one build-consumption entry per input,
     * and advance the order. Any shortfall rolls everything back.
     *
     * @throws ValidationException when the order cannot accept this output
     * @throws StockPostingException when an input has too little stock at the location
     */
    public function complete(BuildOrder $order, float $outputQuantity, User $actor, ?string $notes = null): ProductionRun
    {
        $outputQuantity = round($outputQuantity, 3);

        if ($outputQuantity <= 0) {
            throw ValidationException::withMessages(['output_quantity' => __('Output quantity must be greater than zero.')]);
        }

        return DB::transaction(function () use ($order, $outputQuantity, $actor, $notes): ProductionRun {
            $order = BuildOrder::query()->lockForUpdate()->findOrFail($order->id);
            $order->load(['formula', 'location', 'innovation']);

            if (! $order->status->acceptsProduction()) {
                throw ValidationException::withMessages(['output_quantity' => __('Only planned or in-progress builds can record production.')]);
            }

            $remaining = $order->remainingQuantity();

            if ($outputQuantity > $remaining) {
                throw ValidationException::withMessages(['output_quantity' => __('Output cannot exceed the remaining :remaining units of this build.', ['remaining' => $this->trimmed($remaining)])]);
            }

            $formula = $order->formula;
            $yield = (float) $formula->yield_quantity;
            $lines = $yield > 0
                ? $this->consumedLines($formula->items()->where('is_optional', false)->get(), $outputQuantity / $yield)
                : collect();

            if ($lines->isEmpty()) {
                throw ValidationException::withMessages(['output_quantity' => __('This build\'s formula has no required parts to consume.')]);
            }

            $totalCost = round($lines->sum('line_cost'), 2);
            $unitCost = round($totalCost / $outputQuantity, 2);
            $innovation = $order->innovation;
            $markup = $innovation->markup_percentage;
            $suggestedPrice = InnovationFormulaService::suggestedSellingPrice($unitCost, $markup);

            $run = ProductionRun::create([
                'run_number' => ProductionRun::nextNumber(),
                'build_order_id' => $order->id,
                'innovation_id' => $order->innovation_id,
                'innovation_formula_id' => $formula->id,
                'location_id' => $order->location_id,
                'output_quantity' => $outputQuantity,
                'total_input_cost' => $totalCost,
                'unit_production_cost' => $unitCost,
                'markup_percentage' => $markup,
                'suggested_selling_price' => $suggestedPrice,
                'completed_by' => $actor->id,
                'completed_at' => now(),
                'notes' => $notes,
            ]);

            foreach ($lines as $line) {
                $run->inputs()->create([
                    'inventory_item_id' => $line['item']->id,
                    'unit_id' => $line['unit_id'],
                    'location_id' => $order->location_id,
                    'formula_quantity' => $line['formula_quantity'],
                    'consumed_quantity' => $line['consumed_quantity'],
                    'unit_cost' => $line['unit_cost'],
                    'line_cost' => $line['line_cost'],
                ]);

                $this->ledger->post(
                    $line['item'],
                    $order->location,
                    StockTransactionType::BuildConsumption,
                    0,
                    $line['consumed_quantity'],
                    $line['unit_cost'],
                    $actor,
                    [
                        'production_run_id' => $run->id,
                        'build_order_id' => $order->id,
                        'reference_number' => $run->run_number,
                        'notes' => $notes,
                    ],
                );
            }

            $produced = round((float) $order->produced_quantity + $outputQuantity, 3);
            $isComplete = $produced >= (float) $order->planned_quantity;

            $order->update([
                'produced_quantity' => $produced,
                'status' => $isComplete ? BuildOrderStatus::Completed : BuildOrderStatus::InProgress,
                'started_at' => $order->started_at ?? now(),
                'completed_at' => $isComplete ? now() : null,
            ]);

            $innovation->forceFill([
                'estimated_unit_cost' => $unitCost,
                'suggested_selling_price' => $suggestedPrice,
            ])->save();

            return $run;
        });
    }

    /**
     * Quantities and costs to consume for each line, ordered by item so balance locks are taken in a stable order.
     *
     * @param  EloquentCollection<int, InnovationItem>  $lines
     * @return Collection<int, array{item: InventoryItem, unit_id: int, formula_quantity: float, consumed_quantity: float, unit_cost: float, line_cost: float}>
     */
    private function consumedLines(EloquentCollection $lines, float $multiplier): Collection
    {
        return $lines
            ->load(['inventoryItem' => fn ($query) => $query->withTrashed()])
            ->sortBy('inventory_item_id')
            ->values()
            ->map(function (InnovationItem $line) use ($multiplier): array {
                $consumed = round((float) $line->quantity_required * $multiplier, 3);
                $unitCost = round((float) ($line->inventoryItem->unit_cost ?? 0), 2);

                return [
                    'item' => $line->inventoryItem ?? throw (new ModelNotFoundException)->setModel(InventoryItem::class, [$line->inventory_item_id]),
                    'unit_id' => $line->unit_id,
                    'formula_quantity' => (float) $line->quantity_required,
                    'consumed_quantity' => $consumed,
                    'unit_cost' => $unitCost,
                    'line_cost' => round($consumed * $unitCost, 2),
                ];
            })
            ->filter(fn (array $line): bool => $line['consumed_quantity'] > 0)
            ->values();
    }

    /**
     * A quantity without trailing zeros, e.g. 2.5 rather than 2.500.
     */
    private function trimmed(float $quantity): string
    {
        return rtrim(rtrim(number_format($quantity, 3, '.', ''), '0'), '.');
    }
}
