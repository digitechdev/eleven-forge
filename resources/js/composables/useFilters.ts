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
            const query: Record<string, string | number> = Object.fromEntries(
                Object.entries(filters).filter(
                    (entry): entry is [string, string | number] =>
                        entry[1] !== null &&
                        entry[1] !== undefined &&
                        entry[1] !== '',
                ),
            );

            // Keep the chosen page size; changing a filter starts again from page 1.
            const perPage = new URLSearchParams(window.location.search).get(
                'per_page',
            );

            if (perPage) {
                query.per_page = perPage;
            }

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
