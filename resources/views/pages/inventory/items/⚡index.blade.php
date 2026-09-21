<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app'), Title('Inventory items')] class extends Component
{
    //
};
?>

<section data-page-title="Inventory items">
    <div class="mx-auto flex w-full max-w-5xl flex-col gap-4">
        <flux:heading size="xl">{{ __('Inventory items') }}</flux:heading>
        <flux:text>{{ __('The master catalog for physical materials, components, and equipment.') }}</flux:text>
        <x-page-roadmap
            :tasks="[
                __('Build item creation and editing for codes, type, specifications, category, unit, cost, and status.'),
                __('Track quantity on hand as a stock snapshot and flag items below minimum stock.'),
                __('Connect items to innovation bills of materials with required quantities and optional parts.'),
            ]"
            :related="[
                ['label' => __('Item categories'), 'description' => __('Categories organize items in a physical inventory hierarchy.'), 'href' => auth()->user()?->can('viewAny', App\Models\ItemCategory::class) ? route('inventory.categories.index') : null],
                ['label' => __('Units'), 'description' => __('Each item has a default measurement unit.'), 'href' => auth()->user()?->can('viewAny', App\Models\Unit::class) ? route('inventory.units.index') : null],
                ['label' => __('Innovations'), 'description' => __('Items are reused in innovation bills of materials.'), 'href' => auth()->user()?->can('viewAny', App\Models\Innovation::class) ? route('innovations.index') : null],
                ['label' => __('Dashboard'), 'description' => __('Low-stock items can appear in a future dashboard summary.'), 'href' => route('dashboard')],
            ]"
        />
    </div>
</section>
