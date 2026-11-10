<?php

namespace App\Models;

use Database\Factories\InnovationItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of an innovation formula (versioned bill of materials).
 *
 * @property int $id
 * @property int $innovation_id
 * @property int $innovation_formula_id
 * @property int $inventory_item_id
 * @property int $unit_id
 * @property string $quantity_required
 * @property string|null $usage_description
 * @property string|null $specification_notes
 * @property bool $is_optional
 * @property int $sort_order
 */
#[Fillable(['innovation_id', 'innovation_formula_id', 'inventory_item_id', 'unit_id', 'quantity_required', 'usage_description', 'specification_notes', 'is_optional', 'sort_order'])]
class InnovationItem extends Model
{
    /** @use HasFactory<InnovationItemFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['quantity_required' => 'decimal:3', 'is_optional' => 'boolean', 'sort_order' => 'integer'];
    }

    /** @return BelongsTo<Innovation, $this> */
    public function innovation(): BelongsTo
    {
        return $this->belongsTo(Innovation::class);
    }

    /** @return BelongsTo<InnovationFormula, $this> */
    public function formula(): BelongsTo
    {
        return $this->belongsTo(InnovationFormula::class, 'innovation_formula_id');
    }

    /** @return BelongsTo<InventoryItem, $this> */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /** @return BelongsTo<Unit, $this> */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
