export interface AuthUser {
    id: number;
    name: string;
    email: string;
    roles: string[];
    permissions: string[];
}

export interface Impersonation {
    name: string;
    since: number;
}

export interface SharedPageProps {
    auth: {
        user: AuthUser | null;
    };
    portal: {
        base: string;
    };
    flash: {
        success?: string | null;
        error?: string | null;
    };
    impersonation: Impersonation | null;
}

declare module '@inertiajs/core' {
    interface PageProps extends SharedPageProps {}
}
