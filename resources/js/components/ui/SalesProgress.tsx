import { ReactNode } from 'react';
import { formatDate, formatMoney } from '../../lib/format';

const PROGRESS_COLORS: Array<{ min: number; color: string }> = [
    { min: 100, color: '#16a34a' },
    { min: 60, color: '#22c55e' },
    { min: 30, color: '#f59e0b' },
    { min: 0, color: '#ef4444' },
];

const TRACK_COLOR = '#e5e7eb';

function colorForPct(pct: number): string {
    return (
        PROGRESS_COLORS.find((step) => pct >= step.min)?.color ??
        PROGRESS_COLORS[PROGRESS_COLORS.length - 1].color
    );
}

interface RingProps {
    pct: number;
    size: number;
    stroke: number;
    children?: ReactNode;
}

function Ring({ pct, size, stroke, children }: RingProps) {
    const radius = (size - stroke) / 2;
    const circumference = 2 * Math.PI * radius;
    const offset = circumference - (pct / 100) * circumference;

    return (
        <div className="relative" style={{ width: size, height: size }}>
            <svg width={size} height={size} className="-rotate-90" aria-hidden="true">
                <circle
                    cx={size / 2}
                    cy={size / 2}
                    r={radius}
                    fill="none"
                    stroke={TRACK_COLOR}
                    strokeWidth={stroke}
                />
                <circle
                    cx={size / 2}
                    cy={size / 2}
                    r={radius}
                    fill="none"
                    stroke={colorForPct(pct)}
                    strokeWidth={stroke}
                    strokeLinecap="round"
                    strokeDasharray={circumference}
                    strokeDashoffset={offset}
                />
            </svg>
            <div className="absolute inset-0 flex flex-col items-center justify-center">
                {children}
            </div>
        </div>
    );
}

export interface SalesProgressProps {
    achieved: number | string | null | undefined;
    target: number | string | null | undefined;
    progress?: number | string | null;
    periodStart?: string | null;
    periodEnd?: string | null;
    label?: string | null;
    size?: 'sm' | 'lg';
    projectType?: string | null;
}

const REGULAR_LABEL = 'Regular Project';

export default function SalesProgress({
    achieved,
    target,
    progress,
    periodStart,
    periodEnd,
    label,
    size = 'sm',
    projectType,
}: SalesProgressProps) {
    const achievedAmount = Number(achieved) || 0;
    const targetAmount = Number(target) || 0;
    const derived = targetAmount > 0 ? (achievedAmount / targetAmount) * 100 : 0;
    const pct = Math.round(
        Math.min(100, Math.max(0, progress === null || progress === undefined ? derived : Number(progress) || 0)),
    );

    const period =
        periodStart && periodEnd ? `${formatDate(periodStart)} – ${formatDate(periodEnd)}` : null;
    const caption = [label, period].filter(Boolean).join(' · ');
    const accessibleLabel = [
        `${pct}% of this month's target`,
        `${formatMoney(achievedAmount)} of ${formatMoney(targetAmount)}`,
        period,
    ]
        .filter(Boolean)
        .join(' — ');

    if (projectType === 'regular') {
        const regularLabel = `${formatMoney(achievedAmount)} — ${REGULAR_LABEL}`;

        if (size === 'lg') {
            return (
                <div className="flex flex-col items-center gap-1" role="img" aria-label={regularLabel}>
                    <span className="text-xl font-bold text-gray-900">
                        {formatMoney(achievedAmount)}
                    </span>
                    <span className="text-[11px] uppercase tracking-wide text-gray-400">
                        {REGULAR_LABEL}
                    </span>
                </div>
            );
        }

        return (
            <div
                className="flex flex-col items-end text-xs"
                role="img"
                aria-label={regularLabel}
                title={regularLabel}
            >
                <span className="font-medium text-gray-700">{formatMoney(achievedAmount)}</span>
                <span className="text-gray-400">/ {REGULAR_LABEL}</span>
            </div>
        );
    }

    if (size === 'lg') {
        return (
            <div className="flex flex-col items-center gap-2" role="img" aria-label={accessibleLabel}>
                <Ring pct={pct} size={120} stroke={10}>
                    <span className="text-xl font-bold text-gray-900">{pct}%</span>
                    <span className="text-[10px] uppercase tracking-wide text-gray-400">
                        This month
                    </span>
                </Ring>
                <div className="text-center text-xs text-gray-500">
                    {formatMoney(achievedAmount)} / {formatMoney(targetAmount)}
                </div>
                {caption && (
                    <div className="text-center text-[11px] text-gray-400">{caption}</div>
                )}
            </div>
        );
    }

    return (
        <div
            className="flex items-center justify-end gap-2"
            role="img"
            aria-label={accessibleLabel}
            title={accessibleLabel}
        >
            <div className="flex flex-col items-end text-xs">
                <span className="font-medium text-gray-700">{formatMoney(achievedAmount)}</span>
                <span className="text-gray-400">/ {formatMoney(targetAmount)}</span>
            </div>
            <Ring pct={pct} size={40} stroke={4}>
                <span className="text-[10px] font-semibold text-gray-700">{pct}%</span>
            </Ring>
        </div>
    );
}
