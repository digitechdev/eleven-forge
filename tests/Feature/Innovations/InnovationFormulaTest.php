<?php

use App\Enums\BuildOrderStatus;
use App\Enums\FormulaStatus;
use App\Models\BuildOrder;
use App\Models\Innovation;
use App\Models\InnovationFormula;
use App\Models\InnovationItem;
use App\Models\InventoryItem;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->contributor = User::factory()->contributor()->create();
    $this->innovation = Innovation::factory()->for($this->contributor, 'creator')->create(['markup_percentage' => 50]);
});

test('a new version copies the source formula lines and yield into a draft', function () {
    $source = InnovationFormula::factory()->for($this->innovation)->active()->create(['yield_quantity' => 4]);
    $line = InnovationItem::factory()->forFormula($source)->create(['quantity_required' => 3, 'is_optional' => true, 'usage_description' => 'Frame']);

    $this->actingAs($this->contributor)
        ->post(route('innovations.formulas.store', $this->innovation), ['source_formula_id' => $source->id])
        ->assertSessionHasNoErrors();

    $copy = $this->innovation->formulas()->where('version', 2)->sole();

    expect($copy->status)->toBe(FormulaStatus::Draft)
        ->and((float) $copy->yield_quantity)->toBe(4.0)
        ->and($copy->items()->sole()->only(['inventory_item_id', 'unit_id', 'quantity_required', 'is_optional', 'usage_description']))
        ->toBe($line->only(['inventory_item_id', 'unit_id', 'quantity_required', 'is_optional', 'usage_description']))
        ->and($source->items()->count())->toBe(1);
});

test('activating a draft archives the previous active formula and stores its estimated cost', function () {
    $previous = InnovationFormula::factory()->for($this->innovation)->active()->create();
    $draft = InnovationFormula::factory()->for($this->innovation)->create(['yield_quantity' => 2]);
    InnovationItem::factory()->forFormula($draft)->create([
        'inventory_item_id' => InventoryItem::factory()->create(['unit_cost' => 100])->id,
        'quantity_required' => 3,
    ]);
    InnovationItem::factory()->forFormula($draft)->create([
        'inventory_item_id' => InventoryItem::factory()->create(['unit_cost' => 40])->id,
        'quantity_required' => 1,
    ]);
    InnovationItem::factory()->forFormula($draft)->create([
        'inventory_item_id' => InventoryItem::factory()->create(['unit_cost' => 999])->id,
        'quantity_required' => 1,
        'is_optional' => true,
    ]);

    $this->actingAs($this->contributor)
        ->patch(route('innovations.formulas.activate', [$this->innovation, $draft]))
        ->assertSessionHasNoErrors();

    expect($previous->fresh()->status)->toBe(FormulaStatus::Archived)
        ->and($draft->fresh())
        ->status->toBe(FormulaStatus::Active)
        ->estimated_unit_cost->toBe('170.00')
        ->and($this->innovation->fresh())
        ->estimated_unit_cost->toBe('170.00')
        ->suggested_selling_price->toBe('255.00')
        ->and($this->innovation->formulas()->where('status', FormulaStatus::Active)->count())->toBe(1);
});

test('a formula without lines cannot be activated', function () {
    $draft = InnovationFormula::factory()->for($this->innovation)->create();

    $this->actingAs($this->contributor)
        ->patch(route('innovations.formulas.activate', [$this->innovation, $draft]))
        ->assertSessionHasErrors(['formula' => 'Add at least one line before activating this formula.']);

    expect($draft->fresh()->status)->toBe(FormulaStatus::Draft);
});

test('only drafts can be activated', function () {
    $archived = InnovationFormula::factory()->for($this->innovation)->archived()->create();
    InnovationItem::factory()->forFormula($archived)->create();

    $this->actingAs($this->contributor)
        ->patch(route('innovations.formulas.activate', [$this->innovation, $archived]))
        ->assertForbidden();
});

