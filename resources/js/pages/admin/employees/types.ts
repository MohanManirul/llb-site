export interface EmployeeOption {
    value: string | number;
    label: string;
    description?: string;
    image?: string | null;
}

export interface UserOption extends EmployeeOption {
    phone?: string | null;
}

export interface Employee {
    id: number;
    user_id: number;
    company_id?: number | string | null;
    company_name?: string | null;
    department_id?: number | string | null;
    department_name?: string | null;
    designation_id?: number | string | null;
    designation?: string | null;
    name: string;
    email: string;
    phone?: string | null;
    description?: string | null;
    image_url?: string | null;
    thumbnail_url?: string | null;
    joining_date?: string | null;
    resignation_date?: string | null;
    is_active: boolean;
    created_at?: string;
}
