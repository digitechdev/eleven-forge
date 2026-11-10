<?php

namespace App\Http\Controllers\Inventory;

use App\Enums\FormulaStatus;
use App\Enums\InventoryItemStatus;
use App\Enums\InventoryItemType;
use App\Enums\StockStatus;
use App\Enums\StockTransactionType;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Stock\ProvidesPostingOptions;
use App\Http\Requests\Inventory\InventoryItemRequest;
use App\Models\InnovationItem;
use App\Models\InventoryBalance;
use App\Models\InventoryItem;
use App\Models\InventoryItemMedia;
use App\Models\InventoryItemPriceChange;
use App\Models\ItemCategory;
use App\Models\Location;
use App\Models\StockLedgerEntry;
use App\Models\Unit;
use App\Services\InventoryItemPriceService;
use App\Services\StockLedgerService;
use App\Support\PerPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class InventoryItemController extends Controller
{
    use ProvidesPostingOptions;

    /**
     * The item page's tabs; the first is the default.
     *
     * @var list<string>
     */
    private const array TABS = ['overview', 'stock', 'movements', 'media', 'cost-history'];

    /**
     * How many of the latest ledger entries the Movements tab lists.
     */
    private const int MOVEMENTS_LIMIT = 50;

    /**
     * List inventory items with their stock, in total or at one location, filtered by search,
     * category, type, item status, and stock status.
     */
    public function index(Request $request): Response
    {
        $filters = $this->indexFilters($request);
        $locationId = filled($filters['location'] ?? null) ? (int) $filters['location'] : null;

        return Inertia::render('inventory/items/Index', [
            'items' => InventoryItem::query()
                ->withStockQuantities($locationId)
                ->with(['category:id,name', 'defaultUnit:id,name,symbol'])
                ->when($filters['search'] ?? null, fn ($query, string $search) => $query->search($search))
                ->when($filters['category'] ?? null, fn ($query, string $category) => $query->inCategory((int) $category))
                ->when(InventoryItemType::tryFrom((string) ($filters['type'] ?? '')), fn ($query, InventoryItemType $type) => $query->where('item_type', $type))
                ->when(InventoryItemStatus::tryFrom((string) ($filters['item_status'] ?? '')), fn ($query, InventoryItemStatus $status) => $query->where('status', $status))
                ->when(StockStatus::tryFrom((string) ($filters['stock'] ?? '')), fn ($query, StockStatus $status) => $query->withStockStatus($status, $locationId))
                ->orderBy('name')
                ->paginate(PerPage::fromRequest($request))
                ->withQueryString()
                ->through(fn (InventoryItem $item): array => $this->itemData(
                    $item,
                    (float) $item->getAttribute('stock_on_hand'),
                    (float) $item->getAttribute('stock_available'),
                )),
            'stats' => $this->stockStats($locationId),
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
            'stockStatuses' => StockStatus::options(),
            'locations' => $this->locationOptions(Location::query()->active()),
            'filterLocations' => $this->locationOptions(),
            'defaultLocationId' => Location::query()->where('code', Location::MAIN_CODE)->where('is_active', true)->value('id'),
            'can' => [
                'manage' => Gate::allows('create', InventoryItem::class),
            ],
        ]);
    }

    /**
     * Show an item as tabs: overview, stock by location, movements, media, and cost history.
     */
    public function show(Request $request, InventoryItem $item): Response
    {
        Gate::authorize('view', $item);

        $item->load([
            'category.parent:id,name',
            'defaultUnit:id,name,symbol',
            'creator:id,name',
            'balances.location:id,code,name,is_active',
            'media',
        ]);

        $canPost = Gate::allows('create', StockLedgerEntry::class) && $item->status === InventoryItemStatus::Active;
        $onHand = (float) $item->quantity_on_hand;
        $available = round($onHand - (float) $item->balances->sum(fn (InventoryBalance $balance): float => (float) $balance->reserved_quantity), 3);

        return Inertia::render('inventory/items/Show', [
            'tab' => in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : self::TABS[0],
            'item' => [
                ...$this->itemData($item, $onHand, $available),
                'category_path' => $item->category->parent
                    ? "{$item->category->parent->name} › {$item->category->name}"
                    : $item->category->name,
                'created_by' => $item->creator?->name,
                'created_at' => $item->created_at?->toIso8601String(),
                'updated_at' => $item->updated_at?->toIso8601String(),
            ],
            'balances' => $item->balances
                ->sortBy(fn (InventoryBalance $balance): string => $balance->location->name, SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->map(fn (InventoryBalance $balance): array => $this->balanceData($balance, $item)),
            'movements' => fn () => $item->ledgerEntries()
                ->with('location:id,code,name')
                ->latest('posted_at')
                ->latest('id')
                ->limit(self::MOVEMENTS_LIMIT)
                ->get()
                ->map(fn (StockLedgerEntry $entry): array => [
                    'id' => $entry->id,
                    'entry_number' => $entry->entry_number,
                    'posted_at' => $entry->posted_at->toIso8601String(),
                    'type' => $entry->transaction_type->value,
                    'type_label' => $entry->transaction_type->label(),
                    'location_name' => $entry->location->name,
                    'quantity_in' => (float) $entry->quantity_in,
                    'quantity_out' => (float) $entry->quantity_out,
                    'balance_after' => (float) $entry->balance_after,
                    'reference_number' => $entry->reference_number,
                ]),
            'movementsLimit' => self::MOVEMENTS_LIMIT,
            'priceHistory' => fn () => $item->priceChanges()
                ->with('changer:id,name')
                ->latest('effective_at')
                ->latest('id')
                ->get()
                ->map(fn (InventoryItemPriceChange $change): array => [
                    'id' => $change->id,
                    'effective_at' => $change->effective_at->toIso8601String(),
                    'previous_unit_cost' => $change->previous_unit_cost === null ? null : (float) $change->previous_unit_cost,
                    'new_unit_cost' => $change->new_unit_cost === null ? null : (float) $change->new_unit_cost,
                    'changed_by' => $change->changer?->name,
                    'reason' => $change->reason,
                ]),
            'media' => $item->media->map(fn (InventoryItemMedia $media): array => [
                'id' => $media->id,
                'media_type' => $media->media_type->value,
                'url' => $media->url(),
                'original_name' => $media->original_name,
                'mime_type' => $media->mime_type,
                'size' => $media->size,
                'sort_order' => $media->sort_order,
            ]),
            'mediaLimit' => InventoryItemMedia::MAX_PER_ITEM,
            'ledgerUrl' => $this->ledgerUrl($item),
            'posting' => $canPost ? fn (): array => $this->postingOptions() : null,
            'can' => [
                'update' => Gate::allows('update', $item),
                'post' => $canPost,
            ],
            'usages' => fn () => $item->innovationItems()
                ->whereHas('innovation')
                ->whereHas('formula', fn ($query) => $query->where('status', '!=', FormulaStatus::Archived))
                ->with(['innovation:id,innovation_code,name', 'unit:id,symbol', 'formula:id,version,status'])
                ->get()
                // One row per innovation: its active formula, otherwise its newest draft.
                ->sortByDesc(fn (InnovationItem $line): array => [$line->formula->status === FormulaStatus::Active, $line->formula->version])
                ->unique('innovation_id')
                ->sortBy(fn (InnovationItem $line): string => $line->innovation->name)
                ->values()
                ->map(fn (InnovationItem $line): array => [
                    'id' => $line->id,
                    'innovation_code' => $line->innovation->innovation_code,
                    'innovation_name' => $line->innovation->name,
                    'innovation_url' => route('innovations.show', $line->innovation),
                    'quantity_required' => (float) $line->quantity_required,
                    'unit_symbol' => $line->unit->symbol,
                    'is_optional' => $line->is_optional,
                    'usage_description' => $line->usage_description,
                    'formula_label' => "v{$line->formula->version} · {$line->formula->status->label()}",
                ]),
        ]);
    }

    /**
     * Create an item; a blank item code is generated automatically. Opening stock, when given,
     * is posted to the ledger in the same transaction.
     */
    public function store(InventoryItemRequest $request, InventoryItemPriceService $prices, StockLedgerService $ledger): RedirectResponse
    {
        DB::transaction(function () use ($request, $prices, $ledger): void {
            $item = $prices->create([
                ...$request->itemAttributes(),
                'quantity_on_hand' => 0,
                'created_by' => $request->user()->id,
            ], $request->user());

            if ($request->hasOpeningStock()) {
                $openingCost = $request->validated('opening_unit_cost');

                $ledger->post(
                    $item,
                    Location::query()->findOrFail((int) $request->validated('opening_location_id')),
                    StockTransactionType::OpeningBalance,
                    (float) $request->validated('opening_quantity'),
                    0,
                    $openingCost === null ? null : (float) $openingCost,
                    $request->user(),
                    ['notes' => __('Opening stock entered when the item was created.')],
                );
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Item created.')]);

        return back();
    }

    /**
     * Update an item's details. A unit cost change is recorded in its price history;
     * quantities are never changed here.
     */
    public function update(InventoryItemRequest $request, InventoryItem $item, InventoryItemPriceService $prices): RedirectResponse
    {
        $prices->update($item, [
            ...$request->itemAttributes(),
            'item_code' => $request->validated('item_code') ?: $item->item_code,
        ], $request->user(), $request->validated('cost_change_reason'));

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
     * The item's fields with its stock, in total or at the filtered location.
     *
     * @return array<string, mixed>
     */
    private function itemData(InventoryItem $item, float $onHand, float $available): array
    {
        $stockStatus = StockStatus::forQuantity($available, (float) $item->minimum_stock);

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
            'reorder_level' => (float) $item->reorder_level,
            'unit_cost' => $item->unit_cost === null ? null : (float) $item->unit_cost,
            'status' => $item->status->value,
            'status_label' => $item->status->label(),
            'on_hand' => $onHand,
            'available' => $available,
            'stock_status' => $stockStatus->value,
            'stock_status_label' => $stockStatus->label(),
            'stock_value' => round($onHand * (float) ($item->unit_cost ?? 0), 2),
            'ledger_url' => $this->ledgerUrl($item),
        ];
    }

    /**
     * One location's stock with its status based on available quantity.
     *
     * @return array{id: int, location_id: int, location_code: string, location_name: string, location_is_active: bool, on_hand: float, reserved: float, available: float, status: string, status_label: string, ledger_url: string}
     */
    private function balanceData(InventoryBalance $balance, InventoryItem $item): array
    {
        $status = StockStatus::forQuantity($balance->availableQuantity(), (float) $item->minimum_stock);

        return [
            'id' => $balance->id,
            'location_id' => $balance->location_id,
            'location_code' => $balance->location->code,
            'location_name' => $balance->location->name,
            'location_is_active' => $balance->location->is_active,
            'on_hand' => (float) $balance->on_hand_quantity,
            'reserved' => (float) $balance->reserved_quantity,
            'available' => $balance->availableQuantity(),
            'status' => $status->value,
            'status_label' => $status->label(),
            'ledger_url' => route('inventory.ledger.index', ['item' => $item->id, 'location' => $balance->location_id]),
        ];
    }

    /**
     * The list filters, reading the older "status" (item or stock status) and "stock=low" forms too.
     *
     * @return array{search?: string, category?: string, type?: string, item_status?: string, stock?: string, location?: string}
     */
    private function indexFilters(Request $request): array
    {
        $legacyStatus = (string) $request->query('status', '');
        $itemStatus = $request->query('item_status') ?? (InventoryItemStatus::tryFrom($legacyStatus) ? $legacyStatus : null);
        $stockStatus = StockStatus::fromFilter((string) ($request->query('stock') ?? $legacyStatus));

        return array_filter([
            ...$request->only(['search', 'category', 'type', 'location']),
            'item_status' => $itemStatus,
            'stock' => $stockStatus?->value,
        ], fn (mixed $value): bool => filled($value));
    }

    /**
     * Stock value and low / out-of-stock item counts, in total or at one location.
     *
     * @return array{stock_value: float, low_stock: int, out_of_stock: int}
     */
    private function stockStats(?int $locationId): array
    {
        return [
            'stock_value' => round((float) InventoryItem::query()
                ->selectRaw('sum('.InventoryItem::onHandQuantitySql($locationId).' * coalesce(inventory_items.unit_cost, 0)) as stock_value', InventoryItem::stockBindings($locationId))
                ->value('stock_value'), 2),
            'low_stock' => InventoryItem::query()->withStockStatus(StockStatus::Low, $locationId)->count(),
            'out_of_stock' => InventoryItem::query()->withStockStatus(StockStatus::Out, $locationId)->count(),
        ];
    }

    /**
     * The item's full stock ledger.
     */
    private function ledgerUrl(InventoryItem $item): string
    {
        return route('inventory.ledger.index', ['item' => $item->id]);
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
