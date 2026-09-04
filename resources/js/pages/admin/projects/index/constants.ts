export const HEALTH_BADGE: Record<string, string> = {
    green: 'bg-green-100 text-green-700',
    yellow: 'bg-yellow-100 text-yellow-700',
    red: 'bg-red-100 text-red-700',
    gray: 'bg-gray-100 text-gray-600',
};

export const HEALTH_OPTIONS: { value: string; label: string }[] = [
    { value: 'upcoming', label: 'Upcoming' },
    { value: 'on_track', label: 'On Track' },
    { value: 'at_risk', label: 'At Risk' },
    { value: 'off_track', label: 'Off Track' },
];

export const DEFAULT_HIDDEN_COLUMNS = ['company', 'department'];

export const HIDDEN_COLUMNS_KEY = 'datatable:projects:hidden_columns:v2';
