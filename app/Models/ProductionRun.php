<?php

namespace App\Models;

use Database\Factories\ProductionRunFactory;
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
 * A completed, immutable production run: units built against a build order and the parts they consumed.
 *
 * @property int $id
 * @property string $run_number
 * @property int $build_order_id
 * @property int $innovation_id
 * @property int $innovation_formula_id
 * @property int $location_id
 * @property string $output_quantity
 * @property string $total_input_cost
 * @property string $unit_production_cost
 * @property string|null $markup_percentage
 * @property string|null $suggested_selling_price
 * @property int|null $completed_by
 * @property Carbon $completed_at
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['run_number', 'build_order_id', 'innovation_id', 'innovation_formula_id', 'location_id', 'output_quantity', 'total_input_cost', 'unit_production_cost', 'markup_percentage', 'suggested_selling_price', 'completed_by', 'completed_at', 'notes'])]
class ProductionRun extends Model
{
    /** @use HasFactory<ProductionRunFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'output_quantity' => 'decimal:3',
            'total_input_cost' => 'decimal:2',
            'unit_production_cost' => 'decimal:2',
            'markup_percentage' => 'decimal:2',
            'suggested_selling_price' => 'decimal:2',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Runs matching a term in their number, build order number, or innovation name/code.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function search(Builder $query, string $term): void
    {
        $query->where(fn (Builder $query) => $query
            ->whereLike('run_number', "%{$term}%")
            ->orWhereHas('buildOrder', fn (Builder $query) => $query->whereLike('order_number', "%{$term}%"))
            ->orWhereHas('innovation', fn (Builder $query) => $query->withTrashed()->search($term)));
    }

    /**
     * The next sequential run number for the given year, e.g. PR-2026-0001.
     */
    public static function nextNumber(?int $year = null): string
    {
        $prefix = sprintf('PR-%d-', $year ?? now()->year);

        $highest = static::query()
            ->whereLike('run_number', $prefix.'%')
            ->pluck('run_number')
            ->map(fn (string $number): int => (int) Str::after($number, $prefix))
            ->max() ?? 0;

        return $prefix.str_pad((string) ($highest + 1), 4, '0', STR_PAD_LEFT);
    }

    /** @return BelongsTo<BuildOrder, $this> */
    public function buildOrder(): BelongsTo
    {
        return $this->belongsTo(BuildOrder::class);
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
    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /** @return HasMany<ProductionRunInput, $this> */
    public function inputs(): HasMany
    {
        return $this->hasMany(ProductionRunInput::class);
    }

    /** @return HasMany<StockLedgerEntry, $this> */
    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(StockLedgerEntry::class);
    }
}
