<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\LocationRequest;
use App\Models\Location;
use App\Support\PerPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LocationController extends Controller
{
    /**
     * List locations with search and active-state filters, and the stock each one holds.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status']);

        return Inertia::render('inventory/locations/Index', [
            'locations' => Location::query()
                ->withCount(['balances as item_count' => fn ($query) => $query->where('on_hand_quantity', '>', 0)])
                ->withSum('balances as total_on_hand', 'on_hand_quantity')
                ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where(
                    fn ($query) => $query->whereLike('code', "%{$search}%")->orWhereLike('name', "%{$search}%"),
                ))
                ->when(($filters['status'] ?? null) === 'active', fn ($query) => $query->where('is_active', true))
                ->when(($filters['status'] ?? null) === 'inactive', fn ($query) => $query->where('is_active', false))
                ->orderBy('name')
                ->paginate(PerPage::fromRequest($request))
                ->withQueryString()
                ->through(fn (Location $location): array => [
                    'id' => $location->id,
                    'code' => $location->code,
                    'name' => $location->name,
                    'description' => $location->description,
                    'is_active' => $location->is_active,
                    'is_main' => $location->isMain(),
                    'item_count' => (int) $location->getAttribute('item_count'),
                    'total_on_hand' => (float) $location->getAttribute('total_on_hand'),
                    'can_delete' => Gate::allows('delete', $location),
                ]),
            'filters' => $filters,
            'can' => [
                'manage' => Gate::allows('create', Location::class),
            ],
        ]);
    }

    /**
     * Create a location.
     */
    public function store(LocationRequest $request): RedirectResponse
    {
        Location::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Location created.')]);

        return back();
    }

    /**
     * Update a location. Deactivated locations keep their stock and history but take no new postings.
     */
    public function update(LocationRequest $request, Location $location): RedirectResponse
    {
        $location->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Location updated.')]);

        return back();
    }

    /**
     * Delete an unused location. Locations with stock or history must be deactivated instead.
     */
    public function destroy(Location $location): RedirectResponse
    {
        Gate::authorize('delete', $location);

        if ($location->isInUse()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('This location has stock or ledger history. Deactivate it instead.')]);

            return back();
        }

        $location->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Location deleted.')]);

        return back();
    }
}
