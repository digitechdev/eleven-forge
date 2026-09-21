<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.public'), Title('Showcase')] class extends Component
{
    //
};
?>

<section data-page-title="Showcase">
    <div class="mx-auto flex w-full max-w-5xl flex-col gap-4">
        <flux:heading size="xl">{{ __('Showcase') }}</flux:heading>
        <flux:card>
            <flux:text>{{ __('Selected public innovations will appear here after the publishing workflow is built.') }}</flux:text>
        </flux:card>
    </div>
</section>
