import { useMemo } from 'react';
import { usePage } from '@inertiajs/react';

export interface Permissions {
    can: (permission: string) => boolean;
    isSuperAdmin: boolean;
    roles: string[];
    permissions: string[];
}

export default function usePermissions(): Permissions {
    const user = usePage().props.auth?.user ?? null;

    return useMemo<Permissions>(() => {
        const roles = user?.roles ?? [];
        const permissions = user?.permissions ?? [];
        const isSuperAdmin = roles.includes('super-admin');

        return {
            can: (permission: string) =>
                isSuperAdmin || permissions.includes(permission),
            isSuperAdmin,
            roles,
            permissions,
        };
    }, [user]);
}
