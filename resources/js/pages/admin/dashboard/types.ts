import type { ReactNode } from 'react';

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

export interface DashboardRange {
    from?: string | null;
    to?: string | null;
    scoped?: boolean;
    label?: string;
}

export interface ActivityEntry {
    id: number | string;
    description: string;
    causer?: string | null;
    at?: string | null;
}

export interface DashboardReport {
    activity?: ActivityEntry[];
    heading?: string;
    cards?: DashboardCard[];
    range?: DashboardRange;
}
