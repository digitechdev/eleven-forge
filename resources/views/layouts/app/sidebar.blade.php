<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Overview')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
                @if (auth()->user()->can('viewAny', App\Models\Innovation::class) || auth()->user()->can('viewAny', App\Models\InnovationCategory::class))
                    <flux:sidebar.group :heading="__('Innovations')" class="grid">
                        @can('viewAny', App\Models\Innovation::class)
                            <flux:sidebar.item icon="light-bulb" :href="route('innovations.index')" :current="request()->routeIs('innovations.*')" wire:navigate>
                                {{ __('Innovations') }}
                            </flux:sidebar.item>
                        @endcan
                        @can('viewAny', App\Models\InnovationCategory::class)
                            <flux:sidebar.item icon="tag" :href="route('innovation-categories.index')" :current="request()->routeIs('innovation-categories.*')" wire:navigate>
                                {{ __('Innovation categories') }}
                            </flux:sidebar.item>
                        @endcan
                    </flux:sidebar.group>
                @endif
                @can('viewAny', App\Models\Technology::class)
                    <flux:sidebar.group :heading="__('Technologies')" class="grid">
                        <flux:sidebar.item icon="code-bracket-square" :href="route('technologies.index')" :current="request()->routeIs('technologies.*')" wire:navigate>
                            {{ __('Technologies') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endcan
                @can('viewAny', App\Models\InventoryItem::class)
                    <flux:sidebar.group :heading="__('Inventory')" class="grid">
                        <flux:sidebar.item icon="cube" :href="route('inventory.items.index')" :current="request()->routeIs('inventory.items.*')" wire:navigate>
                            {{ __('Items') }}
                        </flux:sidebar.item>
                        @can('viewAny', App\Models\ItemCategory::class)
                            <flux:sidebar.item icon="rectangle-stack" :href="route('inventory.categories.index')" :current="request()->routeIs('inventory.categories.*')" wire:navigate>
                                {{ __('Item categories') }}
                            </flux:sidebar.item>
                        @endcan
                        @can('viewAny', App\Models\Unit::class)
                            <flux:sidebar.item icon="scale" :href="route('inventory.units.index')" :current="request()->routeIs('inventory.units.*')" wire:navigate>
                                {{ __('Units') }}
                            </flux:sidebar.item>
                        @endcan
                    </flux:sidebar.group>
                @endcan
                @if (auth()->user()->can('viewAny', App\Models\User::class) || auth()->user()->can(App\AccessPermission::RoleManage->value))
                    <flux:sidebar.group :heading="__('Administration')" class="grid">
                        @can('viewAny', App\Models\User::class)
                            <flux:sidebar.item icon="user-group" :href="route('administration.users.index')" :current="request()->routeIs('administration.users.*')" wire:navigate>
                                {{ __('Users') }}
                            </flux:sidebar.item>
                        @endcan
                        @can(App\AccessPermission::RoleManage->value)
                            <flux:sidebar.item icon="identification" :href="route('administration.roles.index')" :current="request()->routeIs('administration.roles.*')" wire:navigate>
                                {{ __('Roles') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="key" :href="route('administration.permissions.index')" :current="request()->routeIs('administration.permissions.*')" wire:navigate>
                                {{ __('Permissions') }}
                            </flux:sidebar.item>
                        @endcan
                    </flux:sidebar.group>
                @endif
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <flux:header class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:navbar>
                <flux:navbar.item icon="globe-alt" :href="route('showcase.index')" :current="request()->routeIs('showcase.*')" wire:navigate>
                    {{ __('Showcase') }}
                </flux:navbar.item>
            </flux:navbar>

            <x-notification-bell />

            <flux:dropdown position="top" align="end" class="lg:hidden">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
