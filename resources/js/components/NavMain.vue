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
        <SidebarGroupLabel class="label-mono text-sidebar-foreground/40">
            {{ label }}
        </SidebarGroupLabel>
        <SidebarMenu class="gap-px">
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="active(item)"
                    :tooltip="item.title"
                    class="relative h-9 rounded-sm text-sidebar-foreground/70 transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground data-[active=true]:bg-sidebar-accent data-[active=true]:font-medium data-[active=true]:text-sidebar-accent-foreground"
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
                            v-if="active(item)"
                            class="absolute inset-y-1.5 left-0 w-0.5 rounded-full bg-sidebar-primary"
                            aria-hidden="true"
                        />
                        <component
                            :is="item.icon"
                            :class="active(item) ? 'text-sidebar-primary' : ''"
                        />
                        <span class="group-data-[collapsible=icon]:hidden">{{
                            item.title
                        }}</span>
                    </component>
                </SidebarMenuButton>
                <SidebarMenuBadge
                    v-if="item.badge"
                    class="rounded-sm bg-sidebar-primary px-1.5 label-mono text-sidebar-primary-foreground"
                    :aria-label="`${item.badge} pending`"
                >
                    {{ item.badge }}
                </SidebarMenuBadge>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
