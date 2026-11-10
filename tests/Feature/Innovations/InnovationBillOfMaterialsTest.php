<?php

use App\Enums\FormulaStatus;
use App\Models\Innovation;
use App\Models\InnovationFormula;
use App\Models\InnovationItem;
use App\Models\InventoryItem;
use App\Models\Technology;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->contributor = User::factory()->contributor()->create();
    $this->innovation = Innovation::factory()->for($this->contributor, 'creator')->create();
});

test('the first bom line starts a version 1 draft formula and defaults to the item unit', function () {
    $item = InventoryItem::factory()->create(['unit_cost' => 12.5]);

    $this->actingAs($this->contributor)
        ->post(route('innovations.items.store', $this->innovation), [
            'inventory_item_id' => $item->id,
            'quantity_required' => 2,
            'is_optional' => false,
        ])
        ->assertSessionHasNoErrors();

    $formula = $this->innovation->formulas()->sole();
    $line = $formula->items()->sole();

    expect($formula->version)->toBe(1)
        ->and($formula->status)->toBe(FormulaStatus::Draft)
        ->and($line->unit_id)->toBe($item->default_unit_id)
        ->and($line->innovation_id)->toBe($this->innovation->id);

    $this->get(route('innovations.show', $this->innovation))
        ->assertInertia(fn (Assert $page) => $page
            ->where('selectedFormulaId', $formula->id)
            ->where('formulas.0.items.0.estimated_cost', 25)
            ->where('formulas.0.total', 25));
});

test('an item appears once per formula but may repeat across versions', function () {
    $item = InventoryItem::factory()->create();
    $draft = InnovationFormula::factory()->for($this->innovation)->create();
    InnovationItem::factory()->forFormula($draft)->create(['inventory_item_id' => $item->id]);
    $nextDraft = InnovationFormula::factory()->for($this->innovation)->create();

    $this->actingAs($this->contributor)
        ->post(route('innovations.items.store', $this->innovation), [
            'innovation_formula_id' => $draft->id,
            'inventory_item_id' => $item->id,
            'quantity_required' => 1,
            'is_optional' => false,
        ])
        ->assertSessionHasErrors(['inventory_item_id' => 'This item is already in the bill of materials. Edit the existing line instead.']);

    $this->post(route('innovations.items.store', $this->innovation), [
        'innovation_formula_id' => $nextDraft->id,
        'inventory_item_id' => $item->id,
        'quantity_required' => 1,
        'is_optional' => false,
    ])->assertSessionHasNoErrors();

    expect($nextDraft->items()->count())->toBe(1);
});

test('once an innovation has formulas a new line must name one of its own', function () {
    $item = InventoryItem::factory()->create();
    InnovationFormula::factory()->for($this->innovation)->create();
    $foreignFormula = InnovationFormula::factory()->create();

    $this->actingAs($this->contributor)
        ->post(route('innovations.items.store', $this->innovation), [
            'inventory_item_id' => $item->id,
            'quantity_required' => 1,
            'is_optional' => false,
        ])
        ->assertSessionHasErrors(['innovation_formula_id' => 'Choose the formula version to add this line to.']);

    $this->post(route('innovations.items.store', $this->innovation), [
        'innovation_formula_id' => $foreignFormula->id,
        'inventory_item_id' => $item->id,
        'quantity_required' => 1,
        'is_optional' => false,
    ])->assertSessionHasErrors('innovation_formula_id');

    expect($foreignFormula->items()->exists())->toBeFalse();
});

test('bom lines use the chosen unit and require a positive quantity', function () {
    $item = InventoryItem::factory()->create();
    $unit = Unit::factory()->create();

    $this->actingAs($this->contributor)
        ->post(route('innovations.items.store', $this->innovation), [
            'inventory_item_id' => $item->id,
            'unit_id' => $unit->id,
            'quantity_required' => 0,
            'is_optional' => true,
        ])
        ->assertSessionHasErrors('quantity_required');

    $this->post(route('innovations.items.store', $this->innovation), [
        'inventory_item_id' => $item->id,
        'unit_id' => $unit->id,
        'quantity_required' => 3,
        'is_optional' => true,
    ])->assertSessionHasNoErrors();

    expect($this->innovation->innovationItems()->sole())
        ->unit_id->toBe($unit->id)
        ->is_optional->toBeTrue();
});

test('bom lines of another innovation cannot be edited through this one', function () {
    $foreignLine = InnovationItem::factory()->create();

    $this->actingAs($this->contributor)
        ->delete(route('innovations.items.destroy', [$this->innovation, $foreignLine]))
        ->assertNotFound();
});

test('users who cannot update the innovation cannot change its bom', function () {
    $item = InventoryItem::factory()->create();

    $this->actingAs(User::factory()->contributor()->create())
        ->post(route('innovations.items.store', $this->innovation), [
            'inventory_item_id' => $item->id,
            'quantity_required' => 1,
            'is_optional' => false,
        ])
        ->assertForbidden();
});

test('a technology can be linked once per innovation', function () {
    $technology = Technology::factory()->create();

    $this->actingAs($this->contributor)
        ->post(route('innovations.technologies.store', $this->innovation), [
            'technology_id' => $technology->id,
            'version' => '13.x',
            'purpose' => 'Backend',
        ])
        ->assertSessionHasNoErrors();

    $this->post(route('innovations.technologies.store', $this->innovation), [
        'technology_id' => $technology->id,
    ])->assertSessionHasErrors(['technology_id' => 'This technology is already linked to the innovation.']);

    expect($this->innovation->innovationTechnologies()->sole())
        ->version->toBe('13.x')
        ->purpose->toBe('Backend');
});
