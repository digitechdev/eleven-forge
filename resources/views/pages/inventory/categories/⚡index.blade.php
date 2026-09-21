<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app'), Title('Item categories')] class extends Component
{
    //
};
?>

<section data-page-title="Item categories">
    <div class="mx-auto flex w-full max-w-5xl flex-col gap-4">
        <flux:heading size="xl">{{ __('Item categories') }}</flux:heading>
        <flux:text>{{ __('Organize physical inventory into parent and child categories.') }}</flux:text>
        <x-page-roadmap
            :tasks="[
                __('Build category creation and editing with optional parent, description, active state, and display order.'),
                __('Show the category hierarchy and let staff filter items by category.'),
                __('Preserve item assignments when a category is deactivated.'),
            ]"
            :related="[
                ['label' => __('Inventory items'), 'description' => __('One category can contain many physical items.'), 'href' => auth()->user()?->can('viewAny', App\Models\InventoryItem::class) ? route('inventory.items.index') : null],
                ['label' => __('Innovations'), 'description' => __('Categorized items become parts in innovation bills of materials.'), 'href' => auth()->user()?->can('viewAny', App\Models\Innovation::class) ? route('innovations.index') : null],
            ]"
        />
    </div>
</section>
