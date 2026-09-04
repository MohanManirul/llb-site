export interface BusinessStatusOption {
    value: string;
    label: string;
    color: string;
}

export const BUSINESS_STATUS_OPTIONS: BusinessStatusOption[] = [
    { value: 'business_setup', label: 'Business Setup', color: '#6366F1' },
    { value: 'campaign_running', label: 'Campaign Running', color: '#10B981' },
    { value: 'campaign_off', label: 'Campaign Off', color: '#F59E0B' },
    { value: 'on_hold', label: 'On Hold', color: '#64748B' },
    { value: 'completed', label: 'Completed', color: '#3B82F6' },
    { value: 'closed', label: 'Closed', color: '#EF4444' },
];

export function businessStatusLabel(value?: string | null): string {
    return BUSINESS_STATUS_OPTIONS.find((o) => o.value === value)?.label ?? '—';
}

export function businessStatusColor(value?: string | null): string {
    return BUSINESS_STATUS_OPTIONS.find((o) => o.value === value)?.color ?? '#6B7280';
}

export function businessStatusBadgeStyle(value?: string | null) {
    const color = businessStatusColor(value);

    return {
        color,
        backgroundColor: `${color}1A`,
        borderColor: `${color}33`,
    };
}
