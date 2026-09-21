<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app'), Title('Users')] class extends Component
{
    //
};
?>

<section data-page-title="Users">
    <div class="mx-auto flex w-full max-w-5xl flex-col gap-4">
        <flux:heading size="xl">{{ __('Users') }}</flux:heading>
        <flux:card>
            <flux:text>{{ __('Admin account management will be built with the authentication workflow.') }}</flux:text>
        </flux:card>
    </div>
</section>
