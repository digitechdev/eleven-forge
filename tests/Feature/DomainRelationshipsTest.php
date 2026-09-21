<?php

use App\InnovationType;
use App\InnovationVisibility;
use App\Models\Innovation;
use App\Models\InnovationItem;
use App\Models\InnovationMedia;
use App\Models\InnovationTechnology;
use App\Models\InventoryItem;
use App\Models\Technology;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

test('an innovation connects its category, creator, BOM, technologies, and media', function () {
    $innovation = Innovation::factory()->create([
        'type' => InnovationType::Hybrid,
        'visibility' => InnovationVisibility::Public,
    ]);
    $item = InventoryItem::factory()->create(['quantity_on_hand' => '12.500']);
    $unit = Unit::factory()->create();
    $technology = Technology::factory()->create();

    InnovationItem::factory()->for($innovation)->for($item, 'inventoryItem')->for($unit)->create([
        'quantity_required' => '2.250',
    ]);
    InnovationTechnology::factory()->for($innovation)->for($technology)->create();
    InnovationMedia::factory()->for($innovation)->create();

    $innovation->refresh();

    expect($innovation->category)->not->toBeNull()
        ->and($innovation->creator)->not->toBeNull()
        ->and($innovation->type)->toBe(InnovationType::Hybrid)
        ->and($innovation->visibility)->toBe(InnovationVisibility::Public)
        ->and($innovation->inventoryItems)->toHaveCount(1)
        ->and($innovation->technologies)->toHaveCount(1)
        ->and($innovation->media)->toHaveCount(1)
        ->and($innovation->innovationItems->first()->quantity_required)->toBe('2.250')
        ->and($item->refresh()->quantity_on_hand)->toBe('12.500');
});

test('reference data seeding is repeatable and does not create a sample account', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(Unit::count())->toBe(9)
        ->and(Technology::count())->toBe(10)
        ->and(User::count())->toBe(5)
        ->and(User::query()->where('email', 'test@example.com')->exists())->toBeFalse();
});
