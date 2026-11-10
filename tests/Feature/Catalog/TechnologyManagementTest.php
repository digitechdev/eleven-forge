<?php

use App\Enums\TechnologyCategory;
use App\Models\Innovation;
use App\Models\InnovationTechnology;
use App\Models\Technology;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('viewers can filter technologies and see the innovations using them but not manage them', function () {
    $python = Technology::factory()->create(['name' => 'Python', 'category' => TechnologyCategory::Language]);
    Technology::factory()->create(['name' => 'Laravel', 'category' => TechnologyCategory::Framework]);
    $innovation = Innovation::factory()->create(['name' => 'Crop Scanner']);
    InnovationTechnology::factory()->for($innovation)->for($python)->create(['version' => '3.12', 'purpose' => 'Image pipeline']);

    $this->actingAs(User::factory()->viewer()->create())
        ->get(route('technologies.index', ['category' => 'Language']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('technologies/Index')
            ->has('technologies.data', 1)
            ->where('technologies.data.0.name', 'Python')
            ->where('technologies.data.0.innovations_count', 1)
            ->where('technologies.data.0.innovations.0.name', 'Crop Scanner')
            ->where('technologies.data.0.innovations.0.version', '3.12')
            ->where('can.manage', false),
        );

    $this->post(route('technologies.store'), ['name' => 'Rust', 'category' => 'Language', 'status' => 'ACTIVE'])
        ->assertForbidden();
});

test('managers can create and update technologies with unique names and known categories', function () {
    $this->actingAs(User::factory()->manager()->create());

    $this->post(route('technologies.store'), [
        'name' => 'ONNX',
        'category' => 'AI / ML',
        'website' => 'https://onnx.ai',
        'status' => 'ACTIVE',
    ])->assertSessionHasNoErrors();

    $technology = Technology::query()->where('name', 'ONNX')->firstOrFail();

    $this->post(route('technologies.store'), ['name' => 'ONNX', 'category' => 'Gadget', 'website' => 'not a url', 'status' => 'ACTIVE'])
        ->assertSessionHasErrors(['name', 'category', 'website']);

    $this->put(route('technologies.update', $technology), ['name' => 'ONNX', 'category' => 'AI / ML', 'status' => 'DEPRECATED'])
        ->assertSessionHasNoErrors();

    expect($technology->fresh()->category)->toBe(TechnologyCategory::AiMl)
        ->and($technology->fresh()->status->value)->toBe('DEPRECATED');
});

test('technologies used by innovations cannot be deleted', function () {
    $this->actingAs(User::factory()->manager()->create());
    $usedTechnology = InnovationTechnology::factory()->create()->technology;
    $unusedTechnology = Technology::factory()->create();

    $this->delete(route('technologies.destroy', $usedTechnology));
    $this->delete(route('technologies.destroy', $unusedTechnology));

    expect($usedTechnology->fresh())->not->toBeNull()
        ->and($unusedTechnology->fresh())->toBeNull();
});
