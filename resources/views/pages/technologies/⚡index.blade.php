<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app'), Title('Technologies')] class extends Component
{
    //
};
?>

<section data-page-title="Technologies">
    <div class="mx-auto flex w-full max-w-5xl flex-col gap-4">
        <flux:heading size="xl">{{ __('Technologies') }}</flux:heading>
        <flux:text>{{ __('Maintain the non-physical tools and platforms used by innovations.') }}</flux:text>
        <x-page-roadmap
            :tasks="[
                __('Build technology records with category, description, website, and active or deprecated status.'),
                __('Link technologies to innovations with the version used and its purpose.'),
                __('Add search and filters so staff can find innovations using a technology.'),
            ]"
            :related="[
                ['label' => __('Innovations'), 'description' => __('An innovation can use many technologies, and a technology can support many innovations.'), 'href' => auth()->user()?->can('viewAny', App\Models\Innovation::class) ? route('innovations.index') : null],
                ['label' => __('Dashboard'), 'description' => __('Technology usage can contribute to future portfolio summaries.'), 'href' => route('dashboard')],
            ]"
        />
    </div>
</section>
