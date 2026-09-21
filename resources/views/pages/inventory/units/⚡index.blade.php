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
        <flux:card>
            <flux:text>{{ __('Unit management will be built in the next phase.') }}</flux:text>
        </flux:card>
    </div>
</section>
