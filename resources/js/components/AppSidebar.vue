<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    BookOpen,
    Boxes,
    Code,
    Hammer,
    IdCard,
    Layers,
    LayoutGrid,
    Lightbulb,
    MapPin,
    Ruler,
    Settings2,
    ShieldCheck,
    Tag,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import SidebarBrand from '@/components/brand/SidebarBrand.vue';
import NavMain from '@/components/NavMain.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import administration from '@/routes/administration';
import innovationCategories from '@/routes/innovation-categories';
import innovations from '@/routes/innovations';
import inventory from '@/routes/inventory';
import production from '@/routes/production';
import showcase from '@/routes/showcase';
import technologies from '@/routes/technologies';
import type { NavGroup, NavItem } from '@/types';

const page = usePage();

/** Keep only the entries the user's role allows. The server still authorizes every request. */
function allowed(entries: [boolean | undefined, NavItem][]): NavItem[] {
    return entries.filter(([can]) => can).map(([, item]) => item);
}

/**
 * Everyday work at the top, with no headings; rarely changed lists fold away at the bottom.
 */
const groups = computed<{ main: NavItem[]; bottom: NavGroup[] }>(() => {
    const can = page.props.auth.access?.can;

    if (!can) {
        return { main: [], bottom: [] };
    }

    const main = allowed([
        [
            true,
            {
                title: 'Dashboard',
                href: dashboard(),
                icon: LayoutGrid,
                exact: true,
            },
        ],
        [
            can.viewInnovations,
            {
                title: 'Innovations',
                href: innovations.index(),
                icon: Lightbulb,
            },
        ],
        [
            can.viewInventoryItems,
            { title: 'Items', href: inventory.items.index(), icon: Boxes },
        ],
        [
            can.viewStock,
            {
                title: 'Stock movements',
                href: inventory.ledger.index(),
                icon: BookOpen,
            },
        ],
        [
            can.viewBuildOrders,
            {
                title: 'Builds',
                href: production.buildOrders.index(),
                icon: Hammer,
            },
        ],
    ]);

    const setup = allowed([
        [
            can.viewLocations,
            {
                title: 'Locations',
                href: inventory.locations.index(),
                icon: MapPin,
            },
        ],
        [
            can.viewUnits,
            { title: 'Units', href: inventory.units.index(), icon: Ruler },
        ],
        [
            can.viewItemCategories,
            {
                title: 'Item categories',
                href: inventory.categories.index(),
                icon: Layers,
            },
        ],
        [
            can.viewInnovationCategories,
            {
                title: 'Innovation categories',
                href: innovationCategories.index(),
                icon: Tag,
            },
        ],
        [
            can.viewTechnologies,
            {
                title: 'Technologies',
                href: technologies.index(),
                icon: Code,
            },
        ],
    ]);

    const admin = allowed([
        [
            can.manageUsers,
            {
                title: 'Users',
                href: administration.users.index(),
                icon: Users,
            },
        ],
        [
            can.manageRoles,
            {
                title: 'Roles & permissions',
                href: administration.roles.index(),
                icon: IdCard,
                alsoActiveOn: [administration.permissions.index()],
            },
        ],
    ]);

    return {
        main,
        bottom: [
            {
                label: 'Setup',
                items: setup,
                collapsible: true,
                icon: Settings2,
            },
            {
                label: 'Administration',
                items: admin,
                collapsible: true,
                icon: ShieldCheck,
            },
        ].filter((group) => group.items.length > 0),
    };
});
</script>

<template>
    <Sidebar collapsible="icon" variant="sidebar">
        <SidebarHeader class="border-b border-sidebar-border">
            <Link
                :href="dashboard()"
                aria-label="Dashboard"
                class="rounded-sm outline-none focus-visible:ring-2 focus-visible:ring-sidebar-ring"
            >
                <SidebarBrand />
            </Link>
        </SidebarHeader>

        <SidebarContent
            class="[scrollbar-width:thin] [scrollbar-color:var(--sidebar-border)_transparent] overflow-x-hidden"
        >
            <nav class="flex flex-1 flex-col py-3" aria-label="Main">
                <NavMain
                    label="Main"
                    :show-label="false"
                    :items="groups.main"
                />

                <div
                    v-if="groups.bottom.length"
                    class="mt-auto border-t border-sidebar-border pt-2"
                >
                    <NavMain
                        v-for="group in groups.bottom"
                        :key="group.label"
                        :label="group.label"
                        :items="group.items"
                        :collapsible="group.collapsible"
                        :icon="group.icon"
                    />
                </div>
            </nav>
        </SidebarContent>

        <SidebarFooter class="border-t border-sidebar-border">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton
                        as-child
                        tooltip="Public showcase"
                        class="h-9 rounded-lg text-sidebar-foreground/60 transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                    >
                        <Link :href="showcase.index()">
                            <ArrowUpRight />
                            <span>Public showcase</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
