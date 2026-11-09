import { router } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { reactive } from 'vue';

type FilterValue = string | number | null | undefined;

/**
 * Reactive list filters that reload the current page (keeping state) whenever they change.
 */
export function useFilters<T extends Record<string, FilterValue>>(
    initial: T,
    url: () => string,
) {
    const filters = reactive({ ...initial }) as T;

    watchDebounced(
        filters,
        () => {
            const query = Object.fromEntries(
                Object.entries(filters).filter(
                    ([, v]) => v !== null && v !== undefined && v !== '',
                ),
            );

            router.get(url(), query, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
        },
        { debounce: 300, deep: true },
    );

    function reset(): void {
        for (const key of Object.keys(filters)) {
            (filters as Record<string, FilterValue>)[key] = null;
        }
    }

    return { filters, reset };
}
