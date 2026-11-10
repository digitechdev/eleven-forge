import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from '@lucide/vue';

export type BreadcrumbItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
    /** Count shown beside the item, e.g. pending reviews. */
    badge?: number;
    /** Only highlight on an exact URL match (otherwise child pages also match). */
    exact?: boolean;
    /** Other pages that belong to this entry, e.g. Permissions under "Roles & permissions". */
    alsoActiveOn?: NonNullable<InertiaLinkProps['href']>[];
    /** Run an action (e.g. open a modal) instead of navigating. */
    action?: () => void;
};

export type NavGroup = {
    label: string;
    items: NavItem[];
    /** Show the label as a heading; off for the main list, which needs none. */
    showLabel?: boolean;
    /** Fold the group behind a menu-style toggle with this icon. */
    collapsible?: boolean;
    icon?: LucideIcon;
};
