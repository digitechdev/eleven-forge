<?php

namespace App\Models;

use Database\Factories\UnitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'symbol', 'decimal_places', 'is_active'])]
class Unit extends Model
{
    /** @use HasFactory<UnitFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'decimal_places' => 'integer'];
    }

    /** @return HasMany<InventoryItem, $this> */
    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'default_unit_id');
    }

    /** @return HasMany<InnovationItem, $this> */
    public function innovationItems(): HasMany
    {
        return $this->hasMany(InnovationItem::class);
    }
}
