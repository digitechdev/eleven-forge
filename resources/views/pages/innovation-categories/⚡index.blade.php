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
        <flux:card>
            <flux:text>{{ __('Category management will be built in the next phase.') }}</flux:text>
        </flux:card>
    </div>
</section>
