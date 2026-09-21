<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.public'), Title('Showcase')] class extends Component
{
    //
};
?>

<section data-page-title="Showcase">
    <div class="mx-auto flex w-full max-w-5xl flex-col gap-4">
        <flux:heading size="xl">{{ __('Showcase') }}</flux:heading>
        <flux:text>{{ __('This public space will feature approved innovations and selected media.') }}</flux:text>
        <x-page-roadmap
            :tasks="[
                __('Display only innovations explicitly marked public and ready to showcase.'),
                __('Show featured innovations, their approved images or video links, and accessible descriptions.'),
                __('Add public browsing and detail pages without exposing internal-only records or media.'),
            ]"
            :related="[
                ['label' => __('Innovations'), 'description' => __('The internal registry controls status, visibility, featured selection, and public media.'), 'href' => auth()->user()?->can('viewAny', App\Models\Innovation::class) ? route('innovations.index') : null],
                ['label' => __('Innovation categories'), 'description' => __('Categories classify the innovations that may be presented here.'), 'href' => auth()->user()?->can('viewAny', App\Models\InnovationCategory::class) ? route('innovation-categories.index') : null],
            ]"
        />
    </div>
</section>
