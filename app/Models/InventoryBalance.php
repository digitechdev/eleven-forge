<?php

namespace App\Models;

use Database\Factories\InventoryBalanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
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
}
