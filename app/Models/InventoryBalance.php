<?php

namespace App\Models;

use App\Enums\StockStatus;
use Database\Factories\InventoryBalanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Current stock of one item at one location. Only the stock ledger service changes it.
 *
 * @property int $id
 * @property int $inventory_item_id
 * @property int $location_id
 * @property string $on_hand_quantity
 * @property string $reserved_quantity
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['inventory_item_id', 'location_id', 'on_hand_quantity', 'reserved_quantity'])]
class InventoryBalance extends Model
{
    /** @use HasFactory<InventoryBalanceFactory> */
    use HasFactory;

    /**
     * SQL for on hand minus reserved, qualified so it works inside subqueries.
     */
    private const string AVAILABLE_SQL = '(inventory_balances.on_hand_quantity - inventory_balances.reserved_quantity)';

    protected function casts(): array
    {
        return ['on_hand_quantity' => 'decimal:3', 'reserved_quantity' => 'decimal:3'];
    }

    /**
     * On hand minus reserved.
     */
    public function availableQuantity(): float
    {
        return round((float) $this->on_hand_quantity - (float) $this->reserved_quantity, 3);
    }

    /**
     * Out of stock when nothing is available, low when available is at or below the item's positive minimum.
     */
    public function stockStatus(): StockStatus
    {
        return StockStatus::forQuantity($this->availableQuantity(), (float) $this->inventoryItem->minimum_stock);
    }

    /** @return BelongsTo<InventoryItem, $this> */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /** @return BelongsTo<Location, $this> */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Balances whose stock status (see stockStatus()) matches the given one.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function withStockStatus(Builder $query, StockStatus $status): void
    {
        $available = self::AVAILABLE_SQL;

        match ($status) {
            StockStatus::Out => $query->whereRaw("{$available} <= 0"),
            StockStatus::Low => $query->whereRaw("{$available} > 0")->whereHas('inventoryItem', fn (Builder $item) => $item
                ->where('inventory_items.minimum_stock', '>', 0)
                ->whereRaw("{$available} <= inventory_items.minimum_stock")),
            StockStatus::In => $query->whereRaw("{$available} > 0")->whereHas('inventoryItem', fn (Builder $item) => $item
                ->where(fn (Builder $item) => $item
                    ->where('inventory_items.minimum_stock', '<=', 0)
                    ->orWhereRaw("{$available} > inventory_items.minimum_stock"))),
        };
    }

    /**
     * Balances at a location.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function atLocation(Builder $query, int $locationId): void
    {
        $query->where('inventory_balances.location_id', $locationId);
    }
}
