<?php

namespace App\Http\Controllers\Inventory;

use App\Enums\InventoryItemStatus;
use App\Enums\InventoryItemType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\InventoryItemRequest;
use App\Models\InnovationItem;
use App\Models\InventoryItem;
use App\Models\ItemCategory;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class InventoryItemController extends Controller
{
    /**
     * List inventory items with search, category, type, status, and low-stock filters.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'category', 'type', 'status', 'stock']);

        return Inertia::render('inventory/items/Index', [
            'items' => InventoryItem::query()
                ->with(['category:id,name', 'defaultUnit:id,name,symbol'])
                ->when($filters['search'] ?? null, fn ($query, string $search) => $query->search($search))
                ->when($filters['category'] ?? null, fn ($query, string $category) => $query->inCategory((int) $category))
                ->when(InventoryItemType::tryFrom((string) ($filters['type'] ?? '')), fn ($query, InventoryItemType $type) => $query->where('item_type', $type))
                ->when(InventoryItemStatus::tryFrom((string) ($filters['status'] ?? '')), fn ($query, InventoryItemStatus $status) => $query->where('status', $status))
                ->when(($filters['stock'] ?? null) === 'low', fn ($query) => $query->lowStock())
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString()
                ->through(fn (InventoryItem $item): array => $this->itemData($item)),
            'stats' => [
                'total' => InventoryItem::query()->count(),
                'low_stock' => InventoryItem::query()->lowStock()->count(),
                'stock_value' => (float) InventoryItem::query()->sum(DB::raw('quantity_on_hand * coalesce(unit_cost, 0)')),
            ],
            'filters' => $filters,
            'categories' => $this->categoryOptions(),
            'units' => Unit::query()->orderBy('name')->get(['id', 'name', 'symbol', 'is_active'])
                ->map(fn (Unit $unit): array => [
                    'value' => $unit->id,
                    'label' => "{$unit->name} ({$unit->symbol})",
                    'is_active' => $unit->is_active,
                ]),
            'types' => InventoryItemType::options(),
            'statuses' => InventoryItemStatus::options(),
            'can' => [
                'manage' => Gate::allows('create', InventoryItem::class),
            ],
        ]);
    }

    /**
     * Show an item with every field and the innovations whose BOM uses it.
     */
    public function show(InventoryItem $item): Response
    {
        Gate::authorize('view', $item);

        $item->load(['category.parent:id,name', 'defaultUnit:id,name,symbol', 'creator:id,name']);

        return Inertia::render('inventory/items/Show', [
            'item' => [
                ...$this->itemData($item),
                'category_path' => $item->category->parent
                    ? "{$item->category->parent->name} › {$item->category->name}"
                    : $item->category->name,
                'created_by' => $item->creator?->name,
                'created_at' => $item->created_at?->toIso8601String(),
                'updated_at' => $item->updated_at?->toIso8601String(),
            ],
            'usages' => $item->innovationItems()
                ->whereHas('innovation')
                ->with(['innovation:id,innovation_code,name', 'unit:id,symbol'])
                ->orderBy('id')
                ->get()
                ->map(fn (InnovationItem $line): array => [
                    'id' => $line->id,
                    'innovation_code' => $line->innovation->innovation_code,
                    'innovation_name' => $line->innovation->name,
                    'innovation_url' => route('innovations.show', $line->innovation),
                    'quantity_required' => (float) $line->quantity_required,
                    'unit_symbol' => $line->unit->symbol,
                    'is_optional' => $line->is_optional,
                    'usage_description' => $line->usage_description,
                ]),
        ]);
    }

    /**
     * Create an item; a blank item code is generated automatically.
     */
    public function store(InventoryItemRequest $request): RedirectResponse
    {
        InventoryItem::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Item created.')]);

        return back();
    }

    /**
     * Update an item.
     */
    public function update(InventoryItemRequest $request, InventoryItem $item): RedirectResponse
    {
        $item->update([
            ...$request->validated(),
            'item_code' => $request->validated('item_code') ?: $item->item_code,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Item updated.')]);

        return back();
    }

    /**
     * Soft delete an item. Items used in a bill of materials must be discontinued instead.
     */
    public function destroy(InventoryItem $item): RedirectResponse
    {
        Gate::authorize('delete', $item);

        if ($item->innovationItems()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('This item is used in an innovation bill of materials. Set its status to Discontinued instead.')]);

            return back();
        }

        $item->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Item deleted.')]);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function itemData(InventoryItem $item): array
    {
        return [
            'id' => $item->id,
            'item_code' => $item->item_code,
            'name' => $item->name,
            'item_type' => $item->item_type->value,
            'item_type_label' => $item->item_type->label(),
            'category_id' => $item->category_id,
            'category_name' => $item->category->name,
            'default_unit_id' => $item->default_unit_id,
            'unit_symbol' => $item->defaultUnit->symbol,
            'brand' => $item->brand,
            'model' => $item->model,
            'manufacturer' => $item->manufacturer,
            'part_number' => $item->part_number,
            'description' => $item->description,
            'specification' => $item->specification,
            'quantity_on_hand' => (float) $item->quantity_on_hand,
            'minimum_stock' => (float) $item->minimum_stock,
            'unit_cost' => $item->unit_cost === null ? null : (float) $item->unit_cost,
            'status' => $item->status->value,
            'status_label' => $item->status->label(),
            'is_low_stock' => $item->isLowStock(),
        ];
    }

    /**
     * Every item category, children labelled under their parent, with its active state.
     *
     * @return array<int, array{value: int, label: string, is_active: bool}>
     */
    private function categoryOptions(): array
    {
        return ItemCategory::query()
            ->with('parent:id,name')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'parent_id', 'name', 'is_active'])
            ->map(fn (ItemCategory $category): array => [
                'value' => $category->id,
                'label' => $category->parent ? "{$category->parent->name} › {$category->name}" : $category->name,
                'is_active' => $category->is_active,
            ])
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }
}
