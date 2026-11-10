<?php

use App\Enums\InnovationMediaType;
use App\Models\InventoryItem;
use App\Models\InventoryItemMedia;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Storage::fake('public');
    $this->item = InventoryItem::factory()->create();
});

test('managers upload images and videos to the item folder on the public disk', function () {
    $manager = User::factory()->manager()->create();

    $this->actingAs($manager)
        ->post(route('inventory.items.media.store', $this->item), ['file' => UploadedFile::fake()->image('front.jpg')])
        ->assertSessionHasNoErrors();
    $this->post(route('inventory.items.media.store', $this->item), ['file' => UploadedFile::fake()->create('demo.mp4', 50 * 1024, 'video/mp4')])
        ->assertSessionHasNoErrors();

    [$image, $video] = $this->item->media()->get()->all();

    expect($image->media_type)->toBe(InnovationMediaType::Image)
        ->and($image->original_name)->toBe('front.jpg')
        ->and($image->uploaded_by)->toBe($manager->id)
        ->and($image->file_path)->toStartWith("inventory-items/{$this->item->id}/")
        ->and($video->media_type)->toBe(InnovationMediaType::Video)
        ->and($video->sort_order)->toBe(1);

    Storage::disk('public')->assertExists([$image->file_path, $video->file_path]);

    $this->get(route('inventory.items.show', $this->item))
        ->assertInertia(fn (Assert $page) => $page
            ->has('media', 2)
            ->where('media.0.url', $image->url())
            ->where('can.update', true),
        );
});

test('uploads must be an allowed type and size', function (UploadedFile $file) {
    $this->actingAs(User::factory()->manager()->create())
        ->post(route('inventory.items.media.store', $this->item), ['file' => $file])
        ->assertSessionHasErrors('file');

    expect($this->item->media()->count())->toBe(0);
})->with([
    'pdf' => fn () => UploadedFile::fake()->create('manual.pdf', 100, 'application/pdf'),
    'gif' => fn () => UploadedFile::fake()->image('anim.gif'),
    'image over 10 MB' => fn () => UploadedFile::fake()->image('huge.jpg')->size(10241),
    'video over 100 MB' => fn () => UploadedFile::fake()->create('long.mp4', 102401, 'video/mp4'),
]);

test('an item can have at most ten media files', function () {
    InventoryItemMedia::factory()->count(InventoryItemMedia::MAX_PER_ITEM)->create(['inventory_item_id' => $this->item->id]);

    $this->actingAs(User::factory()->manager()->create())
        ->post(route('inventory.items.media.store', $this->item), ['file' => UploadedFile::fake()->image('extra.png')])
        ->assertSessionHasErrors('file');

    expect($this->item->media()->count())->toBe(InventoryItemMedia::MAX_PER_ITEM);
});

test('moving a file renumbers the gallery', function () {
    [$first, $second, $third] = collect(range(0, 2))
        ->map(fn (int $order) => InventoryItemMedia::factory()->create(['inventory_item_id' => $this->item->id, 'sort_order' => $order]))
        ->all();

    $this->actingAs(User::factory()->manager()->create())
        ->put(route('inventory.items.media.update', [$this->item, $third]), ['sort_order' => 0])
        ->assertSessionHasNoErrors();

    expect($this->item->media()->pluck('id')->all())->toBe([$third->id, $first->id, $second->id]);
});

test('deleting media removes the stored file', function () {
    $path = UploadedFile::fake()->image('front.jpg')->store("inventory-items/{$this->item->id}", 'public');
    $media = InventoryItemMedia::factory()->create(['inventory_item_id' => $this->item->id, 'file_path' => $path]);

    $this->actingAs(User::factory()->manager()->create())
        ->delete(route('inventory.items.media.destroy', [$this->item, $media]))
        ->assertRedirect();

    expect($media->fresh())->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

test('media of another item cannot be changed through this item', function () {
    $foreign = InventoryItemMedia::factory()->create();

    $this->actingAs(User::factory()->manager()->create())
        ->delete(route('inventory.items.media.destroy', [$this->item, $foreign]))
        ->assertNotFound();

    expect($foreign->fresh())->not->toBeNull();
});

test('viewers cannot upload, reorder, or delete media', function () {
    $media = InventoryItemMedia::factory()->create(['inventory_item_id' => $this->item->id]);

    $this->actingAs(User::factory()->viewer()->create());

    $this->post(route('inventory.items.media.store', $this->item), ['file' => UploadedFile::fake()->image('front.jpg')])->assertForbidden();
    $this->put(route('inventory.items.media.update', [$this->item, $media]), ['sort_order' => 1])->assertForbidden();
    $this->delete(route('inventory.items.media.destroy', [$this->item, $media]))->assertForbidden();
});
