<?php

namespace App\Models;

use App\Enums\StockTransactionType;
use Database\Factories\StockLedgerEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * One immutable stock movement. Mistakes are corrected with a reversal, never by editing.
 *
 * @property int $id
 * @property string $entry_number
 * @property Carbon $posted_at
 * @property StockTransactionType $transaction_type
 * @property int $inventory_item_id
 * @property int $location_id
 * @property string $quantity_in
 * @property string $quantity_out
 * @property string $unit_cost
 * @property string $total_value
 * @property string $balance_after
 * @property int|null $production_run_id
 * @property int|null $build_order_id
 * @property int|null $reversal_of_entry_id
 * @property string|null $reference_number
 * @property int|null $created_by
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['entry_number', 'posted_at', 'transaction_type', 'inventory_item_id', 'location_id', 'quantity_in', 'quantity_out', 'unit_cost', 'total_value', 'balance_after', 'production_run_id', 'build_order_id', 'reversal_of_entry_id', 'reference_number', 'created_by', 'notes'])]
class StockLedgerEntry extends Model
{
    /** @use HasFactory<StockLedgerEntryFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'posted_at' => 'datetime',
            'transaction_type' => StockTransactionType::class,
            'quantity_in' => 'decimal:3',
            'quantity_out' => 'decimal:3',
            'unit_cost' => 'decimal:2',
            'total_value' => 'decimal:2',
            'balance_after' => 'decimal:3',
        ];
    }

    /**
     * Whether a reversal can still be posted against this entry.
     */
    public function isReversible(): bool
    {
        return $this->transaction_type !== StockTransactionType::Reversal
            && $this->production_run_id === null
            && ! $this->reversal()->exists();
    }

    /** @return BelongsTo<InventoryItem, $this> */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class)->withTrashed();
    }

    /** @return BelongsTo<Location, $this> */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<ProductionRun, $this> */
    public function productionRun(): BelongsTo
    {
        return $this->belongsTo(ProductionRun::class);
    }

    /** @return BelongsTo<BuildOrder, $this> */
    public function buildOrder(): BelongsTo
    {
        return $this->belongsTo(BuildOrder::class);
    }

    /** @return BelongsTo<self, $this> */
    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_entry_id');
    }

    /** @return HasOne<self, $this> */
    public function reversal(): HasOne
    {
        return $this->hasOne(self::class, 'reversal_of_entry_id');
    }
}
