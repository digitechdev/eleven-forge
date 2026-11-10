<?php

use App\Enums\StockTransactionType;
use App\Exceptions\StockPostingException;
use App\Models\InventoryBalance;
use App\Models\InventoryItem;
use App\Models\Location;
use App\Models\StockLedgerEntry;
use App\Models\User;
use App\Services\StockLedgerService;

beforeEach(function () {
    $this->ledger = app(StockLedgerService::class);
    $this->item = InventoryItem::factory()->create(['unit_cost' => 50]);
    $this->warehouse = Location::factory()->create();
    $this->workshop = Location::factory()->create();
    $this->actor = User::factory()->create();
});

test('postings update the location balance, the item total, and record the resulting balance', function () {
    $this->ledger->post($this->item, $this->warehouse, StockTransactionType::Receive, 10, 0, 40, $this->actor);
    $this->ledger->post($this->item, $this->workshop, StockTransactionType::OpeningBalance, 5, 0, null, $this->actor);
    $issue = $this->ledger->post($this->item, $this->warehouse, StockTransactionType::Issue, 0, 3, null, $this->actor);

    expect((float) $issue->balance_after)->toBe(7.0)
        ->and((float) $issue->total_value)->toBe(-150.0)
        ->and((float) InventoryBalance::query()->where('location_id', $this->warehouse->id)->value('on_hand_quantity'))->toBe(7.0)
        ->and((float) $this->item->fresh()->quantity_on_hand)->toBe(12.0);
});

test('stock cannot go below what is on hand and a refused posting changes nothing', function () {
    $this->ledger->post($this->item, $this->warehouse, StockTransactionType::Receive, 2, 0);

    expect(fn () => $this->ledger->post($this->item, $this->warehouse, StockTransactionType::Issue, 0, 5))
        ->toThrow(StockPostingException::class);

    expect(StockLedgerEntry::query()->count())->toBe(1)
        ->and((float) $this->item->fresh()->quantity_on_hand)->toBe(2.0);
});

test('an entry can be reversed exactly once and the reversal cannot itself be reversed', function () {
    $receipt = $this->ledger->post($this->item, $this->warehouse, StockTransactionType::Receive, 8, 0, 25);

    $reversal = $this->ledger->reverse($receipt, $this->actor, 'Wrong item received');

    expect($reversal->transaction_type)->toBe(StockTransactionType::Reversal)
        ->and((float) $reversal->quantity_out)->toBe(8.0)
        ->and((float) $reversal->unit_cost)->toBe(25.0)
        ->and($reversal->reversal_of_entry_id)->toBe($receipt->id)
        ->and((float) $this->item->fresh()->quantity_on_hand)->toBe(0.0);

    expect(fn () => $this->ledger->reverse($receipt, $this->actor, 'Again'))->toThrow(StockPostingException::class);
    expect(fn () => $this->ledger->reverse($reversal, $this->actor, 'Undo'))->toThrow(StockPostingException::class);
});

test('a posting must move stock in one direction only', function () {
    expect(fn () => $this->ledger->post($this->item, $this->warehouse, StockTransactionType::Adjustment, 1, 1))
        ->toThrow(InvalidArgumentException::class);
});
