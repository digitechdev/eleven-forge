<?php

namespace Database\Factories;

use App\Enums\StockTransactionType;
use App\Models\InventoryItem;
use App\Models\Location;
use App\Models\StockLedgerEntry;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<StockLedgerEntry>
 */
class StockLedgerEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'entry_number' => 'LED-'.Str::upper((string) Str::ulid()),
            'posted_at' => now(),
            'transaction_type' => StockTransactionType::Receive,
            'inventory_item_id' => InventoryItem::factory(),
            'location_id' => Location::factory(),
            'quantity_in' => 1,
            'quantity_out' => 0,
            'unit_cost' => 0,
            'total_value' => 0,
            'balance_after' => 1,
        ];
    }
}
