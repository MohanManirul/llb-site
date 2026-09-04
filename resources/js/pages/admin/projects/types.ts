import type { ReactNode } from 'react';

export type { BusinessStatusOption } from '@/config/businessStatus';

export interface PickerOption {
    value: number | string;
    label: string;
    description?: string | null;
    image_url?: string | null;
    thumbnail_url?: string | null;
}

export interface ClientPickerOption extends PickerOption {
    email?: string | null;
    phone?: string | null;
}

export interface ProjectClient {
    id: number;
    name: string;
    email?: string | null;
    phone?: string | null;
}

export interface ProjectEmployee {
    id: number;
    name: string;
    designation?: string | null;
    image_url?: string | null;
    thumbnail_url?: string | null;
    email?: string | null;
    phone?: string | null;
}

export interface ProjectMilestone {
    id: number;
    sequence: number;
    period_start?: string | null;
    period_end?: string | null;
    target_amount?: number | string | null;
    achieved_amount?: number | string | null;
    status?: string | null;
    status_label?: string | null;
}

export interface ProjectSale {
    id: number;
    sale_date?: string | null;
    amount?: number | string | null;
    reference?: string | null;
    employee?: string | null;
}

export interface ProjectPayment {
    id: number;
    payment_date?: string | null;
    amount?: number | string | null;
    payment_type?: string | null;
    reference_number?: string | null;
    notes?: string | null;
    created_by?: string | null;
}

export interface ProjectAssignment {
    id: number;
    team?: string | null;
    employee?: string | null;
    assigned_at?: string | null;
    unassigned_at?: string | null;
    reason?: string | null;
}

export interface ProjectInvoice {
    id: number;
    invoice_number: string;
    invoice_date?: string | null;
    total_amount?: number | string | null;
    paid_amount?: number | string | null;
    due_amount?: number | string | null;
    due_date?: string | null;
    email_sent_to?: string | null;
    email_sent_at?: string | null;
}

export interface MilestoneProgress {
    id?: number | null;
    sequence?: number | null;
    label?: string | null;
    period_start?: string | null;
    period_end?: string | null;
    target?: number | string | null;
    achieved?: number | string | null;
    progress?: number | string | null;
    raw_progress?: number | string | null;
    started?: boolean;
    health_status?: string | null;
    health_label?: string | null;
    health_color?: string | null;
}

export interface ProjectListRow {
    id: number;
    project_name?: string | null;
    business_name: string;
    client?: string | null;
    client_email?: string | null;
    client_phone?: string | null;
    company?: string | null;
    department?: string | null;
    team?: string | null;
    assigned_employee?: string | null;
    assigned_employee_email?: string | null;
    assigned_employee_phone?: string | null;
    package_amount?: number | string | null;
    amount_due?: number | string | null;
    achieved_sales?: number | string | null;
    sales_target?: number | string | null;
    monthly_target?: number | string | null;
    health_color?: string | null;
    health_label?: string | null;
    project_type?: string | null;
    project_type_label?: string | null;
    business_status?: string | null;
    business_status_label?: string | null;
    business_status_color?: string | null;
    end_date?: string | null;
    can_edit?: boolean;
    can_delete?: boolean;
    can_view_notes?: boolean;
    can_add_notes?: boolean;
    can_view_reports?: boolean;
    can_submit_reports?: boolean;
}

export interface ProjectColumn {
    key: string;
    header: string;
    className?: string;
    sortable?: boolean;
    render: (row: ProjectListRow) => ReactNode;
}

export interface PendingStatus {
    project: ProjectListRow;
    value: number | string;
    label: string;
}

export interface ProjectFilters {
    companyId: string;
    departmentId: string;
    teamId: string;
    healthStatus: string;
    businessStatusId: string;
}

export interface ProjectFilterOptions {
    company: PickerOption | null;
    department: PickerOption | null;
    team: PickerOption | null;
}

export interface ProjectFilterState {
    filters: ProjectFilters;
    options: ProjectFilterOptions;
}

export interface ProjectDetail {
    id: number;
    company_id?: number | string | null;
    department_id?: number | string | null;
    team_id?: number | string | null;
    assigned_employee_id?: number | string | null;
    client_id?: number | string | null;
    client?: ProjectClient | null;

    project_name?: string | null;
    business_name: string;
    website_url?: string | null;
    description?: string | null;

    start_date?: string | null;
    contract_months?: number | null;
    contract_days?: number | null;
    end_date?: string | null;
    next_payment_date?: string | null;

    contact_person?: string | null;
    contact_email?: string | null;
    contact_phone?: string | null;

    package_amount?: number | string | null;
    amount_paid?: number | string | null;
    amount_due?: number | string | null;

    project_type?: string;
    project_type_label?: string | null;
    sales_target?: number | string | null;
    monthly_target?: number | string | null;
    achieved_sales?: number | string | null;
    total_achieved_sales?: number | string | null;
    milestone?: MilestoneProgress | null;
    target_start_date?: string | null;
    target_months?: number | null;
    target_days?: number | null;
    target_deadline?: string | null;

    business_status?: string | null;
    business_status_label?: string | null;
    business_status_color?: string | null;
    health_color?: string | null;
    health_label?: string | null;

    company?: string | null;
    department?: string | null;
    team?: string | null;
    assigned_employee?: ProjectEmployee | null;

    milestones?: ProjectMilestone[];
    sales?: ProjectSale[];
    assignments?: ProjectAssignment[];
    payments?: ProjectPayment[];
    invoices?: ProjectInvoice[];

    can_edit?: boolean;
    can_delete?: boolean;
    can_view_notes?: boolean;
    can_add_notes?: boolean;
    can_view_reports?: boolean;
    can_submit_reports?: boolean;
}

export interface ProjectNote {
    id: number;
    note: string;
    author?: string | null;
    created_at?: string | null;
}

export interface SalesReport {
    id?: number;
    week_start: string;
    week_end: string;
    total_sales: number | string;
    total_order_quantity: number | string;
    total_amount_spent: number | string;
    description?: string | null;
}
