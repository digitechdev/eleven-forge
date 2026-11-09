const numberFormat = new Intl.NumberFormat('en-PH', {
    maximumFractionDigits: 2,
});
const integerFormat = new Intl.NumberFormat('en-PH', {
    maximumFractionDigits: 0,
});

export function formatNumber(
    value: number | null | undefined,
    decimals = 2,
): string {
    if (value === null || value === undefined) {
        return '—';
    }

    return decimals === 0
        ? integerFormat.format(value)
        : numberFormat.format(value);
}

/** "1,234.5 kg" */
export function formatKg(
    value: number | null | undefined,
    decimals = 2,
): string {
    return value === null || value === undefined
        ? '—'
        : `${formatNumber(value, decimals)} kg`;
}

/** Signed weight, e.g. "+12 kg" / "−20 kg". */
export function formatSignedKg(value: number | null | undefined): string {
    if (value === null || value === undefined) {
        return '—';
    }

    if (value === 0) {
        return '0 kg';
    }

    return `${value > 0 ? '+' : '−'}${formatNumber(Math.abs(value))} kg`;
}

export function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    // Plain dates ("2026-11-06") are calendar dates, not instants: avoid timezone shifts.
    const date = /^\d{4}-\d{2}-\d{2}$/.test(value)
        ? new Date(`${value}T00:00:00`)
        : new Date(value);

    return date.toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

export function formatDateTime(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}

export function formatMonthRange(
    start: string | null,
    end: string | null,
): string {
    const fmt = (v: string) =>
        new Date(`${v}T00:00:00`).toLocaleDateString('en-PH', {
            month: 'short',
            year: 'numeric',
        });

    if (!start || !end) {
        return start ? fmt(start) : end ? fmt(end) : '—';
    }

    const [a, b] = [fmt(start), fmt(end)];

    return a === b ? a : `${a} – ${b}`;
}

export function formatBytes(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(0)} KB`;
    }

    return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
}

export function todayIso(): string {
    const d = new Date();

    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}
