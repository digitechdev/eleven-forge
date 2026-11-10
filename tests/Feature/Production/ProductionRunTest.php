<?php

use App\Enums\BuildOrderStatus;
use App\Enums\StockTransactionType;
use App\Models\BuildOrder;
use App\Models\Innovation;
use App\Models\InnovationFormula;
use App\Models\InnovationItem;
use App\Models\InventoryBalance;
use App\Models\InventoryItem;
use App\Models\Location;
use App\Models\ProductionRun;
use App\Models\StockLedgerEntry;
use App\Models\User;
use App\Services\StockLedgerService;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->manager = User::factory()->manager()->create();
    $this->location = Location::factory()->create();
    $this->innovation = Innovation::factory()->create(['markup_percentage' => 20]);
    $this->formula = InnovationFormula::factory()->for($this->innovation)->active()->create(['yield_quantity' => 2]);

    $this->board = InventoryItem::factory()->create(['unit_cost' => 150]);
    $this->screw = InventoryItem::factory()->create(['unit_cost' => 0.5]);
    $this->sticker = InventoryItem::factory()->create(['unit_cost' => 10]);

    InnovationItem::factory()->forFormula($this->formula)->create(['inventory_item_id' => $this->board->id, 'quantity_required' => 1]);
    InnovationItem::factory()->forFormula($this->formula)->create(['inventory_item_id' => $this->screw->id, 'quantity_required' => 3]);
    InnovationItem::factory()->forFormula($this->formula)->create(['inventory_item_id' => $this->sticker->id, 'quantity_required' => 1, 'is_optional' => true]);

    $ledger = app(StockLedgerService::class);
    $ledger->post($this->board, $this->location, StockTransactionType::Receive, 10, 0);
    $ledger->post($this->screw, $this->location, StockTransactionType::Receive, 100, 0);

    $this->order = BuildOrder::factory()->forFormula($this->formula, $this->location)->create(['planned_quantity' => 10]);
});

function onHand(InventoryItem $item, Location $location): float
{
    return (float) InventoryBalance::query()->where('inventory_item_id', $item->id)->where('location_id', $location->id)->value('on_hand_quantity');
}

test('a run consumes the required non-optional parts and records their cost', function () {
    $this->travelTo('2026-11-10 09:00');

    $this->actingAs($this->manager)
        ->post(route('production.build-orders.runs.store', $this->order), ['output_quantity' => 3, 'notes' => 'First batch'])
        ->assertSessionHasNoErrors();

    $run = ProductionRun::query()->sole();

    expect($run)
        ->run_number->toBe('PR-2026-0001')
        ->output_quantity->toBe('3.000')
        ->total_input_cost->toBe('227.25')
        ->unit_production_cost->toBe('75.75')
        ->markup_percentage->toBe('20.00')
        ->suggested_selling_price->toBe('90.90')
        ->completed_by->toBe($this->manager->id);

    expect($run->inputs()->orderBy('inventory_item_id')->get()->map->only(['inventory_item_id', 'consumed_quantity', 'unit_cost', 'line_cost'])->all())->toBe([
        ['inventory_item_id' => $this->board->id, 'consumed_quantity' => '1.500', 'unit_cost' => '150.00', 'line_cost' => '225.00'],
        ['inventory_item_id' => $this->screw->id, 'consumed_quantity' => '4.500', 'unit_cost' => '0.50', 'line_cost' => '2.25'],
    ]);

    $entries = StockLedgerEntry::query()->where('transaction_type', StockTransactionType::BuildConsumption)->get();

    expect($entries)->toHaveCount(2)
        ->and($entries->pluck('production_run_id')->unique()->all())->toBe([$run->id])
        ->and($entries->pluck('build_order_id')->unique()->all())->toBe([$this->order->id])
        ->and($entries->pluck('reference_number')->unique()->all())->toBe(['PR-2026-0001'])
        ->and(onHand($this->board, $this->location))->toBe(8.5)
        ->and(onHand($this->screw, $this->location))->toBe(95.5)
        ->and(StockLedgerEntry::query()->where('inventory_item_id', $this->sticker->id)->exists())->toBeFalse();

    expect($this->order->fresh())
        ->produced_quantity->toBe('3.000')
        ->status->toBe(BuildOrderStatus::InProgress)
        ->started_at->not->toBeNull()
        ->completed_at->toBeNull()
        ->and($this->innovation->fresh())
        ->estimated_unit_cost->toBe('75.75')
        ->suggested_selling_price->toBe('90.90');
});

