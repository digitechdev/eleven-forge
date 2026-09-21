<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app'), Title('Units')] class extends Component
{
    //
};
?>

<section data-page-title="Units">
    <div class="mx-auto flex w-full max-w-5xl flex-col gap-4">
        <flux:heading size="xl">{{ __('Units') }}</flux:heading>
        <flux:text>{{ __('Standardize how item stock and required quantities are measured.') }}</flux:text>
        <x-page-roadmap
            :tasks="[
                __('Build unit creation and editing with name, symbol, and active state.'),
                __('Assign a default unit to each inventory item.'),
                __('Let each bill-of-materials entry use the appropriate unit for its required quantity.'),
            ]"
            :related="[
                ['label' => __('Inventory items'), 'description' => __('An item uses a default unit for its stock snapshot.'), 'href' => auth()->user()?->can('viewAny', App\Models\InventoryItem::class) ? route('inventory.items.index') : null],
                ['label' => __('Innovations'), 'description' => __('A bill-of-materials line records quantity and unit together.'), 'href' => auth()->user()?->can('viewAny', App\Models\Innovation::class) ? route('innovations.index') : null],
            ]"
        />
    </div>
</section>
