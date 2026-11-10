<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuBadge,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavItem } from '@/types';

withDefaults(
    defineProps<{
        items: NavItem[];
        label?: string;
    }>(),
    { label: 'Platform' },
);

const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

function active(item: NavItem): boolean {
    if (item.action) {
        return false;
    }

    return item.exact
        ? isCurrentUrl(item.href)
        : isCurrentOrParentUrl(item.href);
}
</script>

<template>
    <SidebarGroup class="px-2 py-1">
        <SidebarGroupLabel
            class="text-[11px] font-semibold tracking-wide text-sidebar-foreground/40 uppercase"
        >
            {{ label }}
        </SidebarGroupLabel>
        <SidebarMenu class="gap-0.5">
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="active(item)"
                    :tooltip="item.title"
                    class="group/nav relative h-9 rounded-lg text-sidebar-foreground/70 transition-all duration-200 hover:bg-sidebar-accent hover:text-sidebar-accent-foreground data-[active=true]:bg-gradient-to-r data-[active=true]:from-sidebar-primary/18 data-[active=true]:to-sidebar-accent/40 data-[active=true]:font-medium data-[active=true]:text-sidebar-accent-foreground"
                >
                    <component
                        :is="item.action ? 'button' : Link"
                        v-bind="
                            item.action
                                ? { type: 'button', onClick: item.action }
                                : {
                                      href: item.href,
                                      'aria-current': active(item)
                                          ? 'page'
                                          : undefined,
                                  }
                        "
                    >
                        <span
                            :class="[
                                'absolute inset-y-2 left-0 w-[3px] origin-center rounded-full bg-sidebar-primary shadow-[0_0_10px_var(--sidebar-primary)] transition-transform duration-300 ease-[var(--ease-out-expo)]',
                                active(item) ? 'scale-y-100' : 'scale-y-0',
                            ]"
                            aria-hidden="true"
                        />
                        <component
                            :is="item.icon"
                            :class="[
                                'transition-transform duration-300 ease-[var(--ease-spring)] group-hover/nav:scale-110',
                                active(item) ? 'text-sidebar-primary' : '',
                            ]"
                        />
                        <span class="group-data-[collapsible=icon]:hidden">{{
                            item.title
                        }}</span>
                    </component>
                </SidebarMenuButton>
                <SidebarMenuBadge
                    v-if="item.badge"
                    class="rounded-full bg-sidebar-primary px-1.5 text-[11px] font-semibold text-sidebar-primary-foreground"
                    :aria-label="`${item.badge} pending`"
                >
                    {{ item.badge }}
                </SidebarMenuBadge>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
