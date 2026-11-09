<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Boxes,
    Code,
    Globe,
    IdCard,
    KeyRound,
    LayoutGrid,
    Layers,
    Lightbulb,
    Ruler,
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
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import administration from '@/routes/administration';
import innovationCategories from '@/routes/innovation-categories';
import innovations from '@/routes/innovations';
import inventory from '@/routes/inventory';
import showcase from '@/routes/showcase';
import technologies from '@/routes/technologies';
import type { NavGroup, NavItem } from '@/types';

const page = usePage();

/**
 * Navigation follows the user's permissions. The server still authorizes every request.
 */
const groups = computed<NavGroup[]>(() => {
    const can = page.props.auth.access?.can;

    if (!can) {
        return [];
    }

    const registry: NavItem[] = [
        ...(can.viewInnovations
            ? [
                  {
                      title: 'Innovations',
                      href: innovations.index(),
                      icon: Lightbulb,
                  },
              ]
            : []),
        ...(can.viewInnovationCategories
            ? [
                  {
                      title: 'Innovation categories',
                      href: innovationCategories.index(),
                      icon: Tag,
                  },
              ]
            : []),
        ...(can.viewTechnologies
            ? [
                  {
                      title: 'Technologies',
                      href: technologies.index(),
                      icon: Code,
                  },
              ]
            : []),
    ];

    const stock: NavItem[] = [
        ...(can.viewInventoryItems
            ? [
                  {
                      title: 'Items',
                      href: inventory.items.index(),
                      icon: Boxes,
                  },
              ]
            : []),
        ...(can.viewItemCategories
            ? [
                  {
                      title: 'Item categories',
                      href: inventory.categories.index(),
                      icon: Layers,
                  },
              ]
            : []),
        ...(can.viewUnits
            ? [
                  {
                      title: 'Units',
                      href: inventory.units.index(),
                      icon: Ruler,
                  },
              ]
            : []),
    ];

    const admin: NavItem[] = [
        ...(can.manageUsers
            ? [
                  {
                      title: 'Users',
                      href: administration.users.index(),
                      icon: Users,
                  },
              ]
            : []),
        ...(can.manageRoles
            ? [
                  {
                      title: 'Roles',
                      href: administration.roles.index(),
                      icon: IdCard,
                  },
                  {
                      title: 'Permissions',
                      href: administration.permissions.index(),
                      icon: KeyRound,
                  },
              ]
            : []),
    ];

    return [
        {
            label: 'Overview',
            items: [
                {
                    title: 'Dashboard',
                    href: dashboard(),
                    icon: LayoutGrid,
                    exact: true,
                },
                { title: 'Showcase', href: showcase.index(), icon: Globe },
            ],
        },
        { label: 'Registry', items: registry },
        { label: 'Inventory', items: stock },
        { label: 'Administration', items: admin },
    ].filter((g) => g.items.length > 0);
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
            class="[scrollbar-width:thin] [scrollbar-color:var(--sidebar-border)_transparent]"
        >
            <nav class="flex flex-col gap-1 py-3" aria-label="Main">
                <NavMain
                    v-for="group in groups"
                    :key="group.label"
                    :label="group.label"
                    :items="group.items"
                />
            </nav>
        </SidebarContent>

        <SidebarFooter class="border-t border-sidebar-border">
            <p
                class="px-2 py-1.5 label-mono text-sidebar-foreground/40 group-data-[collapsible=icon]:hidden"
            >
                Eleven Forge · Registry
            </p>
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
