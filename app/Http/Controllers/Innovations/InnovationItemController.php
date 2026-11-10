<?php

namespace App\Http\Controllers\Innovations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Innovations\InnovationItemRequest;
use App\Models\Innovation;
use App\Models\InnovationItem;
use App\Models\InventoryItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class InnovationItemController extends Controller
{
    /**
     * Add a line to the innovation's bill of materials.
     */
    public function store(InnovationItemRequest $request, Innovation $innovation): RedirectResponse
    {
        $innovation->innovationItems()->create($this->attributes($request));
        $innovation->forceFill(['updated_by' => $request->user()->id])->touch();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Bill of materials line added.')]);

        return back();
    }

    /**
     * Update a bill of materials line.
     */
    public function update(InnovationItemRequest $request, Innovation $innovation, InnovationItem $innovationItem): RedirectResponse
    {
        $innovationItem->update($this->attributes($request));
        $innovation->forceFill(['updated_by' => $request->user()->id])->touch();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Bill of materials line updated.')]);

        return back();
    }

    /**
     * Remove a bill of materials line.
     */
    public function destroy(Request $request, Innovation $innovation, InnovationItem $innovationItem): RedirectResponse
    {
        Gate::authorize('delete', $innovationItem);

        $innovationItem->delete();
        $innovation->forceFill(['updated_by' => $request->user()->id])->touch();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Bill of materials line removed.')]);

        return back();
    }

    /**
     * Validated attributes, defaulting the unit to the item's default unit.
     *
     * @return array<string, mixed>
     */
    private function attributes(InnovationItemRequest $request): array
    {
        $data = $request->validated();

        $data['unit_id'] ??= InventoryItem::query()->whereKey($data['inventory_item_id'])->value('default_unit_id');
        $data['sort_order'] ??= 0;

        return $data;
    }
}
