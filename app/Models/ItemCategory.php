<?php

namespace App\Models;

use Database\Factories\ItemCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['parent_id', 'name', 'description', 'is_active', 'sort_order'])]
class ItemCategory extends Model
{
    /** @use HasFactory<ItemCategoryFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    /** @return BelongsTo<self, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<self, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /** @return HasMany<InventoryItem, $this> */
    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'category_id');
    }

    /**
     * Get the IDs of every category nested below this one, at any depth.
     *
     * @return list<int>
     */
    public function descendantIds(): array
    {
        $descendantIds = [];
        $parentIds = [$this->id];

        while ($parentIds !== []) {
            $parentIds = self::query()->whereIn('parent_id', $parentIds)->pluck('id')->all();
            $parentIds = array_values(array_diff($parentIds, $descendantIds));
            $descendantIds = [...$descendantIds, ...$parentIds];
        }

        return $descendantIds;
    }

    /**
     * Determine whether the category still has items (including archived ones) or child categories.
     */
    public function isInUse(): bool
    {
        return $this->children()->exists() || $this->inventoryItems()->withTrashed()->exists();
    }
}
