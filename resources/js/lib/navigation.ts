import { ref } from 'vue';

/**
 * The path of a page visit that is still loading, if any.
 * Lets the sidebar highlight the destination immediately instead of after the response arrives.
 */
export const pendingPath = ref<string | null>(null);
