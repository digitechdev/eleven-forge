<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\InventoryItemPriceChange;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Saves inventory items and keeps an audit trail of their reference unit cost.
 * A cost change never touches stock quantities.
 */
class InventoryItemPriceService
{
    /**
     * Create an item and record its initial unit cost, when it has one.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes, ?User $actor = null): InventoryItem
    {
        return DB::transaction(function () use ($attributes, $actor): InventoryItem {
            $item = InventoryItem::create($attributes);

            if ($item->unit_cost !== null) {
                $this->record($item, null, $this->normalize($item->unit_cost), $actor, null);
            }

            return $item;
        });
    }

    /**
     * Update an item; when the unit cost changes, record the previous and new cost.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(InventoryItem $item, array $attributes, ?User $actor = null, ?string $reason = null): InventoryItem
    {
        return DB::transaction(function () use ($item, $attributes, $actor, $reason): InventoryItem {
            $previousCost = $this->normalize($item->unit_cost);
            $newCost = array_key_exists('unit_cost', $attributes) ? $this->normalize($attributes['unit_cost']) : $previousCost;

            $item->update($attributes);

            if ($previousCost !== $newCost) {
                $this->record($item, $previousCost, $newCost, $actor, $reason);
            }

            return $item;
        });
    }

    private function record(InventoryItem $item, ?float $previousCost, ?float $newCost, ?User $actor, ?string $reason): InventoryItemPriceChange
    {
        return $item->priceChanges()->create([
            'previous_unit_cost' => $previousCost,
            'new_unit_cost' => $newCost,
            'changed_by' => $actor?->id,
            'reason' => filled($reason) ? $reason : null,
            'effective_at' => now(),
        ]);
    }

    /**
     * A cost rounded to the stored two decimals, so "450.5" and "450.50" compare equal.
     */
    private function normalize(mixed $cost): ?float
    {
        return $cost === null || $cost === '' ? null : round((float) $cost, 2);
    }
}
