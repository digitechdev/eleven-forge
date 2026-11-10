<?php

namespace App\Http\Controllers\Production;

use App\Exceptions\StockPostingException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Production\CompleteProductionRunRequest;
use App\Models\BuildOrder;
use App\Models\ProductionRun;
use App\Models\ProductionRunInput;
use App\Models\StockLedgerEntry;
use App\Services\ProductionRunService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Production run detail, and completing a run against a build order. Completed runs are immutable.
 */
class ProductionRunController extends Controller
{
    /**
     * Show a run with every consumed input snapshot and its stock ledger entries.
     */
    public function show(ProductionRun $productionRun): Response
    {
        Gate::authorize('view', $productionRun);

        $productionRun->load([
            'innovation:id,innovation_code,name',
            'formula:id,version,yield_quantity',
            'location:id,code,name',
            'completer:id,name',
            'buildOrder:id,order_number,planned_quantity,produced_quantity,status',
            'inputs' => fn ($query) => $query->orderBy('id'),
            'inputs.inventoryItem:id,item_code,name',
            'inputs.unit:id,symbol',
            'inputs.location:id,name',
            'ledgerEntries' => fn ($query) => $query->orderBy('id'),
        ]);

        $ledgerUrl = route('inventory.ledger.index', ['reference' => $productionRun->run_number]);
        $entriesByItem = $productionRun->ledgerEntries->keyBy('inventory_item_id');

        return Inertia::render('production/runs/Show', [
            'run' => [
                'id' => $productionRun->id,
                'run_number' => $productionRun->run_number,
                'innovation' => [
                    'id' => $productionRun->innovation_id,
                    'code' => $productionRun->innovation?->innovation_code,
                    'name' => $productionRun->innovation?->name,
                    'url' => route('innovations.show', ['innovation' => $productionRun->innovation_id, 'formula' => $productionRun->innovation_formula_id]),
                ],
                'build_order' => [
                    'id' => $productionRun->build_order_id,
                    'number' => $productionRun->buildOrder?->order_number,
                    'status_label' => $productionRun->buildOrder?->status->label(),
                    'planned_quantity' => (float) $productionRun->buildOrder?->planned_quantity,
                    'produced_quantity' => (float) $productionRun->buildOrder?->produced_quantity,
                    'url' => route('production.build-orders.show', $productionRun->build_order_id),
                ],
                'formula_version' => $productionRun->formula?->version,
                'formula_yield' => $productionRun->formula ? (float) $productionRun->formula->yield_quantity : null,
                'location' => $productionRun->location?->name,
                'output_quantity' => (float) $productionRun->output_quantity,
                'total_input_cost' => (float) $productionRun->total_input_cost,
                'unit_production_cost' => (float) $productionRun->unit_production_cost,
                'markup_percentage' => $productionRun->markup_percentage !== null ? (float) $productionRun->markup_percentage : null,
                'suggested_selling_price' => $productionRun->suggested_selling_price !== null ? (float) $productionRun->suggested_selling_price : null,
                'completed_at' => $productionRun->completed_at->toIso8601String(),
                'completed_by' => $productionRun->completer?->name,
                'notes' => $productionRun->notes,
            ],
            'inputs' => $productionRun->inputs->map(function (ProductionRunInput $input) use ($entriesByItem): array {
                /** @var StockLedgerEntry|null $entry */
                $entry = $entriesByItem->get($input->inventory_item_id);

                return [
                    'id' => $input->id,
                    'item_code' => $input->inventoryItem?->item_code,
                    'item_name' => $input->inventoryItem?->name,
                    'item_url' => $input->inventoryItem ? route('inventory.items.show', $input->inventory_item_id) : null,
                    'unit_symbol' => $input->unit?->symbol,
                    'location' => $input->location?->name,
                    'formula_quantity' => (float) $input->formula_quantity,
                    'consumed_quantity' => (float) $input->consumed_quantity,
                    'unit_cost' => (float) $input->unit_cost,
                    'line_cost' => (float) $input->line_cost,
                    'ledger_entry_number' => $entry?->entry_number,
                    'balance_after' => $entry ? (float) $entry->balance_after : null,
                ];
            }),
            'ledgerUrl' => $ledgerUrl,
        ]);
    }

    /**
     * Complete a production run against a build order, consuming its formula's parts.
     */
    public function store(CompleteProductionRunRequest $request, BuildOrder $buildOrder, ProductionRunService $production): RedirectResponse
    {
        $data = $request->validated();

        try {
            $run = $production->complete($buildOrder, (float) $data['output_quantity'], $request->user(), $data['notes'] ?? null);
        } catch (StockPostingException $exception) {
            throw ValidationException::withMessages(['output_quantity' => $exception->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Production run :number completed.', ['number' => $run->run_number])]);

        return to_route('production.runs.show', $run);
    }
}
