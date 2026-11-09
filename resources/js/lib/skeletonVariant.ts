import type { SkeletonPageVariant } from '@/components/skeletons/SkeletonPage.vue';

/**
 * Guess the shape of the page at a URL so the navigation skeleton resembles what is about to load.
 */
export function skeletonVariantFor(pathname: string): SkeletonPageVariant {
    const path = pathname.replace(/\/+$/, '') || '/';

    if (path === '/dashboard') {
        return 'dashboard';
    }

    if (/\/(create|edit)$/.test(path) || path.startsWith('/settings')) {
        return 'form';
    }

    return 'list';
}
