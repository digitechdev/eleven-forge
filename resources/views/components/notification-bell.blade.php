<flux:dropdown position="bottom" align="end">
    <flux:button icon="bell" variant="ghost" :aria-label="__('Notifications')" data-test="notification-bell" />

    <flux:menu>
        <div class="w-64 px-3 py-2">
            <flux:heading size="sm">{{ __('Notifications') }}</flux:heading>
            <flux:text class="mt-2">{{ __('No notifications yet.') }}</flux:text>
        </div>
    </flux:menu>
</flux:dropdown>
