<?php

namespace App\Models;

use Database\Factories\InventoryItemPriceChangeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A recorded change to an item's reference unit cost.
 *
 * @property int $id
 * @property int $inventory_item_id
 * @property string|null $previous_unit_cost
 * @property string|null $new_unit_cost
 * @property int|null $changed_by
 * @property string|null $reason
 * @property Carbon $effective_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['inventory_item_id', 'previous_unit_cost', 'new_unit_cost', 'changed_by', 'reason', 'effective_at'])]
class InventoryItemPriceChange extends Model
{
    /** @use HasFactory<InventoryItemPriceChangeFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['previous_unit_cost' => 'decimal:2', 'new_unit_cost' => 'decimal:2', 'effective_at' => 'datetime'];
    }

    /** @return BelongsTo<InventoryItem, $this> */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /** @return BelongsTo<User, $this> */
    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
