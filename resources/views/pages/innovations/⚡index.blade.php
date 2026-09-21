<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app'), Title('Innovations')] class extends Component
{
    //
};
?>

<section data-page-title="Innovations">
    <div class="mx-auto flex w-full max-w-5xl flex-col gap-4">
        <flux:heading size="xl">{{ __('Innovations') }}</flux:heading>
        <flux:text>{{ __('The central record for each software, hardware, or hybrid innovation.') }}</flux:text>
        <x-page-roadmap
            :tasks="[
                __('Build create, edit, search, and restore flows for innovation details, status, category, and ownership.'),
                __('Add a bill of materials with inventory items, quantities, units, and optional parts.'),
                __('Link technologies with version and purpose, and attach images or video URLs.'),
                __('Control public visibility, featured status, and which media can appear in Showcase.'),
            ]"
            :related="[
                ['label' => __('Innovation categories'), 'description' => __('Each innovation belongs to a business or functional category.'), 'href' => auth()->user()?->can('viewAny', App\Models\InnovationCategory::class) ? route('innovation-categories.index') : null],
                ['label' => __('Inventory items'), 'description' => __('Physical parts used by an innovation become its bill of materials.'), 'href' => auth()->user()?->can('viewAny', App\Models\InventoryItem::class) ? route('inventory.items.index') : null],
                ['label' => __('Technologies'), 'description' => __('Software tools and platforms are linked with their version and purpose.'), 'href' => auth()->user()?->can('viewAny', App\Models\Technology::class) ? route('technologies.index') : null],
                ['label' => __('Showcase'), 'description' => __('Selected public innovations and media appear on the public site.'), 'href' => route('showcase.index')],
            ]"
        />
    </div>
</section>