test('lines of an active formula stay editable until a build order uses it', function () {
    $active = InnovationFormula::factory()->for($this->innovation)->active()->create();
    $line = InnovationItem::factory()->forFormula($active)->create(['quantity_required' => 1]);
    $payload = ['inventory_item_id' => $line->inventory_item_id, 'quantity_required' => 2, 'is_optional' => false];

    $this->actingAs($this->contributor)
        ->put(route('innovations.items.update', [$this->innovation, $line]), $payload)
        ->assertSessionHasNoErrors();

    BuildOrder::factory()->forFormula($active)->status(BuildOrderStatus::Draft)->create();

    $this->put(route('innovations.items.update', [$this->innovation, $line]), [...$payload, 'quantity_required' => 5])
        ->assertForbidden();
    $this->delete(route('innovations.items.destroy', [$this->innovation, $line]))->assertForbidden();
    $this->post(route('innovations.items.store', $this->innovation), [
        'innovation_formula_id' => $active->id,
        'inventory_item_id' => InventoryItem::factory()->create()->id,
        'quantity_required' => 1,
        'is_optional' => false,
    ])->assertForbidden();

    expect($line->fresh()->quantity_required)->toBe('2.000');
});

test('archived formulas are read-only', function () {
    $archived = InnovationFormula::factory()->for($this->innovation)->archived()->create();
    $line = InnovationItem::factory()->forFormula($archived)->create();

    $this->actingAs($this->contributor)
        ->delete(route('innovations.items.destroy', [$this->innovation, $line]))
        ->assertForbidden();
    $this->put(route('innovations.formulas.update', [$this->innovation, $archived]), ['yield_quantity' => 3])
        ->assertForbidden();

    expect($line->fresh())->not->toBeNull();
});

test('a draft formula yield must be positive', function () {
    $draft = InnovationFormula::factory()->for($this->innovation)->create();

    $this->actingAs($this->contributor)
        ->put(route('innovations.formulas.update', [$this->innovation, $draft]), ['yield_quantity' => 0])
        ->assertSessionHasErrors('yield_quantity');

    $this->put(route('innovations.formulas.update', [$this->innovation, $draft]), ['yield_quantity' => 2.5, 'notes' => 'Batch of 2.5'])
        ->assertSessionHasNoErrors();

    expect($draft->fresh()->yield_quantity)->toBe('2.500');
});

test('formulas of another innovation cannot be reached through this one', function () {
    $foreign = InnovationFormula::factory()->create();

    $this->actingAs($this->contributor)
        ->patch(route('innovations.formulas.activate', [$this->innovation, $foreign]))
        ->assertNotFound();
});

test('users who cannot update the innovation cannot manage its formulas', function () {
    $draft = InnovationFormula::factory()->for($this->innovation)->create();
    InnovationItem::factory()->forFormula($draft)->create();

    $this->actingAs(User::factory()->viewer()->create())
        ->post(route('innovations.formulas.store', $this->innovation))
        ->assertForbidden();
    $this->patch(route('innovations.formulas.activate', [$this->innovation, $draft]))->assertForbidden();
});

test('the show page lists formula versions and defaults to the active one', function () {
    InnovationFormula::factory()->for($this->innovation)->archived()->create();
    $active = InnovationFormula::factory()->for($this->innovation)->active()->create();
    $draft = InnovationFormula::factory()->for($this->innovation)->create();

    $this->actingAs($this->contributor)
        ->get(route('innovations.show', $this->innovation))
        ->assertInertia(fn (Assert $page) => $page
            ->has('formulas', 3)
            ->where('selectedFormulaId', $active->id)
            ->where('formulas.1.can.update', true)
            ->where('formulas.0.can.update', false)
            ->where('formulas.2.can.activate', true));

    $this->get(route('innovations.show', [$this->innovation, 'formula' => $draft->id]))
        ->assertInertia(fn (Assert $page) => $page->where('selectedFormulaId', $draft->id));
});

test('changing the markup recalculates the suggested selling price', function () {
    $this->innovation->update(['estimated_unit_cost' => 200]);

    $this->actingAs($this->contributor)
        ->put(route('innovations.update', $this->innovation), [
            ...$this->innovation->only(['name', 'category_id', 'innovation_code', 'slug']),
            'type' => $this->innovation->type->value,
            'status' => $this->innovation->status->value,
            'visibility' => $this->innovation->visibility->value,
            'is_featured' => false,
            'markup_percentage' => 25,
        ])
        ->assertSessionHasNoErrors();

    expect($this->innovation->fresh())
        ->markup_percentage->toBe('25.00')
        ->suggested_selling_price->toBe('250.00');
});
