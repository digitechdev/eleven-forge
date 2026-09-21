<x-layouts::app :title="__('Dashboard')">
    <div class="mx-auto flex w-full max-w-5xl flex-col gap-4">
        <div class="flex flex-col gap-1">
            <flux:heading size="xl">{{ __('Welcome back, :name!', ['name' => auth()->user()->name]) }}</flux:heading>
            <flux:text>{{ __('Here is your Eleven Forge dashboard.') }}</flux:text>
        </div>
        <x-page-roadmap
            :tasks="[
                __('Add live innovation counts by status and visibility.'),
                __('Surface low-stock inventory items and recent changes.'),
                __('Show useful shortcuts for the work each role can perform.'),
            ]"
            :related="[
                ['label' => __('Innovations'), 'description' => __('Innovation records will provide lifecycle and publication summaries.'), 'href' => auth()->user()?->can('viewAny', App\Models\Innovation::class) ? route('innovations.index') : null],
                ['label' => __('Inventory items'), 'description' => __('Stock snapshots and minimum levels will support low-stock alerts.'), 'href' => auth()->user()?->can('viewAny', App\Models\InventoryItem::class) ? route('inventory.items.index') : null],
                ['label' => __('Showcase'), 'description' => __('Public innovations are the selected output of the internal registry.'), 'href' => route('showcase.index')],
            ]"
        />
    </div>
</x-layouts::app>
