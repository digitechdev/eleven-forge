<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\UnitRequest;
use App\Models\Unit;
use App\Support\PerPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UnitController extends Controller
{
    /**
     * List units with search and active-state filters.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status']);

        return Inertia::render('inventory/units/Index', [
            'units' => Unit::query()
                ->withCount(['inventoryItems', 'innovationItems'])
                ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where(
                    fn ($query) => $query->whereLike('name', "%{$search}%")->orWhereLike('symbol', "%{$search}%"),
                ))
                ->when(($filters['status'] ?? null) === 'active', fn ($query) => $query->where('is_active', true))
                ->when(($filters['status'] ?? null) === 'inactive', fn ($query) => $query->where('is_active', false))
                ->orderBy('name')
                ->paginate(PerPage::fromRequest($request))
                ->withQueryString()
                ->through(fn (Unit $unit): array => [
                    'id' => $unit->id,
                    'name' => $unit->name,
                    'symbol' => $unit->symbol,
                    'decimal_places' => $unit->decimal_places,
                    'is_active' => $unit->is_active,
                    'usage_count' => $unit->inventory_items_count + $unit->innovation_items_count,
                ]),
            'filters' => $filters,
            'can' => [
                'manage' => Gate::allows('create', Unit::class),
            ],
        ]);
    }

    /**
     * Create a unit.
     */
    public function store(UnitRequest $request): RedirectResponse
    {
        Unit::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Unit created.')]);

        return back();
    }

    /**
     * Update a unit.
     */
    public function update(UnitRequest $request, Unit $unit): RedirectResponse
    {
        $unit->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Unit updated.')]);

        return back();
    }

    /**
     * Delete an unused unit. Units already referenced must be deactivated instead.
     */
    public function destroy(Unit $unit): RedirectResponse
    {
        Gate::authorize('delete', $unit);

        if ($unit->inventoryItems()->exists() || $unit->innovationItems()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('This unit is in use. Deactivate it instead.')]);

            return back();
        }

        $unit->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Unit deleted.')]);

        return back();
    }
}
