<?php

namespace App\Models;

use App\Enums\BuildOrderStatus;
use Database\Factories\BuildOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A plan to build units of an innovation from one formula, consuming parts at one location.
 *
 * @property int $id
 * @property string $order_number
 * @property int $innovation_id
 * @property int $innovation_formula_id
 * @property int $location_id
 * @property string $planned_quantity
 * @property string $produced_quantity
 * @property BuildOrderStatus $status
 * @property int|null $requested_by
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['order_number', 'innovation_id', 'innovation_formula_id', 'location_id', 'planned_quantity', 'produced_quantity', 'status', 'requested_by', 'started_at', 'completed_at', 'notes'])]
class BuildOrder extends Model
{
    /** @use HasFactory<BuildOrderFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'planned_quantity' => 'decimal:3',
            'produced_quantity' => 'decimal:3',
            'status' => BuildOrderStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Quantity still to be produced.
     */
    public function remainingQuantity(): float
    {
        return max(0.0, round((float) $this->planned_quantity - (float) $this->produced_quantity, 3));
    }

    /**
     * Produced share of the planned quantity, 0–100.
     */
    public function progressPercentage(): float
    {
        $planned = (float) $this->planned_quantity;

        return $planned > 0 ? round(min(100, (float) $this->produced_quantity / $planned * 100), 1) : 0.0;
    }

    /**
     * Orders matching a term in their number or innovation name/code.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function search(Builder $query, string $term): void
    {
        $query->where(fn (Builder $query) => $query
            ->whereLike('order_number', "%{$term}%")
            ->orWhereHas('innovation', fn (Builder $query) => $query->withTrashed()->search($term)));
    }

    /**
     * The next sequential order number for the given year, e.g. BO-2026-0001.
     */
    public static function nextNumber(?int $year = null): string
    {
        $prefix = sprintf('BO-%d-', $year ?? now()->year);

        $highest = static::query()
            ->whereLike('order_number', $prefix.'%')
            ->pluck('order_number')
            ->map(fn (string $number): int => (int) Str::after($number, $prefix))
            ->max() ?? 0;

        return $prefix.str_pad((string) ($highest + 1), 4, '0', STR_PAD_LEFT);
    }

    /** @return BelongsTo<Innovation, $this> */
    public function innovation(): BelongsTo
    {
        return $this->belongsTo(Innovation::class)->withTrashed();
    }

    /** @return BelongsTo<InnovationFormula, $this> */
    public function formula(): BelongsTo
    {
        return $this->belongsTo(InnovationFormula::class, 'innovation_formula_id');
    }

    /** @return BelongsTo<Location, $this> */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /** @return HasMany<ProductionRun, $this> */
    public function productionRuns(): HasMany
    {
        return $this->hasMany(ProductionRun::class);
    }

    /** @return HasMany<StockLedgerEntry, $this> */
    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(StockLedgerEntry::class);
    }
}
