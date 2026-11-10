export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

/** What the signed-in user may do; shapes navigation only, the server still authorizes. */
export type Access = {
    role: string | null;
    can: {
        viewInnovations: boolean;
        viewInnovationCategories: boolean;
        viewInventoryItems: boolean;
        viewItemCategories: boolean;
        viewUnits: boolean;
        viewStock: boolean;
        viewLocations: boolean;
        viewBuildOrders: boolean;
        viewProductionRuns: boolean;
        viewTechnologies: boolean;
        manageUsers: boolean;
        manageRoles: boolean;
    };
};

export type Auth = {
    user: User;
    access: Access | null;
};

export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
