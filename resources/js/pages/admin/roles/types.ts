export interface Role {
    id: number;
    name: string;
    protected: boolean;
    permissions: string[];
    permissions_count?: number;
    users_count?: number;
}

export interface PermissionGroup {
    module: string;
    permissions: string[];
}
