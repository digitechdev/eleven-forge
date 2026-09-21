<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app'), Title('Innovation categories')] class extends Component
{
    //
};
?>

<section data-page-title="Innovation categories">
    <div class="mx-auto flex w-full max-w-5xl flex-col gap-4">
        <flux:heading size="xl">{{ __('Innovation categories') }}</flux:heading>
        <flux:text>{{ __('Classify innovations by business area or purpose.') }}</flux:text>
        <x-page-roadmap
            :tasks="[
                __('Build category creation and editing with name, slug, description, active state, and display order.'),
                __('Let staff assign a category to an innovation and filter the registry by category.'),
                __('Handle category deactivation without losing existing innovation relationships.'),
            ]"
            :related="[
                ['label' => __('Innovations'), 'description' => __('One category can classify many innovation records.'), 'href' => auth()->user()?->can('viewAny', App\Models\Innovation::class) ? route('innovations.index') : null],
                ['label' => __('Dashboard'), 'description' => __('Category summaries can feed future innovation overview cards.'), 'href' => route('dashboard')],
            ]"
        />
    </div>
</section>
