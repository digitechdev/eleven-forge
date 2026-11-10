export type Option<T extends string | number = string> = {
    value: T;
    label: string;
};

export type InnovationFormOptions = {
    categories: Option<number>[];
    types: Option[];
    statuses: Option[];
    visibilities: Option[];
};

/** The editable fields shared by the create and edit forms. */
export type InnovationFields = {
    innovation_code: string | null;
    name: string;
    slug: string | null;
    category_id: number | null;
    type: string;
    tagline: string | null;
    short_description: string | null;
    description: string | null;
    problem_statement: string | null;
    solution_description: string | null;
    key_features: string[];
    benefits: string[];
    status: string;
    visibility: string;
    developed_at: string | null;
    is_featured: boolean;
};

export type BomLine = {
    id: number;
    inventory_item_id: number;
    item_code: string | null;
    item_name: string | null;
    item_url: string | null;
    unit_id: number;
    unit_symbol: string | null;
    quantity_required: number;
    unit_cost: number | null;
    estimated_cost: number | null;
    usage_description: string | null;
    specification_notes: string | null;
    is_optional: boolean;
    sort_order: number;
};

export type TechnologyLink = {
    id: number;
    technology_id: number;
    name: string | null;
    category: string | null;
    version: string | null;
    purpose: string | null;
};

export type MediaEntry = {
    id: number;
    media_type: 'IMAGE' | 'VIDEO';
    url: string | null;
    video_url: string | null;
    title: string | null;
    caption: string | null;
    alt_text: string | null;
    is_cover: boolean;
    is_public: boolean;
    sort_order: number;
};

export type InventoryItemOption = Option<number> & {
    item_code: string;
    default_unit_id: number;
    unit_cost: number | null;
};

export type DetailOptions = {
    inventoryItems: InventoryItemOption[];
    units: Option<number>[];
    technologies: Option<number>[];
    mediaTypes: Option[];
};

/** Badge classes for each lifecycle status. */
export const statusTone: Record<string, string> = {
    CONCEPT: 'border-transparent bg-muted text-muted-foreground',
    DEVELOPMENT: 'border-transparent bg-chart-2/12 text-chart-2',
    PROTOTYPE: 'border-transparent bg-chart-3/15 text-chart-3',
    TESTING: 'border-transparent bg-chart-3/15 text-chart-3',
    COMPLETED: 'border-transparent bg-chart-5/12 text-chart-5',
    DEPLOYED: 'border-transparent bg-chart-1/12 text-chart-1',
    RETIRED: 'border-transparent bg-muted text-muted-foreground line-through',
};
