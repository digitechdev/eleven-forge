<?php

namespace App\Http\Controllers\Stock;

use App\Enums\InventoryItemStatus;
use App\Enums\StockTransactionType;
use App\Models\InventoryItem;
use App\Models\Location;
use Illuminate\Database\Eloquent\Builder;

/**
 * Choices for the shared "Post transaction" dialog: active items and locations, and the manual types.
 */
trait ProvidesPostingOptions
{
    /**
     * @return array{items: array<int, array{value: int, label: string, unit_cost: float|null, unit_symbol: string, decimal_places: int}>, locations: array<int, array{value: int, label: string}>, types: list<array{value: string, label: string}>}
     */
    protected function postingOptions(): array
    {
        return [
            'items' => InventoryItem::query()
                ->where('status', InventoryItemStatus::Active)
                ->with('defaultUnit:id,symbol,decimal_places')
                ->orderBy('name')
                ->get(['id', 'item_code', 'name', 'unit_cost', 'default_unit_id'])
                ->map(fn (InventoryItem $item): array => [
                    'value' => $item->id,
                    'label' => "{$item->item_code} · {$item->name}",
                    'unit_cost' => $item->unit_cost === null ? null : (float) $item->unit_cost,
                    'unit_symbol' => $item->defaultUnit->symbol,
                    'decimal_places' => $item->defaultUnit->decimal_places,
                ])
                ->values()
                ->all(),
            'locations' => $this->locationOptions(Location::query()->active()),
            'types' => array_map(
                fn (StockTransactionType $type): array => ['value' => $type->value, 'label' => $type->label()],
                StockTransactionType::manual(),
            ),
        ];
    }

    /**
     * @param  Builder<Location>|null  $query
     * @return array<int, array{value: int, label: string}>
     */
    protected function locationOptions(?Builder $query = null): array
    {
        return ($query ?? Location::query())
            ->orderBy('name')
            ->get(['id', 'code', 'name'])
            ->map(fn (Location $location): array => ['value' => $location->id, 'label' => "{$location->name} ({$location->code})"])
            ->values()
            ->all();
    }
}
