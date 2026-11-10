<?php

namespace App\Models;

use Database\Factories\ProductionRunInputFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Snapshot of one part consumed by a production run, with the quantity and cost at the time.
 *
 * @property int $id
 * @property int $production_run_id
 * @property int $inventory_item_id
 * @property int $unit_id
 * @property int $location_id
 * @property string $formula_quantity
 * @property string $consumed_quantity
 * @property string $unit_cost
 * @property string $line_cost
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['production_run_id', 'inventory_item_id', 'unit_id', 'location_id', 'formula_quantity', 'consumed_quantity', 'unit_cost', 'line_cost'])]
class ProductionRunInput extends Model
{
    /** @use HasFactory<ProductionRunInputFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'formula_quantity' => 'decimal:3',
            'consumed_quantity' => 'decimal:3',
            'unit_cost' => 'decimal:2',
            'line_cost' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<ProductionRun, $this> */
    public function productionRun(): BelongsTo
    {
        return $this->belongsTo(ProductionRun::class);
    }

    /** @return BelongsTo<InventoryItem, $this> */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class)->withTrashed();
    }

    /** @return BelongsTo<Unit, $this> */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /** @return BelongsTo<Location, $this> */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
