import type { Directive } from 'vue';

const STAGGER_MS = 60;
const MAX_STAGGER_STEPS = 6;

let observer: IntersectionObserver | null = null;

function getObserver(): IntersectionObserver {
    observer ??= new IntersectionObserver(
        (entries) => {
            for (const entry of entries) {
                if (entry.isIntersecting) {
                    entry.target.setAttribute('data-reveal', 'visible');
                    observer?.unobserve(entry.target);
                }
            }
        },
        { rootMargin: '0px 0px -40px 0px', threshold: 0.05 },
    );

    return observer;
}

/**
 * `v-reveal`: fades an element up the first time it scrolls into view.
 * Siblings that also use v-reveal are staggered automatically (e.g. a row of stat cards).
 * Pass a number to set the delay yourself: `v-reveal="120"`.
 */
export const vReveal: Directive<HTMLElement, number | undefined> = {
    mounted(el, binding) {
        if (
            typeof IntersectionObserver === 'undefined' ||
            window.matchMedia('(prefers-reduced-motion: reduce)').matches
        ) {
            return;
        }

        const siblings = el.parentElement
            ? Array.from(el.parentElement.children).filter(
                  (c) => c.hasAttribute('data-reveal') || c === el,
              )
            : [el];
        const index = Math.min(siblings.indexOf(el), MAX_STAGGER_STEPS);

        el.style.setProperty(
            '--reveal-delay',
            `${binding.value ?? Math.max(index, 0) * STAGGER_MS}ms`,
        );
        el.setAttribute('data-reveal', 'hidden');
        getObserver().observe(el);
    },
    unmounted(el) {
        observer?.unobserve(el);
    },
};
