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
        <flux:card>
            <flux:text>{{ __('Inventory category management will be built in the next phase.') }}</flux:text>
        </flux:card>
    </div>
</section>