test('producing the remaining quantity completes the order', function () {
    $this->order->update(['produced_quantity' => 8, 'status' => BuildOrderStatus::InProgress]);

    $this->actingAs($this->manager)
        ->post(route('production.build-orders.runs.store', $this->order), ['output_quantity' => 2])
        ->assertRedirect(route('production.runs.show', ProductionRun::query()->sole()));

    expect($this->order->fresh())
        ->status->toBe(BuildOrderStatus::Completed)
        ->completed_at->not->toBeNull();

    $this->post(route('production.build-orders.runs.store', $this->order), ['output_quantity' => 1])->assertForbidden();
});

test('output above the remaining quantity is rejected', function () {
    $this->order->update(['produced_quantity' => 9]);

    $this->actingAs($this->manager)
        ->post(route('production.build-orders.runs.store', $this->order), ['output_quantity' => 1.5])
        ->assertSessionHasErrors(['output_quantity' => 'Output cannot exceed the remaining 1 units of this build.']);

    expect(ProductionRun::query()->exists())->toBeFalse();
});

test('insufficient stock for any input rolls the whole run back', function () {
    app(StockLedgerService::class)->post($this->screw, $this->location, StockTransactionType::Issue, 0, 90);

    $this->actingAs($this->manager)
        ->post(route('production.build-orders.runs.store', $this->order), ['output_quantity' => 10])
        ->assertSessionHasErrors(['output_quantity' => "Not enough stock of {$this->screw->name} at {$this->location->name}: 10 on hand, 15 requested."]);

    expect(ProductionRun::query()->exists())->toBeFalse()
        ->and(StockLedgerEntry::query()->where('transaction_type', StockTransactionType::BuildConsumption)->exists())->toBeFalse()
        ->and(onHand($this->board, $this->location))->toBe(10.0)
        ->and(onHand($this->screw, $this->location))->toBe(10.0)
        ->and($this->order->fresh()->produced_quantity)->toBe('0.000');
});

test('draft and cancelled orders cannot record production', function (BuildOrderStatus $status) {
    $this->order->update(['status' => $status]);

    $this->actingAs($this->manager)
        ->post(route('production.build-orders.runs.store', $this->order), ['output_quantity' => 1])
        ->assertForbidden();
})->with([BuildOrderStatus::Draft, BuildOrderStatus::Cancelled]);

test('viewers can read production history but cannot complete runs', function () {
    $viewer = User::factory()->viewer()->create();
    $run = ProductionRun::factory()->create();

    $this->actingAs($viewer)
        ->post(route('production.build-orders.runs.store', $this->order), ['output_quantity' => 1])
        ->assertForbidden();

    $this->get(route('production.runs.show', $run))->assertOk();
    $this->get(route('production.build-orders.show', $run->build_order_id))->assertOk();
});

test('the run detail shows each input snapshot even after the item cost changes', function () {
    $this->actingAs($this->manager)
        ->post(route('production.build-orders.runs.store', $this->order), ['output_quantity' => 2]);

    $run = ProductionRun::query()->sole();
    $this->board->update(['unit_cost' => 999]);

    $this->get(route('production.runs.show', $run))
        ->assertInertia(fn (Assert $page) => $page
            ->component('production/runs/Show')
            ->where('run.run_number', $run->run_number)
            ->where('run.build_order.url', route('production.build-orders.show', $this->order))
            ->has('inputs', 2)
            ->where('inputs.0.item_name', $this->board->name)
            ->where('inputs.0.consumed_quantity', 1)
            ->where('inputs.0.unit_cost', 150)
            ->where('inputs.0.ledger_entry_number', fn (?string $number) => $number !== null));
});

test('the old runs list redirects to the builds list', function () {
    $this->actingAs($this->manager)
        ->get(route('production.runs.index'))
        ->assertRedirect(route('production.build-orders.index'));
});
