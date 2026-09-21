<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Log in to your account')" :description="__('Enter your email and password below to log in')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />


        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <div class="relative">
                <flux:input
                    name="password"
                    :label="__('Password')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Password')"
                    viewable
                />

                @if (Route::has('password.request'))
                    <flux:link class="absolute top-0 text-sm end-0" :href="route('password.request')" wire:navigate>
                        {{ __('Forgot your password?') }}
                    </flux:link>
                @endif
            </div>

            <!-- Remember Me -->
            <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                    {{ __('Log in') }}
                </flux:button>
            </div>
        </form>

        @if (app()->environment(['local', 'testing']) && Route::has('demo.login'))
            <div class="flex flex-col gap-3 border-t border-zinc-200 pt-6 dark:border-zinc-700">
                <flux:heading size="sm">{{ __('Demo accounts') }}</flux:heading>
                <flux:text>{{ __('Choose a role to sign in locally.') }}</flux:text>
                <div class="grid grid-cols-2 gap-2">
                    @foreach (App\UserRole::cases() as $role)
                        <form method="POST" action="{{ route('demo.login', $role->value) }}">
                            @csrf
                            <flux:button type="submit" class="w-full">{{ $role->label() }}</flux:button>
                        </form>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-layouts::auth>
