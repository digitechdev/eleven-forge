<?php

use App\Enums\InnovationMediaType;
use App\Enums\InnovationVisibility;
use App\Models\Innovation;
use App\Models\InnovationMedia;
use App\Models\InnovationTechnology;
use App\Models\Technology;
use Inertia\Testing\AssertableInertia as Assert;

test('guests see only public innovations, featured first', function () {
    Innovation::factory()->create(['name' => 'Alpha Public', 'visibility' => InnovationVisibility::Public]);
    Innovation::factory()->create(['name' => 'Zeta Featured', 'visibility' => InnovationVisibility::Public, 'is_featured' => true]);
    Innovation::factory()->create(['name' => 'Secret Internal', 'visibility' => InnovationVisibility::Internal]);
    Innovation::factory()->create(['name' => 'Archived Public', 'visibility' => InnovationVisibility::Public])->delete();

    $this->get(route('showcase.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('showcase/Index')
            ->has('innovations.data', 2)
            ->where('innovations.data.0.name', 'Zeta Featured')
            ->where('innovations.data.1.name', 'Alpha Public')
            ->missing('innovations.data.0.created_by')
            ->missing('innovations.data.0.id'),
        );
});

test('the showcase can be filtered by category and search', function () {
    $match = Innovation::factory()->create(['name' => 'Smart Bin', 'visibility' => InnovationVisibility::Public]);
    Innovation::factory()->create(['name' => 'Smart Meter', 'visibility' => InnovationVisibility::Public]);

    $this->get(route('showcase.index', ['category' => $match->category->slug, 'search' => 'smart']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('innovations.data', 1)
            ->where('innovations.data.0.slug', $match->slug),
        );
});

test('cards use a public cover image and never internal media', function () {
    $innovation = Innovation::factory()->create(['visibility' => InnovationVisibility::Public]);
    InnovationMedia::factory()->for($innovation)->create(['file_path' => 'innovations/internal.jpg', 'is_cover' => true, 'is_public' => false]);
    InnovationMedia::factory()->for($innovation)->create(['file_path' => 'innovations/public.jpg', 'is_public' => true]);

    $this->get(route('showcase.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('innovations.data.0.cover.url', fn (string $url) => str_ends_with($url, 'innovations/public.jpg')),
        );
});

test('public innovation detail shows public media and technologies only', function () {
    $innovation = Innovation::factory()->create([
        'visibility' => InnovationVisibility::Public,
        'key_features' => ['Edge AI'],
    ]);
    InnovationMedia::factory()->for($innovation)->create(['file_path' => 'innovations/internal.jpg', 'is_public' => false]);
    InnovationMedia::factory()->for($innovation)->create(['file_path' => 'innovations/public.jpg', 'is_public' => true, 'is_cover' => true]);
    InnovationMedia::factory()->for($innovation)->create([
        'media_type' => InnovationMediaType::Video,
        'file_path' => null,
        'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        'is_public' => true,
    ]);
    InnovationTechnology::factory()->for($innovation)->for(Technology::factory()->state(['name' => 'Laravel']))->create();

    $this->get(route('showcase.show', $innovation->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('showcase/Show')
            ->where('innovation.name', $innovation->name)
            ->where('innovation.key_features', ['Edge AI'])
            ->where('innovation.technologies', ['Laravel'])
            ->has('innovation.media', 2)
            ->where('innovation.media.0.type', 'image')
            ->where('innovation.media.1.embed_url', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ')
            ->missing('innovation.created_by')
            ->missing('innovation.innovation_code'),
        )
        ->assertDontSee('internal.jpg');
});

test('internal and archived innovations are not found on the showcase', function () {
    $internal = Innovation::factory()->create(['visibility' => InnovationVisibility::Internal]);
    $archived = Innovation::factory()->create(['visibility' => InnovationVisibility::Public]);
    $archived->delete();

    $this->get(route('showcase.show', $internal->slug))->assertNotFound();
    $this->get(route('showcase.show', $archived->slug))->assertNotFound();
});

test('the showcase never exposes internal costing', function () {
    $innovation = Innovation::factory()->create([
        'visibility' => InnovationVisibility::Public,
        'markup_percentage' => 30,
        'estimated_unit_cost' => 1234.56,
        'suggested_selling_price' => 1604.93,
    ]);

    $this->get(route('showcase.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('innovations.data', 1)
            ->missing('innovations.data.0.markup_percentage')
            ->missing('innovations.data.0.estimated_unit_cost')
            ->missing('innovations.data.0.suggested_selling_price'))
        ->assertDontSee('1234.56')
        ->assertDontSee('1604.93');

    $this->get(route('showcase.show', $innovation->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->missing('innovation.markup_percentage')
            ->missing('innovation.estimated_unit_cost')
            ->missing('innovation.suggested_selling_price'))
        ->assertDontSee('1234.56')
        ->assertDontSee('1604.93');
});
