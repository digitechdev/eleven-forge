<?php

namespace App\Http\Controllers;

use App\Enums\BuildOrderStatus;
use App\Enums\InnovationStatus;
use App\Enums\InnovationType;
use App\Enums\InnovationVisibility;
use App\Enums\StockStatus;
use App\Models\BuildOrder;
use App\Models\Innovation;
use App\Models\InventoryItem;
use App\Models\Location;
use App\Models\ProductionRun;
use App\Models\StockLedgerEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the overview, limited to the data and shortcuts the user may access.
     */
    public function __invoke(Request $request): Response
    {
        $canViewInnovations = Gate::allows('viewAny', Innovation::class);
        $canViewInventory = Gate::allows('viewAny', InventoryItem::class);
        $canViewStock = Gate::allows('viewAny', StockLedgerEntry::class);
        $canViewProduction = Gate::allows('viewAny', BuildOrder::class);

        $location = $canViewStock && $request->integer('location') > 0
            ? Location::query()->find($request->integer('location'))
            : null;

        return Inertia::render('Dashboard', [
            'innovationStats' => $canViewInnovations ? $this->innovationStats() : null,
            'stockStats' => $canViewStock ? $this->stockStats($location) : null,
            'locations' => $canViewStock ? Location::query()->orderBy('name')->get(['id', 'name'])->map(fn (Location $option): array => ['value' => $option->id, 'label' => $option->name])->all() : null,
            'filters' => ['location' => $location?->id],
            'lowStockItems' => $canViewInventory ? Inertia::defer(fn (): array => $this->lowStockItems($location), 'activity') : null,
            'recentInnovations' => $canViewInnovations ? Inertia::defer(fn (): array => $this->recentInnovations(), 'activity') : null,
            'activeBuildOrders' => $canViewProduction ? Inertia::defer(fn (): array => $this->activeBuildOrders($location), 'activity') : null,
            'recentRuns' => $canViewProduction ? Inertia::defer(fn (): array => $this->recentRuns($location), 'activity') : null,
            'recentMovements' => $canViewStock ? Inertia::defer(fn (): array => $this->recentMovements($location), 'activity') : null,
            'shortcuts' => $this->shortcuts(),
        ]);
    }

    /**
     * Innovation counts by status, type, and visibility.
     *
     * @return array{total: int, featured: int, public: int, internal: int, byStatus: array<int, array{value: string, label: string, count: int}>, byType: array<int, array{value: string, label: string, count: int}>}
     */
    private function innovationStats(): array
    {
        $statusCounts = Innovation::query()->toBase()->selectRaw('status, count(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $typeCounts = Innovation::query()->toBase()->selectRaw('type, count(*) as aggregate')->groupBy('type')->pluck('aggregate', 'type');
        $visibilityCounts = Innovation::query()->toBase()->selectRaw('visibility, count(*) as aggregate')->groupBy('visibility')->pluck('aggregate', 'visibility');

        return [
            'total' => (int) $statusCounts->sum(),
            'featured' => Innovation::query()->where('is_featured', true)->count(),
            'public' => (int) ($visibilityCounts[InnovationVisibility::Public->value] ?? 0),
            'internal' => (int) ($visibilityCounts[InnovationVisibility::Internal->value] ?? 0),
            'byStatus' => array_map(fn (InnovationStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
                'count' => (int) ($statusCounts[$status->value] ?? 0),
            ], InnovationStatus::cases()),
            'byType' => array_map(fn (InnovationType $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
                'count' => (int) ($typeCounts[$type->value] ?? 0),
            ], InnovationType::cases()),
        ];
    }

    /**
     * Stock value plus low- and out-of-stock counts, overall or at one location.
     * Uses the same rules as the Items page, so these figures match what its filters list.
     *
     * @return array{value: float, low: int, out: int, url: string}
     */
    private function stockStats(?Location $location): array
    {
        $locationId = $location?->id;

        return [
            'value' => round((float) InventoryItem::query()
                ->selectRaw('sum('.InventoryItem::onHandQuantitySql($locationId).' * coalesce(inventory_items.unit_cost, 0)) as stock_value', InventoryItem::stockBindings($locationId))
                ->value('stock_value'), 2),
            'low' => InventoryItem::query()->withStockStatus(StockStatus::Low, $locationId)->count(),
            'out' => InventoryItem::query()->withStockStatus(StockStatus::Out, $locationId)->count(),
            'url' => route('inventory.items.index', array_filter(['location' => $locationId])),
        ];
    }

    /**
     * The five items closest to running out among those at low stock, overall or at one location.
     *
     * @return array<int, array{id: int, item_code: string, name: string, quantity_on_hand: float, minimum_stock: float, unit: string|null, url: string|null}>
     */
    private function lowStockItems(?Location $location): array
    {
        $locationId = $location?->id;

        return InventoryItem::query()
            ->withStockQuantities($locationId)
            ->withStockStatus(StockStatus::Low, $locationId)
            ->with('defaultUnit:id,symbol')
            ->orderBy('stock_available')
            ->orderBy('name')
            ->limit(5)
            ->get()
            ->map(fn (InventoryItem $item): array => [
                'id' => $item->id,
                'item_code' => $item->item_code,
                'name' => $item->name,
                'quantity_on_hand' => (float) $item->getAttribute('stock_available'),
                'minimum_stock' => (float) $item->minimum_stock,
                'unit' => $item->defaultUnit->symbol,
                'url' => route('inventory.items.show', $item),
            ])
            ->all();
    }

    /**
     * Planned and in-progress build orders, overall or consuming from one location.
     *
     * @return array{count: int, orders: array<int, array{id: int, order_number: string, innovation: string, status_label: string, planned: float, produced: float, url: string}>}
     */
    private function activeBuildOrders(?Location $location): array
    {
        $query = BuildOrder::query()
            ->whereIn('status', [BuildOrderStatus::Planned, BuildOrderStatus::InProgress])
            ->when($location, fn ($query, Location $location) => $query->where('location_id', $location->id));

        return [
            'count' => (clone $query)->count(),
            'orders' => $query->with('innovation:id,name')
                ->latest('updated_at')
                ->limit(5)
                ->get()
                ->map(fn (BuildOrder $order): array => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'innovation' => $order->innovation->name,
                    'status_label' => $order->status->label(),
                    'planned' => (float) $order->planned_quantity,
                    'produced' => (float) $order->produced_quantity,
                    'url' => route('production.build-orders.show', $order),
                ])
                ->all(),
        ];
    }

    /**
     * The five most recently completed production runs.
     *
     * @return array<int, array{id: int, run_number: string, innovation: string, output_quantity: float, unit_cost: float, completed_at: string, url: string}>
     */
    private function recentRuns(?Location $location): array
    {
        return ProductionRun::query()
            ->with('innovation:id,name')
            ->when($location, fn ($query, Location $location) => $query->where('location_id', $location->id))
            ->latest('completed_at')
            ->limit(5)
            ->get()
            ->map(fn (ProductionRun $run): array => [
                'id' => $run->id,
                'run_number' => $run->run_number,
                'innovation' => $run->innovation->name,
                'output_quantity' => (float) $run->output_quantity,
                'unit_cost' => (float) $run->unit_production_cost,
                'completed_at' => $run->completed_at->toIso8601String(),
                'url' => route('production.runs.show', $run),
            ])
            ->all();
    }

    /**
     * The five latest stock ledger movements.
     *
     * @return array<int, array{id: int, entry_number: string, item: string, location: string, type_label: string, quantity: float, posted_at: string}>
     */
    private function recentMovements(?Location $location): array
    {
        return StockLedgerEntry::query()
            ->with(['inventoryItem:id,name', 'location:id,name'])
            ->when($location, fn ($query, Location $location) => $query->where('location_id', $location->id))
            ->latest('posted_at')
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (StockLedgerEntry $entry): array => [
                'id' => $entry->id,
                'entry_number' => $entry->entry_number,
                'item' => $entry->inventoryItem->name,
                'location' => $entry->location->name,
                'type_label' => $entry->transaction_type->label(),
                'quantity' => round((float) $entry->quantity_in - (float) $entry->quantity_out, 3),
                'posted_at' => $entry->posted_at->toIso8601String(),
            ])
            ->all();
    }

    /**
     * The five most recently updated innovations.
     *
     * @return array<int, array{id: int, innovation_code: string, name: string, status: string, status_label: string, type_label: string, is_public: bool, updated_at: string|null, url: string|null}>
     */
    private function recentInnovations(): array
    {

        return Innovation::query()
            ->latest('updated_at')
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (Innovation $innovation): array => [
                'id' => $innovation->id,
                'innovation_code' => $innovation->innovation_code,
                'name' => $innovation->name,
                'status' => $innovation->status->value,
                'status_label' => $innovation->status->label(),
                'type_label' => $innovation->type->label(),
                'is_public' => $innovation->visibility === InnovationVisibility::Public,
                'updated_at' => $innovation->updated_at?->toIso8601String(),
                'url' => route('innovations.show', $innovation),
            ])
            ->all();
    }

    /**
     * Quick actions for the work the user's role allows.
     *
     * @return array<int, array{key: string, label: string, description: string, href: string}>
     */
    private function shortcuts(): array
    {
        $shortcuts = [];

        if (Gate::allows('create', Innovation::class)) {
            $shortcuts[] = [
                'key' => 'create-innovation',
                'label' => __('New innovation'),
                'description' => __('Register a software, hardware, or hybrid innovation.'),
                'href' => route('innovations.create'),
            ];
        }

        if (Gate::allows('create', InventoryItem::class)) {
            $shortcuts[] = [
                'key' => 'manage-items',
                'label' => __('Items'),
                'description' => __('Add items, specifications, and costs, and see stock by location.'),
                'href' => route('inventory.items.index'),
            ];
        }

        if (Gate::allows('create', StockLedgerEntry::class)) {
            $shortcuts[] = [
                'key' => 'post-stock',
                'label' => __('Post stock'),
                'description' => __('Receive, issue, return, or adjust stock in Stock movements.'),
                'href' => route('inventory.ledger.index'),
            ];
        }

        if (Gate::allows('create', BuildOrder::class)) {
            $shortcuts[] = [
                'key' => 'build-orders',
                'label' => __('Builds'),
                'description' => __('Plan builds of an innovation and complete production runs.'),
                'href' => route('production.build-orders.index'),
            ];
        }

        if (Gate::allows('viewAny', User::class)) {
            $shortcuts[] = [
                'key' => 'manage-users',
                'label' => __('Manage users'),
                'description' => __('Add accounts, assign roles, and deactivate access.'),
                'href' => route('administration.users.index'),
            ];
        }

        return $shortcuts;
    }
}
