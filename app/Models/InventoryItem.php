<?php

namespace App\Models;

use App\InventoryItemStatus;
use App\InventoryItemType;
use Database\Factories\InventoryItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['item_code', 'category_id', 'default_unit_id', 'name', 'item_type', 'brand', 'model', 'manufacturer', 'part_number', 'description', 'specification', 'quantity_on_hand', 'minimum_stock', 'unit_cost', 'status', 'created_by'])]
class InventoryItem extends Model
{
    /** @use HasFactory<InventoryItemFactory> */
    use HasFactory, SoftDeletes;

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
}
