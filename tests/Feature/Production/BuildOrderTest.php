<?php

use App\Enums\BuildOrderStatus;
use App\Models\BuildOrder;
use App\Models\Innovation;
use App\Models\InnovationFormula;
use App\Models\InnovationItem;
use App\Models\Location;
use App\Models\ProductionRun;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->manager = User::factory()->manager()->create();
    $this->formula = InnovationFormula::factory()->active()->create();
    InnovationItem::factory()->forFormula($this->formula)->create();
    $this->location = Location::factory()->create();
});

test('a build order uses the innovation active formula and gets a sequential number', function () {
    $this->travelTo('2026-11-10');
    BuildOrder::factory()->create(['order_number' => 'BO-2026-0007']);

    $this->actingAs($this->manager)
        ->post(route('production.build-orders.store'), [
            'innovation_id' => $this->formula->innovation_id,
            'location_id' => $this->location->id,
            'planned_quantity' => 3,
            'notes' => 'Pilot batch',
            'plan' => true,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('production.build-orders.index'));

    expect(BuildOrder::query()->where('order_number', 'BO-2026-0008')->sole())
        ->innovation_formula_id->toBe($this->formula->id)
        ->status->toBe(BuildOrderStatus::Planned)
        ->planned_quantity->toBe('3.000')
        ->requested_by->toBe($this->manager->id);
});

test('a build order needs an innovation with an active formula, an active location, and a positive quantity', function () {
    $draftOnly = InnovationFormula::factory()->create();
    $inactive = Location::factory()->create(['is_active' => false]);

    $this->actingAs($this->manager)
        ->post(route('production.build-orders.store'), [
            'innovation_id' => $draftOnly->innovation_id,
            'location_id' => $inactive->id,
            'planned_quantity' => 0,
            'plan' => false,
        ])
        ->assertSessionHasErrors([
            'innovation_id' => 'Choose an innovation with an active formula.',
            'location_id' => 'Choose an active location.',
            'planned_quantity',
        ]);

    expect(BuildOrder::query()->exists())->toBeFalse();
});

test('orders move draft to planned to in progress and record when they started', function () {
    $order = BuildOrder::factory()->forFormula($this->formula)->status(BuildOrderStatus::Draft)->create();

    $this->actingAs($this->manager)->patch(route('production.build-orders.plan', $order))->assertRedirect();
    expect($order->fresh()->status)->toBe(BuildOrderStatus::Planned);

    $this->patch(route('production.build-orders.start', $order))->assertRedirect();
    expect($order->fresh())
        ->status->toBe(BuildOrderStatus::InProgress)
        ->started_at->not->toBeNull();

    $this->patch(route('production.build-orders.cancel', $order))->assertRedirect();
    expect($order->fresh()->status)->toBe(BuildOrderStatus::Cancelled);
});

test('forbidden status transitions are refused', function (BuildOrderStatus $from, string $action) {
    $order = BuildOrder::factory()->forFormula($this->formula)->status($from)->create();

    $this->actingAs($this->manager)
        ->patch(route("production.build-orders.{$action}", $order))
        ->assertForbidden();

    expect($order->fresh()->status)->toBe($from);
})->with([
    'start a draft' => [BuildOrderStatus::Draft, 'start'],
    'plan an in-progress order' => [BuildOrderStatus::InProgress, 'plan'],
    'cancel a completed order' => [BuildOrderStatus::Completed, 'cancel'],
    'restart a cancelled order' => [BuildOrderStatus::Cancelled, 'start'],
    'replan a completed order' => [BuildOrderStatus::Completed, 'plan'],
]);

test('viewers can list build orders but not create or change them', function () {
    $viewer = User::factory()->viewer()->create();
    $order = BuildOrder::factory()->forFormula($this->formula)->status(BuildOrderStatus::Draft)->create();

    $this->actingAs($viewer)
        ->get(route('production.build-orders.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('production/build-orders/Index')
            ->has('orders.data', 1)
            ->where('orders.data.0.can.plan', false)
            ->where('can.create', false));

    $this->post(route('production.build-orders.store'), [
        'innovation_id' => $this->formula->innovation_id,
        'location_id' => $this->location->id,
        'planned_quantity' => 1,
        'plan' => false,
    ])->assertForbidden();
    $this->patch(route('production.build-orders.plan', $order))->assertForbidden();
});

test('build orders can be filtered by status and innovation, and only buildable innovations are offered', function () {
    $planned = BuildOrder::factory()->forFormula($this->formula)->create();
    BuildOrder::factory()->forFormula($this->formula)->status(BuildOrderStatus::Cancelled)->create();
    BuildOrder::factory()->create();
    Innovation::factory()->create();

    $this->actingAs($this->manager)
        ->get(route('production.build-orders.index', ['status' => 'PLANNED', 'innovation' => $this->formula->innovation_id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('orders.data', 1)
            ->where('orders.data.0.id', $planned->id)
            ->where('orders.data.0.url', route('production.build-orders.show', $planned))
            ->where('orders.data.0.completion.lines.0.quantity_required', 1)
            ->has('options.buildableInnovations', 2));
});

test('the build detail shows the order, its actions, and its production runs with their stock movements', function () {
    $order = BuildOrder::factory()->forFormula($this->formula, $this->location)->status(BuildOrderStatus::InProgress)->create(['planned_quantity' => 4]);
    $run = ProductionRun::factory()->create(['build_order_id' => $order->id, 'run_number' => 'PR-2026-0042']);
    ProductionRun::factory()->create();

    $this->actingAs($this->manager)
        ->get(route('production.build-orders.show', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('production/build-orders/Show')
            ->where('order.id', $order->id)
            ->where('order.order_number', $order->order_number)
            ->where('order.location', $this->location->name)
            ->where('order.planned_quantity', 4)
            ->where('order.can.complete', true)
            ->where('order.can.plan', false)
            ->where('order.completion.lines.0.quantity_required', 1)
            ->has('runs', 1)
            ->where('runs.0.run_number', 'PR-2026-0042')
            ->where('runs.0.url', route('production.runs.show', $run))
            ->where('runs.0.ledger_url', route('inventory.ledger.index', ['reference' => 'PR-2026-0042'])));
});

test('viewers can open a build without actions, and users without innovation access cannot', function () {
    $order = BuildOrder::factory()->forFormula($this->formula)->status(BuildOrderStatus::Planned)->create();

    $this->actingAs(User::factory()->viewer()->create())
        ->get(route('production.build-orders.show', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('order.can', ['plan' => false, 'start' => false, 'cancel' => false, 'complete' => false])
            ->where('order.completion', null));

    $this->actingAs(User::factory()->create())
        ->get(route('production.build-orders.show', $order))
        ->assertForbidden();
});
