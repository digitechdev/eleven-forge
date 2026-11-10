<?php

namespace App\Models;

use App\Enums\FormulaStatus;
use Database\Factories\InnovationFormulaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One version of an innovation's bill of materials: the parts needed to build `yield_quantity` units.
 *
 * @property int $id
 * @property int $innovation_id
 * @property int $version
 * @property string $yield_quantity
 * @property FormulaStatus $status
 * @property string|null $notes
 * @property Carbon|null $activated_at
 * @property string|null $estimated_unit_cost
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['innovation_id', 'version', 'yield_quantity', 'status', 'notes', 'activated_at', 'estimated_unit_cost'])]
class InnovationFormula extends Model
{
    /** @use HasFactory<InnovationFormulaFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'yield_quantity' => 'decimal:3',
            'status' => FormulaStatus::class,
            'activated_at' => 'datetime',
            'estimated_unit_cost' => 'decimal:2',
        ];
    }

    /**
     * Whether any build order already uses this formula, which freezes its lines.
     */
    public function isUsed(): bool
    {
        if ($this->hasAttribute('build_orders_exists')) {
            return (bool) $this->getAttribute('build_orders_exists');
        }

        return $this->buildOrders()->exists();
    }

    /**
     * Lines (and yield) can change while the formula is a draft, or active but not yet used by a build order.
     */
    public function isEditable(): bool
    {
        return match ($this->status) {
            FormulaStatus::Draft => true,
            FormulaStatus::Active => ! $this->isUsed(),
            FormulaStatus::Archived => false,
        };
    }

    /** @return BelongsTo<Innovation, $this> */
    public function innovation(): BelongsTo
    {
        return $this->belongsTo(Innovation::class)->withTrashed();
    }

    /** @return HasMany<InnovationItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(InnovationItem::class);
    }

    /** @return HasMany<BuildOrder, $this> */
    public function buildOrders(): HasMany
    {
        return $this->hasMany(BuildOrder::class);
    }
}
