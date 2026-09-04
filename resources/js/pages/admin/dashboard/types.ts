export type { BusinessStatusOption } from '@/config/businessStatus';

import type { ReactNode } from 'react';

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
    business_name?: string;
    project_name?: string;
    client?: string;
    client_email?: string;
    client_phone?: string;
    company?: string;
    department?: string;
    team?: string;
    assigned_employee?: string;
    assigned_employee_email?: string;
    assigned_employee_phone?: string;
    package_amount?: number | string;
    amount_due?: number | string;
    achieved_sales?: number;
    sales_target?: number;
    monthly_target?: number;
    health_status?: string;
    health_color?: string;
    health_label?: string;
    project_type?: string;
    project_type_label?: string;
    business_status?: string;
    end_date?: string;
}

export interface ColumnDef<T> {
    key: string;
    header: string;
    className?: string;
    sortable?: boolean;
    render?: (row: T) => ReactNode;
}

export interface DashboardCard {
    label: string;
    value: string | number;
    icon?: string;
    color?: string;
}

export interface RecentColumn {
    key: string;
    header: string;
}

export interface RecentRow {
    id?: number | string;
    href?: string;
    [key: string]: unknown;
}

export interface RecentTable {
    title: string;
    columns: RecentColumn[];
    rows: RecentRow[];
}

export interface TeamProjectRow {
    id: number | string;
    href?: string;
    project: string;
    client?: string;
    package?: string;
    due?: string;
    status?: string;
    health?: string;
    ends?: string;
}

export interface TeamMember {
    id: number | string;
    name: string;
    designation?: string;
    role: string;
    email?: string;
    phone?: string;
    projects_count?: number;
    projects?: TeamProjectRow[];
}

export interface UnassignedProjectRow {
    id: number | string;
    href?: string;
    business: string;
    client?: string;
    package?: string;
    due?: string;
    status?: string;
}

export interface DashboardTeam {
    team: { id: number | string; name: string };
    members?: TeamMember[];
    unassigned_projects?: UnassignedProjectRow[];
}

export interface DashboardRange {
    from?: string | null;
    to?: string | null;
    scoped?: boolean;
    label?: string;
}

export interface TrendPoint {
    date: string;
    sales: number;
    spend: number;
    orders: number;
}

export interface FinanceCard {
    label: string;
    value: string;
    raw: number;
    icon?: string;
    color?: string;
}

export interface TopProjectRow {
    id: number | string;
    href: string;
    name: string;
    client?: string | null;
    achieved: string;
    target: string;
    percent: number;
}

export interface TopProjects {
    risk: TopProjectRow[];
    performing: TopProjectRow[];
}

export interface DistributionSlice {
    key: string;
    label: string;
    color: string;
    count: number;
}

export interface DashboardDistributions {
    health: DistributionSlice[];
    business_status: DistributionSlice[];
}

export interface ActivityEntry {
    id: number | string;
    description: string;
    causer?: string | null;
    at?: string | null;
}

export interface DashboardReport {
    finance?: FinanceCard[] | null;
    trend?: TrendPoint[];
    top_projects?: TopProjects;
    distributions?: DashboardDistributions;
    activity?: ActivityEntry[];
    heading?: string;
    cards?: DashboardCard[];
    recent?: RecentTable;
    teams?: DashboardTeam[];
    range?: DashboardRange;
}
