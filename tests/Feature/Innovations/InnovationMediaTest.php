<?php

use App\Enums\InnovationMediaType;
use App\Models\Innovation;
use App\Models\InnovationMedia;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Storage::fake('public');
    $this->contributor = User::factory()->contributor()->create();
    $this->innovation = Innovation::factory()->for($this->contributor, 'creator')->create();
});

test('uploading an image stores the file and keeps a single cover', function () {
    $previousCover = InnovationMedia::factory()->for($this->innovation)->create(['is_cover' => true]);

    $this->actingAs($this->contributor)
        ->post(route('innovations.media.store', $this->innovation), [
            'media_type' => InnovationMediaType::Image->value,
            'file' => UploadedFile::fake()->image('board.jpg'),
            'alt_text' => 'The egg board',
            'is_cover' => true,
            'is_public' => true,
        ])
        ->assertSessionHasNoErrors();

    $media = $this->innovation->media()->latest('id')->first();

    Storage::disk('public')->assertExists($media->file_path);
    expect($media->file_path)->toStartWith("innovations/{$this->innovation->id}/")
        ->and($media->is_cover)->toBeTrue()
        ->and($media->url)->toBe(Storage::disk('public')->url($media->file_path))
        ->and($previousCover->fresh()->is_cover)->toBeFalse();
});

test('images require alt text and must be images under 5 MB', function () {
    $this->actingAs($this->contributor)
        ->post(route('innovations.media.store', $this->innovation), [
            'media_type' => InnovationMediaType::Image->value,
            'file' => UploadedFile::fake()->create('notes.pdf', 6000, 'application/pdf'),
            'is_cover' => false,
            'is_public' => false,
        ])
        ->assertSessionHasErrors(['file', 'alt_text']);

    expect($this->innovation->media()->count())->toBe(0);
});

test('videos are added by url without a file', function () {
    $this->actingAs($this->contributor)
        ->post(route('innovations.media.store', $this->innovation), [
            'media_type' => InnovationMediaType::Video->value,
            'video_url' => 'https://www.youtube.com/watch?v=abc',
            'title' => 'Demo',
            'is_cover' => false,
            'is_public' => true,
        ])
        ->assertSessionHasNoErrors();

    expect($this->innovation->media()->sole())
        ->media_type->toBe(InnovationMediaType::Video)
        ->file_path->toBeNull()
        ->url->toBe('https://www.youtube.com/watch?v=abc');
});

test('deleting media removes the stored file', function () {
    $path = UploadedFile::fake()->image('old.jpg')->store("innovations/{$this->innovation->id}", 'public');
    $media = InnovationMedia::factory()->for($this->innovation)->create(['file_path' => $path]);

    $this->actingAs($this->contributor)
        ->delete(route('innovations.media.destroy', [$this->innovation, $media]))
        ->assertSessionHasNoErrors();

    Storage::disk('public')->assertMissing($path);
    expect($media->fresh())->toBeNull();
});

test('only public media is returned by the public scope', function () {
    InnovationMedia::factory()->for($this->innovation)->create(['is_public' => true]);
    InnovationMedia::factory()->for($this->innovation)->create(['is_public' => false]);

    expect($this->innovation->media()->public()->count())->toBe(1);
});
