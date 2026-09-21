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
        <flux:card>
            <flux:text>{{ __('Innovation records and related BOM, technologies, and media will be built in the next phase.') }}</flux:text>
        </flux:card>
    </div>
</section>
