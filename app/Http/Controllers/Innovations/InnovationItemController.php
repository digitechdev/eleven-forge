<?php

namespace App\Http\Controllers\Innovations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Innovations\InnovationItemRequest;
use App\Models\Innovation;
use App\Models\InnovationFormula;
use App\Models\InnovationItem;
use App\Models\InventoryItem;
use App\Services\InnovationFormulaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Lines of an innovation's formula (versioned bill of materials). Lines can only change
 * while their formula is editable: a draft, or active and not yet used by a build order.
 */
class InnovationItemController extends Controller
{
    public function __construct(private InnovationFormulaService $formulas) {}

    /**
     * Add a line to the chosen formula; an innovation without formulas gets a version 1 draft first.
     */
    public function store(InnovationItemRequest $request, Innovation $innovation): RedirectResponse
    {
        $formula = $request->targetFormula() ?? $this->formulas->firstFormula($innovation);

        Gate::authorize('update', $formula);

        $formula->items()->create([...$this->attributes($request), 'innovation_id' => $innovation->id]);
        $this->formulas->refreshCosting($formula);
        $innovation->forceFill(['updated_by' => $request->user()->id])->touch();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Bill of materials line added.')]);

        return to_route('innovations.show', ['innovation' => $innovation, 'formula' => $formula->id]);
    }

    /**
     * Update a bill of materials line.
     */
    public function update(InnovationItemRequest $request, Innovation $innovation, InnovationItem $innovationItem): RedirectResponse
    {
        $innovationItem->update($this->attributes($request));
        $this->formulas->refreshCosting($innovationItem->formula);
        $innovation->forceFill(['updated_by' => $request->user()->id])->touch();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Bill of materials line updated.')]);

        return to_route('innovations.show', ['innovation' => $innovation, 'formula' => $innovationItem->innovation_formula_id]);
    }

    /**
     * Remove a bill of materials line.
     */
    public function destroy(Request $request, Innovation $innovation, InnovationItem $innovationItem): RedirectResponse
    {
        Gate::authorize('delete', $innovationItem);
        Gate::authorize('update', $innovationItem->formula);

        /** @var InnovationFormula $formula */
        $formula = $innovationItem->formula;
        $innovationItem->delete();
        $this->formulas->refreshCosting($formula);
        $innovation->forceFill(['updated_by' => $request->user()->id])->touch();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Bill of materials line removed.')]);

        return to_route('innovations.show', ['innovation' => $innovation, 'formula' => $formula->id]);
    }

    /**
     * Validated attributes, defaulting the unit to the item's default unit.
     *
     * @return array<string, mixed>
     */
    private function attributes(InnovationItemRequest $request): array
    {
        $data = collect($request->validated())->except('innovation_formula_id')->all();

        $data['unit_id'] ??= InventoryItem::query()->whereKey($data['inventory_item_id'])->value('default_unit_id');
        $data['sort_order'] ??= 0;

        return $data;
    }
}
