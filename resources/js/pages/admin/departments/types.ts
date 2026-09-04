export interface Department {
    id: number;
    company_id: number | string;
    company_name?: string | null;
    company_logo_url?: string | null;
    company_thumbnail_url?: string | null;
    name: string;
    description: string | null;
    is_active: boolean;
    created_at: string;
}
