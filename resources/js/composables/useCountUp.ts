import type { MaybeRefOrGetter } from 'vue';
import { onMounted, ref, toValue, watch } from 'vue';

const NUMERIC = /^-?[\d,]+(\.\d+)?$/;

/**
 * Animates a displayed number from its previous value to the new one.
 * Non-numeric values (e.g. "#3", "—") are shown as-is.
 */
export function useCountUp(
    source: MaybeRefOrGetter<string | number>,
    duration = 900,
) {
    const display = ref(String(toValue(source)));
    let frame = 0;
    let current = 0;

    function parse(
        value: string | number,
    ): { n: number; decimals: number } | null {
        const text = String(value).trim();

        if (!NUMERIC.test(text)) {
            return null;
        }

        return {
            n: Number(text.replace(/,/g, '')),
            decimals: text.split('.')[1]?.length ?? 0,
        };
    }

    function run(): void {
        const target = parse(toValue(source));

        if (
            target === null ||
            window.matchMedia('(prefers-reduced-motion: reduce)').matches
        ) {
            display.value = String(toValue(source));
            current = target?.n ?? 0;

            return;
        }

        const from = current;
        const start = performance.now();
        const format = new Intl.NumberFormat('en-PH', {
            minimumFractionDigits: target.decimals,
            maximumFractionDigits: target.decimals,
        });

        cancelAnimationFrame(frame);
        const tick = (now: number) => {
            const t = Math.min(1, (now - start) / duration);
            const eased = 1 - Math.pow(1 - t, 3);
            current = from + (target.n - from) * eased;
            display.value = format.format(current);

            if (t < 1) {
                frame = requestAnimationFrame(tick);
            } else {
                current = target.n;
                display.value = String(toValue(source));
            }
        };
        frame = requestAnimationFrame(tick);
    }

    onMounted(run);
    watch(() => toValue(source), run);

    return display;
}
