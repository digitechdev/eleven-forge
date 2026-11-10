<?php

use App\Models\Innovation;
use App\Models\InnovationCategory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('viewers can list innovation categories in display order but not manage them', function () {
    $robotics = InnovationCategory::factory()->create(['name' => 'Robotics', 'sort_order' => 2]);
    InnovationCategory::factory()->create(['name' => 'Automation', 'sort_order' => 1]);
    Innovation::factory()->for($robotics, 'category')->count(2)->create();

    $this->actingAs(User::factory()->viewer()->create())
        ->get(route('innovation-categories.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('innovation-categories/Index')
            ->where('categories.data.0.name', 'Automation')
            ->where('categories.data.1.name', 'Robotics')
            ->where('categories.data.1.innovations_count', 2)
            ->where('can.manage', false),
        );

    $this->post(route('innovation-categories.store'), ['name' => 'Energy', 'is_active' => true])
        ->assertForbidden();
});

test('managers can create categories with a generated unique slug and update them', function () {
    $this->actingAs(User::factory()->manager()->create());

    $this->post(route('innovation-categories.store'), ['name' => 'Smart Farming', 'slug' => '', 'is_active' => true])
        ->assertSessionHasNoErrors();

    $category = InnovationCategory::query()->where('slug', 'smart-farming')->firstOrFail();

    $this->post(route('innovation-categories.store'), ['name' => 'Smart Farming', 'is_active' => true])
        ->assertSessionHasErrors(['name', 'slug']);

    $this->put(route('innovation-categories.update', $category), [
        'name' => 'Smart Farming',
        'slug' => 'smart-farming',
        'is_active' => false,
        'sort_order' => 5,
    ])->assertSessionHasNoErrors();

    expect($category->fresh())
        ->is_active->toBeFalse()
        ->sort_order->toBe(5);
});

test('categories with innovations cannot be deleted', function () {
    $this->actingAs(User::factory()->manager()->create());
    $usedCategory = Innovation::factory()->create()->category;
    $unusedCategory = InnovationCategory::factory()->create();

    $this->delete(route('innovation-categories.destroy', $usedCategory));
    $this->delete(route('innovation-categories.destroy', $unusedCategory));

    expect($usedCategory->fresh())->not->toBeNull()
        ->and($unusedCategory->fresh())->toBeNull();
});
