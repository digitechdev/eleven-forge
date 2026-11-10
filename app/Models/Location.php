<?php

namespace App\Models;

use Database\Factories\LocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A place where stock is kept: a warehouse, room, or branch.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['code', 'name', 'description', 'is_active'])]
class Location extends Model
{
    /** @use HasFactory<LocationFactory> */
    use HasFactory;

    /**
     * Code of the location existing stock was moved to when per-location stock was introduced.
     */
    public const string MAIN_CODE = 'MAIN';

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @return HasMany<InventoryBalance, $this> */
    public function balances(): HasMany
    {
        return $this->hasMany(InventoryBalance::class);
    }

    /** @return HasMany<StockLedgerEntry, $this> */
    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(StockLedgerEntry::class);
    }

    /**
     * Whether this is the default location, which can never be deleted.
     */
    public function isMain(): bool
    {
        return $this->code === self::MAIN_CODE;
    }

    /**
     * Whether stock or history references this location, so it must be deactivated rather than deleted.
     */
    public function isInUse(): bool
    {
        return $this->balances()->exists() || $this->ledgerEntries()->exists();
    }

    /**
     * Locations that can receive new postings.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
