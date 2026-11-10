<?php

use App\Enums\InnovationStatus;
use App\Enums\InnovationType;
use App\Enums\InnovationVisibility;
use App\Models\Innovation;
use App\Models\InnovationCategory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

/**
 * @return array<string, mixed>
 */
function innovationPayload(array $overrides = []): array
{
    return [
        'innovation_code' => '',
        'name' => 'Smart Egg Board',
        'slug' => '',
        'category_id' => InnovationCategory::factory()->create()->id,
        'type' => InnovationType::Hybrid->value,
        'tagline' => 'Counts eggs',
        'key_features' => ['Vision counting', ' ', 'Cloud sync'],
        'benefits' => [],
        'status' => InnovationStatus::Prototype->value,
        'visibility' => InnovationVisibility::Internal->value,
        'developed_at' => '2026-05-01',
        'is_featured' => false,
        ...$overrides,
    ];
}

test('viewers can list and show innovations but not create or update them', function () {
    $innovation = Innovation::factory()->create(['name' => 'Egg Counter']);

    $this->actingAs(User::factory()->viewer()->create());

    $this->get(route('innovations.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('innovations/Index')
            ->has('innovations.data', 1)
            ->where('can.create', false)
            ->where('can.restore', false));

    $this->get(route('innovations.show', $innovation))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('innovations/Show')
            ->where('innovation.name', 'Egg Counter')
            ->where('can.update', false)
            ->where('options', null));

    $this->get(route('innovations.create'))->assertForbidden();
    $this->post(route('innovations.store'), innovationPayload())->assertForbidden();
    $this->put(route('innovations.update', $innovation), innovationPayload())->assertForbidden();
});

test('contributors create innovations with a generated code and slug', function () {
    $contributor = User::factory()->contributor()->create();
    Innovation::factory()->create(['name' => 'Smart Egg Board', 'slug' => 'smart-egg-board'])->delete();

    $this->actingAs($contributor)
        ->post(route('innovations.store'), innovationPayload())
        ->assertSessionHasNoErrors();

    $innovation = Innovation::query()->where('created_by', $contributor->id)->sole();

    expect($innovation->innovation_code)->toBe('INV-'.now()->year.'-0001')
        ->and($innovation->slug)->toBe('smart-egg-board-2')
        ->and($innovation->updated_by)->toBe($contributor->id)
        ->and($innovation->key_features)->toBe(['Vision counting', 'Cloud sync']);

    $this->post(route('innovations.store'), innovationPayload(['name' => 'Second']))->assertSessionHasNoErrors();

    expect(Innovation::query()->where('name', 'Second')->value('innovation_code'))->toBe('INV-'.now()->year.'-0002');
});

test('a slug used by an archived innovation is rejected', function () {
    Innovation::factory()->create(['slug' => 'taken'])->delete();

    $this->actingAs(User::factory()->contributor()->create())
        ->post(route('innovations.store'), innovationPayload(['slug' => 'taken']))
        ->assertSessionHasErrors(['slug' => 'This slug is already used by another innovation (archived innovations included).']);
});

test('inactive categories are rejected for new innovations', function () {
    $inactive = InnovationCategory::factory()->create(['is_active' => false]);

    $this->actingAs(User::factory()->contributor()->create())
        ->post(route('innovations.store'), innovationPayload(['category_id' => $inactive->id]))
        ->assertSessionHasErrors('category_id');
});

test('contributors update their own innovations but not others', function () {
    $contributor = User::factory()->contributor()->create();
    $own = Innovation::factory()->for($contributor, 'creator')->create();
    $others = Innovation::factory()->create();

    $this->actingAs($contributor)
        ->put(route('innovations.update', $own), innovationPayload(['innovation_code' => $own->innovation_code, 'name' => 'Renamed']))
        ->assertRedirect(route('innovations.show', $own));

    expect($own->fresh())
        ->name->toBe('Renamed')
        ->slug->toBe('renamed')
        ->updated_by->toBe($contributor->id);

    $this->put(route('innovations.update', $others), innovationPayload())->assertForbidden();
    $this->get(route('innovations.edit', $others))->assertForbidden();
});

test('managers soft delete and restore innovations', function () {
    $innovation = Innovation::factory()->create();

    $this->actingAs(User::factory()->manager()->create());

    $this->delete(route('innovations.destroy', $innovation))->assertRedirect(route('innovations.index'));
    expect($innovation->fresh()->trashed())->toBeTrue();

    $this->patch(route('innovations.restore', $innovation))->assertSessionHasNoErrors();
    expect($innovation->fresh()->trashed())->toBeFalse();
});

test('contributors cannot restore innovations', function () {
    $contributor = User::factory()->contributor()->create();
    $innovation = Innovation::factory()->for($contributor, 'creator')->create();
    $innovation->delete();

    $this->actingAs($contributor)->patch(route('innovations.restore', $innovation))->assertForbidden();
});

test('innovations can be filtered by category', function () {
    $category = InnovationCategory::factory()->create();
    Innovation::factory()->for($category, 'category')->create(['name' => 'In category']);
    Innovation::factory()->create();

    $this->actingAs(User::factory()->viewer()->create())
        ->get(route('innovations.index', ['category' => $category->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('innovations.data', 1)
            ->where('innovations.data.0.name', 'In category'));
});

test('only users who can restore see trashed innovations', function () {
    Innovation::factory()->create(['name' => 'Active']);
    Innovation::factory()->create(['name' => 'Archived'])->delete();

    $this->actingAs(User::factory()->manager()->create())
        ->get(route('innovations.index', ['trashed' => 'only']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('innovations.data', 1)
            ->where('innovations.data.0.name', 'Archived'));

    $this->actingAs(User::factory()->viewer()->create())
        ->get(route('innovations.index', ['trashed' => 'only']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('innovations.data', 1)
            ->where('innovations.data.0.name', 'Active'));
});

test('only public innovations are published', function () {
    Innovation::factory()->create(['visibility' => InnovationVisibility::Public]);
    Innovation::factory()->create(['visibility' => InnovationVisibility::Internal]);

    expect(Innovation::query()->published()->count())->toBe(1);
});
