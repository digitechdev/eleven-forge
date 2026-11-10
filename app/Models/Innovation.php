<?php

namespace App\Models;

use App\Enums\FormulaStatus;
use App\Enums\InnovationStatus;
use App\Enums\InnovationType;
use App\Enums\InnovationVisibility;
use Database\Factories\InnovationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $innovation_code
 * @property int $category_id
 * @property string $name
 * @property string $slug
 * @property InnovationType $type
 * @property string|null $tagline
 * @property string|null $short_description
 * @property string|null $description
 * @property string|null $problem_statement
 * @property string|null $solution_description
 * @property list<string>|null $key_features
 * @property list<string>|null $benefits
 * @property InnovationStatus $status
 * @property InnovationVisibility $visibility
 * @property Carbon|null $developed_at
 * @property bool $is_featured
 * @property string|null $markup_percentage
 * @property string|null $estimated_unit_cost
 * @property string|null $suggested_selling_price
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['innovation_code', 'category_id', 'name', 'slug', 'type', 'tagline', 'short_description', 'description', 'problem_statement', 'solution_description', 'key_features', 'benefits', 'status', 'visibility', 'developed_at', 'is_featured', 'markup_percentage', 'estimated_unit_cost', 'suggested_selling_price', 'created_by', 'updated_by'])]
class Innovation extends Model
{
    /** @use HasFactory<InnovationFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['type' => InnovationType::class, 'status' => InnovationStatus::class, 'visibility' => InnovationVisibility::class, 'key_features' => 'array', 'benefits' => 'array', 'developed_at' => 'date', 'is_featured' => 'boolean', 'markup_percentage' => 'decimal:2', 'estimated_unit_cost' => 'decimal:2', 'suggested_selling_price' => 'decimal:2'];
    }

    /** @return BelongsTo<InnovationCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(InnovationCategory::class, 'category_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** @return HasMany<InnovationMedia, $this> */
    public function media(): HasMany
    {
        return $this->hasMany(InnovationMedia::class);
    }

    /** @return HasMany<InnovationItem, $this> */
    public function innovationItems(): HasMany
    {
        return $this->hasMany(InnovationItem::class);
    }

    /** @return HasMany<InnovationFormula, $this> */
    public function formulas(): HasMany
    {
        return $this->hasMany(InnovationFormula::class);
    }

    /** @return HasOne<InnovationFormula, $this> */
    public function activeFormula(): HasOne
    {
        return $this->hasOne(InnovationFormula::class)->where('status', FormulaStatus::Active);
    }

    /** @return HasMany<BuildOrder, $this> */
    public function buildOrders(): HasMany
    {
        return $this->hasMany(BuildOrder::class);
    }

    /** @return HasMany<ProductionRun, $this> */
    public function productionRuns(): HasMany
    {
        return $this->hasMany(ProductionRun::class);
    }

    /** @return HasMany<InnovationTechnology, $this> */
    public function innovationTechnologies(): HasMany
    {
        return $this->hasMany(InnovationTechnology::class);
    }

    /** @return BelongsToMany<InventoryItem, $this> */
    public function inventoryItems(): BelongsToMany
    {
        return $this->belongsToMany(InventoryItem::class, 'innovation_items')->withPivot(['id', 'unit_id', 'quantity_required', 'usage_description', 'specification_notes', 'is_optional', 'sort_order'])->withTimestamps();
    }

    /** @return BelongsToMany<Technology, $this> */
    public function technologies(): BelongsToMany
    {
        return $this->belongsToMany(Technology::class, 'innovation_technologies')->withPivot(['id', 'version', 'purpose'])->withTimestamps();
    }

    /**
     * Innovations visible on the public showcase.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('visibility', InnovationVisibility::Public);
    }

    /**
     * Innovations matching a term in name, code, or tagline.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function search(Builder $query, string $term): void
    {
        $query->where(fn (Builder $query) => $query
            ->whereLike('name', "%{$term}%")
            ->orWhereLike('innovation_code', "%{$term}%")
            ->orWhereLike('tagline', "%{$term}%"));
    }

    /**
     * The next sequential innovation code for the given year, e.g. INV-2026-0001.
     * Soft-deleted innovations are included so codes are never reused.
     */
    public static function nextCode(?int $year = null): string
    {
        $prefix = sprintf('INV-%d-', $year ?? now()->year);

        $highest = static::withTrashed()
            ->whereLike('innovation_code', $prefix.'%')
            ->pluck('innovation_code')
            ->map(fn (string $code): int => (int) Str::after($code, $prefix))
            ->max() ?? 0;

        do {
            $code = $prefix.str_pad((string) ++$highest, 4, '0', STR_PAD_LEFT);
        } while (static::withTrashed()->where('innovation_code', $code)->exists());

        return $code;
    }

    /**
     * A slug derived from the given name that no innovation (including soft-deleted ones) uses yet.
     */
    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'innovation';
        $slug = $base;
        $suffix = 2;

        while (static::withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId, fn (Builder $query, int $id) => $query->whereKeyNot($id))
            ->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
