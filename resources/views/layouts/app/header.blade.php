<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:header container class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden mr-2" icon="bars-2" inset="left" />

            <x-app-logo href="{{ route('dashboard') }}" wire:navigate />

            <flux:navbar class="-mb-px max-lg:hidden overflow-x-auto">
                <flux:navbar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:navbar.item>
                @if (auth()->user()->can('viewAny', App\Models\Innovation::class) || auth()->user()->can('viewAny', App\Models\InnovationCategory::class))
                    <flux:dropdown>
                        <flux:navbar.item icon="light-bulb" icon:trailing="chevron-down" :current="request()->routeIs('innovations.*', 'innovation-categories.*')">
                            {{ __('Innovations') }}
                        </flux:navbar.item>
                        <flux:menu>
                            @can('viewAny', App\Models\Innovation::class)
                                <flux:menu.item icon="light-bulb" :href="route('innovations.index')" wire:navigate>{{ __('Innovations') }}</flux:menu.item>
                            @endcan
                            @can('viewAny', App\Models\InnovationCategory::class)
                                <flux:menu.item icon="tag" :href="route('innovation-categories.index')" wire:navigate>{{ __('Innovation categories') }}</flux:menu.item>
                            @endcan
                        </flux:menu>
                    </flux:dropdown>
                @endif
                @can('viewAny', App\Models\Technology::class)
                    <flux:navbar.item icon="code-bracket-square" :href="route('technologies.index')" :current="request()->routeIs('technologies.*')" wire:navigate>
                        {{ __('Technologies') }}
                    </flux:navbar.item>
                @endcan
                @can('viewAny', App\Models\InventoryItem::class)
                    <flux:dropdown>
                        <flux:navbar.item icon="cube" icon:trailing="chevron-down" :current="request()->routeIs('inventory.*')">
                            {{ __('Inventory') }}
                        </flux:navbar.item>
                        <flux:menu>
                            <flux:menu.item icon="cube" :href="route('inventory.items.index')" wire:navigate>{{ __('Items') }}</flux:menu.item>
                            @can('viewAny', App\Models\ItemCategory::class)
                                <flux:menu.item icon="rectangle-stack" :href="route('inventory.categories.index')" wire:navigate>{{ __('Item categories') }}</flux:menu.item>
                            @endcan
                            @can('viewAny', App\Models\Unit::class)
                                <flux:menu.item icon="scale" :href="route('inventory.units.index')" wire:navigate>{{ __('Units') }}</flux:menu.item>
                            @endcan
                        </flux:menu>
                    </flux:dropdown>
                @endcan
                @if (auth()->user()->can('viewAny', App\Models\User::class) || auth()->user()->can(App\AccessPermission::RoleManage->value))
                    <flux:dropdown>
                        <flux:navbar.item icon="user-group" icon:trailing="chevron-down" :current="request()->routeIs('administration.*')">
                            {{ __('Administration') }}
                        </flux:navbar.item>
                        <flux:menu>
                            @can('viewAny', App\Models\User::class)
                                <flux:menu.item icon="user-group" :href="route('administration.users.index')" wire:navigate>{{ __('Users') }}</flux:menu.item>
                            @endcan
                            @can(App\AccessPermission::RoleManage->value)
                                <flux:menu.item icon="identification" :href="route('administration.roles.index')" wire:navigate>{{ __('Roles') }}</flux:menu.item>
                                <flux:menu.item icon="key" :href="route('administration.permissions.index')" wire:navigate>{{ __('Permissions') }}</flux:menu.item>
                            @endcan
                        </flux:menu>
                    </flux:dropdown>
                @endif
            </flux:navbar>

            <flux:spacer />

            <flux:navbar class="me-1.5 space-x-0.5 rtl:space-x-reverse py-0!">
                <flux:navbar.item icon="globe-alt" :href="route('showcase.index')" :current="request()->routeIs('showcase.*')" wire:navigate>
                    {{ __('Showcase') }}
                </flux:navbar.item>
                <flux:tooltip :content="__('Search')" position="bottom">
                    <flux:navbar.item class="!h-10 [&>div>svg]:size-5" icon="magnifying-glass" href="#" :label="__('Search')" />
                </flux:tooltip>
            </flux:navbar>

            <x-notification-bell />

            <x-desktop-user-menu />
        </flux:header>

        <!-- Mobile Menu -->
        <flux:sidebar collapsible="mobile" sticky class="lg:hidden border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Overview')">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard')  }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
                @if (auth()->user()->can('viewAny', App\Models\Innovation::class) || auth()->user()->can('viewAny', App\Models\InnovationCategory::class))
                    <flux:sidebar.group :heading="__('Innovations')">
                        @can('viewAny', App\Models\Innovation::class)
                            <flux:sidebar.item icon="light-bulb" :href="route('innovations.index')" :current="request()->routeIs('innovations.*')" wire:navigate>{{ __('Innovations') }}</flux:sidebar.item>
                        @endcan
                        @can('viewAny', App\Models\InnovationCategory::class)
                            <flux:sidebar.item icon="tag" :href="route('innovation-categories.index')" :current="request()->routeIs('innovation-categories.*')" wire:navigate>{{ __('Innovation categories') }}</flux:sidebar.item>
                        @endcan
                    </flux:sidebar.group>
                @endif
                @can('viewAny', App\Models\Technology::class)
                    <flux:sidebar.group :heading="__('Technologies')">
                        <flux:sidebar.item icon="code-bracket-square" :href="route('technologies.index')" :current="request()->routeIs('technologies.*')" wire:navigate>{{ __('Technologies') }}</flux:sidebar.item>
                    </flux:sidebar.group>
                @endcan
                @can('viewAny', App\Models\InventoryItem::class)
                    <flux:sidebar.group :heading="__('Inventory')">
                        <flux:sidebar.item icon="cube" :href="route('inventory.items.index')" :current="request()->routeIs('inventory.items.*')" wire:navigate>{{ __('Items') }}</flux:sidebar.item>
                        @can('viewAny', App\Models\ItemCategory::class)
                            <flux:sidebar.item icon="rectangle-stack" :href="route('inventory.categories.index')" :current="request()->routeIs('inventory.categories.*')" wire:navigate>{{ __('Item categories') }}</flux:sidebar.item>
                        @endcan
                        @can('viewAny', App\Models\Unit::class)
                            <flux:sidebar.item icon="scale" :href="route('inventory.units.index')" :current="request()->routeIs('inventory.units.*')" wire:navigate>{{ __('Units') }}</flux:sidebar.item>
                        @endcan
                    </flux:sidebar.group>
                @endcan
                @if (auth()->user()->can('viewAny', App\Models\User::class) || auth()->user()->can(App\AccessPermission::RoleManage->value))
                    <flux:sidebar.group :heading="__('Administration')">
                        @can('viewAny', App\Models\User::class)
                            <flux:sidebar.item icon="user-group" :href="route('administration.users.index')" :current="request()->routeIs('administration.users.*')" wire:navigate>{{ __('Users') }}</flux:sidebar.item>
                        @endcan
                        @can(App\AccessPermission::RoleManage->value)
                            <flux:sidebar.item icon="identification" :href="route('administration.roles.index')" :current="request()->routeIs('administration.roles.*')" wire:navigate>{{ __('Roles') }}</flux:sidebar.item>
                            <flux:sidebar.item icon="key" :href="route('administration.permissions.index')" :current="request()->routeIs('administration.permissions.*')" wire:navigate>{{ __('Permissions') }}</flux:sidebar.item>
                        @endcan
                    </flux:sidebar.group>
                @endif
            </flux:sidebar.nav>

            <flux:spacer />

        </flux:sidebar>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
