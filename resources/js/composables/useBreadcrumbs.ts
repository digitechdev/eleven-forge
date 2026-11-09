import { setLayoutProps } from '@inertiajs/vue3';
import type { MaybeRefOrGetter } from 'vue';
import { toValue, watchEffect } from 'vue';
import type { BreadcrumbItem } from '@/types';

/**
 * The one way pages set the breadcrumb trail shown in the header.
 *
 * Accepts a getter so trails with record names stay in sync after edits.
 * Inertia clears layout props on every page visit, so pages never inherit a stale trail.
 */
export function useBreadcrumbs(
    items: MaybeRefOrGetter<BreadcrumbItem[]>,
): void {
    watchEffect(() =>
        setLayoutProps<{ breadcrumbs: BreadcrumbItem[] }>({
            breadcrumbs: toValue(items),
        }),
    );
}
