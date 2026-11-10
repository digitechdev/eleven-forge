<?php

namespace App\Http\Controllers;

use App\Enums\InnovationStatus;
use App\Enums\InnovationType;
use App\Enums\InnovationVisibility;
use App\Enums\InventoryItemStatus;
use App\Models\Innovation;
use App\Models\InventoryItem;
use App\Models\Unit;
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

        return Inertia::render('Dashboard', [
            'innovationStats' => $canViewInnovations ? $this->innovationStats() : null,
            'lowStockItems' => $canViewInventory ? Inertia::defer(fn (): array => $this->lowStockItems(), 'activity') : null,
            'recentInnovations' => $canViewInnovations ? Inertia::defer(fn (): array => $this->recentInnovations(), 'activity') : null,
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
     * The five active items furthest below their minimum stock.
     *
     * @return array<int, array{id: int, item_code: string, name: string, quantity_on_hand: float, minimum_stock: float, unit: string|null, url: string|null}>
     */
    private function lowStockItems(): array
    {

        return InventoryItem::query()
            ->with('defaultUnit:id,symbol')
            ->where('status', InventoryItemStatus::Active)
            ->where('minimum_stock', '>', 0)
            ->whereColumn('quantity_on_hand', '<=', 'minimum_stock')
            ->orderByRaw('quantity_on_hand / minimum_stock')
            ->orderBy('name')
            ->limit(5)
            ->get()
            ->map(fn (InventoryItem $item): array => [
                'id' => $item->id,
                'item_code' => $item->item_code,
                'name' => $item->name,
                'quantity_on_hand' => (float) $item->quantity_on_hand,
                'minimum_stock' => (float) $item->minimum_stock,
                'unit' => $item->defaultUnit?->symbol,
                'url' => route('inventory.items.show', $item),
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
                'label' => __('Manage inventory items'),
                'description' => __('Update stock levels, specifications, and costs.'),
                'href' => route('inventory.items.index'),
            ];
        }

        if (Gate::allows('create', Unit::class)) {
            $shortcuts[] = [
                'key' => 'manage-units',
                'label' => __('Manage units'),
                'description' => __('Maintain the units used by items and bills of materials.'),
                'href' => route('inventory.units.index'),
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
