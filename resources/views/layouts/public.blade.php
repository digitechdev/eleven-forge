<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white text-zinc-900 dark:bg-zinc-900 dark:text-white">
        <header class="border-b border-zinc-200 dark:border-zinc-700">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-4">
                <a href="{{ route('showcase.index') }}" class="font-semibold">Eleven Forge</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="text-sm">{{ __('Dashboard') }}</a>
                @else
                    <a href="{{ route('login') }}" class="text-sm">{{ __('Staff sign in') }}</a>
                @endauth
            </div>
        </header>
        <main class="px-4 py-10">{{ $slot }}</main>
        @fluxScripts
    </body>
</html>
