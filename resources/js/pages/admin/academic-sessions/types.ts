export interface AcademicSession {
    id: number;
    slug: string;
    label: string;
    start_year: number;
    end_year: number;
    is_current: boolean;
    is_active: boolean;
    sort_order: number;
    created_at: string;
}

export interface SessionFormData {
    label: string;
    is_current: boolean;
    is_active: boolean;
}
