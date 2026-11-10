/** Badge classes for each build status. */
export const buildOrderStatusTone: Record<string, string> = {
    DRAFT: 'border-transparent bg-muted text-muted-foreground',
    PLANNED: 'border-transparent bg-chart-2/12 text-chart-2',
    IN_PROGRESS: 'border-transparent bg-chart-3/15 text-chart-3',
    COMPLETED: 'border-transparent bg-chart-5/12 text-chart-5',
    CANCELLED: 'border-transparent bg-muted text-muted-foreground line-through',
};

/** A required part of a build order's formula and the stock available at its location. */
export type CompletionLine = {
    inventory_item_id: number;
    item_code: string | null;
    item_name: string | null;
    unit_symbol: string | null;
    quantity_required: number;
    available: number;
    unit_cost: number;
};

export type CompletionPreview = {
    yield_quantity: number;
    lines: CompletionLine[];
};

export type BuildOrderRow = {
    id: number;
    order_number: string;
    url: string;
    innovation: {
        id: number;
        code: string | null;
        name: string | null;
        url: string;
    };
    formula_version: number | null;
    location: string | null;
    planned_quantity: number;
    produced_quantity: number;
    remaining_quantity: number;
    progress: number;
    status: string;
    status_label: string;
    requester: string | null;
    notes: string | null;
    created_at: string | null;
    started_at: string | null;
    completed_at: string | null;
    completion: CompletionPreview | null;
    can: { plan: boolean; start: boolean; cancel: boolean; complete: boolean };
};

/** A production run recorded against a build. */
export type BuildRunRow = {
    id: number;
    run_number: string;
    url: string;
    ledger_url: string;
    output_quantity: number;
    total_input_cost: number;
    unit_production_cost: number;
    completed_at: string;
    completed_by: string | null;
};
