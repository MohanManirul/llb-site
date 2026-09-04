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

export interface SharedProgramRef {
    id: number;
    slug: string;
    name: { bn: string | null; en: string | null };
    short_name: { bn: string | null; en: string | null };
    has_levels: boolean;
    has_exam_stages: boolean;
}

export interface StudentUser {
    id: number;
    name: string;
    email: string;
    phone: string | null;
}

export interface SharedPageProps {
    locale: 'bn' | 'en';
    programs: SharedProgramRef[];
    student: StudentUser | null;
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
