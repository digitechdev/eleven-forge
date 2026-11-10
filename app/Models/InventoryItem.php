<?php

namespace App\Models;

use App\Enums\InventoryItemStatus;
use App\Enums\InventoryItemType;
use Database\Factories\InventoryItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $item_code
 * @property int $category_id
 * @property int $default_unit_id
 * @property string $name
 * @property InventoryItemType $item_type
 * @property string|null $brand
 * @property string|null $model
 * @property string|null $manufacturer
 * @property string|null $part_number
 * @property string|null $description
 * @property string|null $specification
 * @property string $quantity_on_hand
 * @property string $minimum_stock
 * @property string|null $unit_cost
 * @property InventoryItemStatus $status
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['item_code', 'category_id', 'default_unit_id', 'name', 'item_type', 'brand', 'model', 'manufacturer', 'part_number', 'description', 'specification', 'quantity_on_hand', 'minimum_stock', 'unit_cost', 'status', 'created_by'])]
class InventoryItem extends Model
{
    /** @use HasFactory<InventoryItemFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Prefix used for automatically generated item codes.
     */
    public const string CODE_PREFIX = 'ITM-';

    /**
     * Fill in a generated item code when none was given.
     */
    protected static function booted(): void
    {
        static::creating(function (InventoryItem $item): void {
            if (blank($item->item_code)) {
                $item->item_code = static::nextItemCode();
            }
        });
    }

    /**
     * The next free sequential item code, e.g. "ITM-00042" (soft-deleted items keep their codes).
     */
    public static function nextItemCode(): string
    {
        $highest = static::withTrashed()
            ->whereLike('item_code', static::CODE_PREFIX.'%')
            ->pluck('item_code')
            ->map(fn (string $code): int => (int) substr($code, strlen(static::CODE_PREFIX)))
            ->max() ?? 0;

        return static::CODE_PREFIX.str_pad((string) ($highest + 1), 5, '0', STR_PAD_LEFT);
    }

    /**
     * Whether stock has reached or dropped below a positive minimum.
     */
    public function isLowStock(): bool
    {
        return (float) $this->minimum_stock > 0 && (float) $this->quantity_on_hand <= (float) $this->minimum_stock;
    }

    protected function casts(): array
    {
        return ['item_type' => InventoryItemType::class, 'status' => InventoryItemStatus::class, 'quantity_on_hand' => 'decimal:3', 'minimum_stock' => 'decimal:3', 'unit_cost' => 'decimal:2'];
    }

    /** @return BelongsTo<ItemCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }

    /** @return BelongsTo<Unit, $this> */
    public function defaultUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'default_unit_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<InnovationItem, $this> */
    public function innovationItems(): HasMany
    {
        return $this->hasMany(InnovationItem::class);
    }

    /** @return BelongsToMany<Innovation, $this> */
    public function innovations(): BelongsToMany
    {
        return $this->belongsToMany(Innovation::class, 'innovation_items')->withPivot(['id', 'unit_id', 'quantity_required', 'usage_description', 'specification_notes', 'is_optional', 'sort_order'])->withTimestamps();
    }

    /**
     * Items whose stock has reached or dropped below a positive minimum.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function lowStock(Builder $query): void
    {
        $query->where('minimum_stock', '>', 0)->whereColumn('quantity_on_hand', '<=', 'minimum_stock');
    }

    /**
     * Items matching a term in code, name, brand, model, or part number.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function search(Builder $query, string $term): void
    {
        $query->where(fn (Builder $query) => $query
            ->whereLike('item_code', "%{$term}%")
            ->orWhereLike('name', "%{$term}%")
            ->orWhereLike('brand', "%{$term}%")
            ->orWhereLike('model', "%{$term}%")
            ->orWhereLike('part_number', "%{$term}%"));
    }

    /**
     * Items in a category or any of its direct child categories.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function inCategory(Builder $query, int $categoryId): void
    {
        $query->where(fn (Builder $query) => $query
            ->where('category_id', $categoryId)
            ->orWhereIn('category_id', ItemCategory::query()->select('id')->where('parent_id', $categoryId)));
    }
}
