@props([
    'tasks' => [],
    'related' => [],
])

<div class="grid gap-4 lg:grid-cols-2">
    <flux:card>
        <flux:heading size="lg">{{ __('Next tasks') }}</flux:heading>
        <ol class="mt-4 list-decimal space-y-2 ps-5 text-sm text-zinc-700 dark:text-zinc-300">
            @foreach ($tasks as $task)
                <li wire:key="roadmap-task-{{ $loop->index }}">{{ $task }}</li>
            @endforeach
        </ol>
    </flux:card>

    <flux:card>
        <flux:heading size="lg">{{ __('Connected areas') }}</flux:heading>
        <div class="mt-4 space-y-4">
            @foreach ($related as $area)
                <div wire:key="roadmap-area-{{ $loop->index }}">
                    @if ($area['href'] ?? null)
                        <flux:link :href="$area['href']" wire:navigate>{{ $area['label'] }}</flux:link>
                    @else
                        <flux:heading size="sm">{{ $area['label'] }}</flux:heading>
                    @endif
                    <flux:text>{{ $area['description'] }}</flux:text>
                </div>
            @endforeach
        </div>
    </flux:card>
</div>
