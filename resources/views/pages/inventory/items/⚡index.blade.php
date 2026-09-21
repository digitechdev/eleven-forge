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
        <flux:card>
            <flux:text>{{ __('The physical item catalog will be built in the next phase.') }}</flux:text>
        </flux:card>
    </div>
</section>
