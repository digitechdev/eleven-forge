<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from '@lucide/vue';
import type { Component } from 'vue';
import { ref } from 'vue';
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

const props = withDefaults(
    defineProps<{
        items: NavItem[];
        label?: string;
        /** Show the label as a small heading above the links. */
        showLabel?: boolean;
        /** Fold the group behind a menu-style toggle; it opens on its own when one of its pages is current. */
        collapsible?: boolean;
        /** Icon for the collapsible toggle. */
        icon?: Component;
    }>(),
    { label: 'Platform', showLabel: true, collapsible: false },
);

const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

const storageKey = `sidebar-group:${props.label}`;

function initiallyOpen(): boolean {
    if (!props.collapsible || props.items.some((item) => active(item))) {
        return true;
    }

    try {
        return window.localStorage.getItem(storageKey) === 'open';
    } catch {
        return false;
    }
}

const open = ref(initiallyOpen());

function toggle(): void {
    open.value = !open.value;

    try {
        window.localStorage.setItem(storageKey, open.value ? 'open' : 'closed');
    } catch {
        // Remembering the group state is a convenience only.
    }
}

function active(item: NavItem): boolean {
    if (item.action) {
        return false;
    }

    const matches = (href: NavItem['href']): boolean =>
        item.exact ? isCurrentUrl(href) : isCurrentOrParentUrl(href);

    return matches(item.href) || (item.alsoActiveOn ?? []).some(matches);
}
</script>

<template>
    <SidebarGroup class="px-2 py-1">
        <SidebarGroupLabel
            v-if="showLabel && !collapsible"
            class="text-[11px] font-semibold tracking-wide text-sidebar-foreground/40 uppercase"
        >
            {{ label }}
        </SidebarGroupLabel>
        <SidebarMenu
            v-if="collapsible"
            class="group-data-[collapsible=icon]:hidden"
        >
            <SidebarMenuItem>
                <SidebarMenuButton
                    class="h-9 rounded-lg text-sidebar-foreground/70 transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                    :aria-expanded="open"
                    @click="toggle"
                >
                    <component :is="icon" v-if="icon" />
                    <span>{{ label }}</span>
                    <ChevronRight
                        :class="[
                            'ml-auto size-4 text-sidebar-foreground/40 transition-transform duration-200',
                            open ? 'rotate-90' : '',
                        ]"
                    />
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
        <SidebarMenu
            :class="[
                'gap-0.5',
                collapsible
                    ? 'mt-0.5 ml-3.5 w-auto min-w-0 border-l border-sidebar-border pl-2 group-data-[collapsible=icon]:ml-0 group-data-[collapsible=icon]:border-l-0 group-data-[collapsible=icon]:pl-0'
                    : '',
                collapsible && !open
                    ? 'hidden group-data-[collapsible=icon]:flex'
                    : '',
            ]"
        >
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
