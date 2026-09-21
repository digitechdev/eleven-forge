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
        <flux:text>{{ __('Control who can sign in and what they can manage.') }}</flux:text>
        <x-page-roadmap
            :tasks="[
                __('Build admin-created accounts with name, email, password setup, and active or inactive status.'),
                __('Assign roles to users and show the permissions each assignment provides.'),
                __('Add safe account updates and deactivation while preserving ownership history.'),
            ]"
            :related="[
                ['label' => __('Roles'), 'description' => __('Roles group permissions and are assigned to users.'), 'href' => auth()->user()?->can(App\AccessPermission::RoleManage->value) ? route('administration.roles.index') : null],
                ['label' => __('Permissions'), 'description' => __('Permissions define which pages and actions a role can use.'), 'href' => auth()->user()?->can(App\AccessPermission::RoleManage->value) ? route('administration.permissions.index') : null],
                ['label' => __('Innovations'), 'description' => __('Creator and last-editor links keep an innovation tied to its users.'), 'href' => auth()->user()?->can('viewAny', App\Models\Innovation::class) ? route('innovations.index') : null],
            ]"
        />
    </div>
</section>
