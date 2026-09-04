export interface TeamOption {
    value: string | number;
    label: string;
    description?: string;
    image_url?: string | null;
    thumbnail_url?: string | null;
}

export interface TeamMemberOption {
    value: string | number;
    label: string;
}

export interface TeamRow {
    id: number;
    company_name?: string | null;
    department_name?: string | null;
    name: string;
    leader?: { name?: string | null } | null;
    members?: TeamMemberOption[];
    members_count?: number;
    is_active: boolean;
    created_at?: string;
}

export interface TeamMemberDetail {
    id: number;
    employee_id?: number | string;
    name?: string;
    designation?: string | null;
    image_url?: string | null;
    thumbnail_url?: string | null;
    role: "leader" | "member";
}

export interface TeamDetail {
    id: number;
    company_id?: number | string | null;
    company_name?: string | null;
    department_id?: number | string | null;
    department_name?: string | null;
    name: string;
    description?: string | null;
    is_active: boolean;
    members?: TeamMemberDetail[];
}
