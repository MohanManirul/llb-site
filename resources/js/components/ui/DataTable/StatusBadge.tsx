import {
    BadgeTone,
    badgeBaseClasses,
    badgeStyle,
    badgeToneClasses,
    isHexColor,
} from '../tokens';

const SOLID_TONES: Record<BadgeTone, string> = {
    green: 'bg-emerald-500 text-white',
    yellow: 'bg-amber-500 text-white',
    red: 'bg-red-500 text-white',
    gray: 'bg-gray-500 text-white',
    blue: 'bg-blue-500 text-white',
    indigo: 'bg-brand-accent text-white',
    purple: 'bg-purple-500 text-white',
};

const STATUS_TONES: Record<string, BadgeTone> = {
    paid: 'green',
    active: 'green',
    completed: 'green',
    approved: 'green',
    pending: 'yellow',
    processing: 'yellow',
    cancelled: 'red',
    failed: 'red',
    rejected: 'red',
    inactive: 'gray',
    draft: 'gray',
    archived: 'gray',
};

export type StatusBadgeVariant = 'soft' | 'solid';

export interface StatusBadgeProps {
    status?: string;
    tone?: BadgeTone;
    color?: string;
    variant?: StatusBadgeVariant;
    className?: string;
}

export default function StatusBadge({
    status,
    tone,
    color,
    variant = 'soft',
    className = '',
}: StatusBadgeProps) {
    if (isHexColor(color)) {
        return (
            <span
                className={`${badgeBaseClasses} border ${className}`}
                style={badgeStyle(color)}
            >
                {status}
            </span>
        );
    }

    const resolvedTone =
        tone ?? STATUS_TONES[status?.toLowerCase() ?? ''] ?? 'gray';

    const toneClasses =
        variant === 'solid'
            ? SOLID_TONES[resolvedTone]
            : `${badgeToneClasses[resolvedTone]} ring-1 ring-inset`;

    return (
        <span className={`${badgeBaseClasses} ${toneClasses} ${className}`}>
            {status}
        </span>
    );
}
