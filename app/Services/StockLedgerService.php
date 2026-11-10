<?php

namespace App\Services;

use App\Enums\StockTransactionType;
use App\Exceptions\StockPostingException;
use App\Models\InventoryBalance;
use App\Models\InventoryItem;
use App\Models\Location;
use App\Models\StockLedgerEntry;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * The only way stock changes. Each posting locks the item's balance at the location,
 * refuses to take it below the reserved quantity, writes one immutable ledger entry,
 * and refreshes the item's total quantity on hand — all in one database transaction.
 */
class StockLedgerService
{
    /**
     * Post a single stock movement.
     *
     * @param  array{posted_at?: Carbon|string|null, reference_number?: string|null, notes?: string|null, production_run_id?: int|null, build_order_id?: int|null, reversal_of_entry_id?: int|null}  $context
     *
     * @throws StockPostingException when the movement would leave less stock than is reserved
     */
    public function post(
        InventoryItem $item,
        Location $location,
        StockTransactionType $type,
        float $quantityIn,
        float $quantityOut,
        ?float $unitCost = null,
        ?User $actor = null,
        array $context = [],
    ): StockLedgerEntry {
        if (($quantityIn <= 0 && $quantityOut <= 0) || ($quantityIn > 0 && $quantityOut > 0)) {
            throw new InvalidArgumentException('A stock posting must move a positive quantity either in or out.');
        }

        return DB::transaction(function () use ($item, $location, $type, $quantityIn, $quantityOut, $unitCost, $actor, $context): StockLedgerEntry {
            $balance = $this->lockedBalance($item, $location);
            $newQuantity = round((float) $balance->on_hand_quantity + $quantityIn - $quantityOut, 3);

            if ($newQuantity < (float) $balance->reserved_quantity) {
                throw new StockPostingException(sprintf(
                    'Not enough stock of %s at %s: %s on hand, %s requested.',
                    $item->name,
                    $location->name,
                    rtrim(rtrim(number_format((float) $balance->on_hand_quantity - (float) $balance->reserved_quantity, 3, '.', ''), '0'), '.'),
                    rtrim(rtrim(number_format($quantityOut, 3, '.', ''), '0'), '.'),
                ));
            }

            $resolvedUnitCost = $unitCost ?? (float) ($item->unit_cost ?? 0);

            $balance->update(['on_hand_quantity' => $newQuantity]);

            $entry = StockLedgerEntry::create([
                'entry_number' => 'LED-'.Str::upper((string) Str::ulid()),
                'posted_at' => $context['posted_at'] ?? now(),
                'transaction_type' => $type,
                'inventory_item_id' => $item->id,
                'location_id' => $location->id,
                'quantity_in' => $quantityIn,
                'quantity_out' => $quantityOut,
                'unit_cost' => $resolvedUnitCost,
                'total_value' => round(($quantityIn - $quantityOut) * $resolvedUnitCost, 2),
                'balance_after' => $newQuantity,
                'production_run_id' => $context['production_run_id'] ?? null,
                'build_order_id' => $context['build_order_id'] ?? null,
                'reversal_of_entry_id' => $context['reversal_of_entry_id'] ?? null,
                'reference_number' => $context['reference_number'] ?? null,
                'created_by' => $actor?->id,
                'notes' => $context['notes'] ?? null,
            ]);

            $this->refreshItemTotal($item);

            return $entry;
        });
    }

    /**
     * Post the inverse of an entry at the same location and cost. Each entry can be reversed once;
     * reversals and production postings cannot be reversed.
     *
     * @throws StockPostingException
     */
    public function reverse(StockLedgerEntry $entry, User $actor, string $reason): StockLedgerEntry
    {
        return DB::transaction(function () use ($entry, $actor, $reason): StockLedgerEntry {
            $entry = StockLedgerEntry::query()->lockForUpdate()->findOrFail($entry->id);

            if (! $entry->isReversible()) {
                throw new StockPostingException('This entry cannot be reversed: it is a reversal, comes from a production run, or was already reversed.');
            }

            return $this->post(
                $entry->inventoryItem,
                $entry->location,
                StockTransactionType::Reversal,
                (float) $entry->quantity_out,
                (float) $entry->quantity_in,
                (float) $entry->unit_cost,
                $actor,
                [
                    'reversal_of_entry_id' => $entry->id,
                    'reference_number' => $entry->entry_number,
                    'notes' => $reason,
                ],
            );
        });
    }

    /**
     * The item's balance row at the location, created at zero if missing, locked for this transaction.
     */
    private function lockedBalance(InventoryItem $item, Location $location): InventoryBalance
    {
        InventoryBalance::query()->firstOrCreate(
            ['inventory_item_id' => $item->id, 'location_id' => $location->id],
            ['on_hand_quantity' => 0, 'reserved_quantity' => 0],
        );

        return InventoryBalance::query()
            ->where('inventory_item_id', $item->id)
            ->where('location_id', $location->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * Keep the item's cached total in step with its balances across all locations.
     */
    private function refreshItemTotal(InventoryItem $item): void
    {
        $total = (float) InventoryBalance::query()->where('inventory_item_id', $item->id)->sum('on_hand_quantity');

        $item->forceFill(['quantity_on_hand' => round($total, 3)])->saveQuietly();
    }
}
