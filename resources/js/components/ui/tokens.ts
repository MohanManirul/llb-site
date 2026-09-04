import type { CSSProperties } from 'react';

export type ControlSize = 'sm' | 'md' | 'lg';
export type Tone = 'default' | 'danger' | 'muted';

export const controlSizeClasses: Record<ControlSize, string> = {
    sm: 'py-1.5 text-xs',
    md: 'py-2 text-sm',
    lg: 'py-2.5 text-base',
};

export const textSizeClasses: Record<ControlSize, string> = {
    sm: 'text-xs',
    md: 'text-sm',
    lg: 'text-base',
};

export const controlBaseClasses =
    'w-full rounded-control border bg-field text-[14px] text-[#262626] transition ' +
    'disabled:cursor-not-allowed disabled:border-gray-300 disabled:bg-[#f0f0f0] ' +
    'disabled:text-[#999999] disabled:opacity-65';

export const focusRingClasses =
    'focus:outline-none focus:ring-2 focus:ring-brand-accent/30 focus:border-brand-accent';

export const focusRingErrorClasses =
    'focus:outline-none focus:ring-2 focus:ring-red-200 focus:border-red-500';

export function fieldStateClasses(error?: string): string {
    return error
        ? `border-red-500 ${focusRingErrorClasses}`
        : `border-black/20 ${focusRingClasses}`;
}

export function controlClasses(size: ControlSize = 'md', error?: string): string {
    return `${controlBaseClasses} ${controlSizeClasses[size]} ${fieldStateClasses(error)}`;
}

export const labelClasses = 'mb-2 block text-[13px] font-medium text-ink';
export const requiredMarkClasses = 'text-red-600';
export const hintClasses = 'mt-1 text-xs text-gray-400';
export const errorTextClasses = 'mt-1 text-xs text-red-600';

export const toneTextClasses: Record<Tone, string> = {
    default: 'text-brand-accent hover:text-brand focus:ring-brand-accent/30',
    danger: 'text-red-600 hover:text-red-700 focus:ring-red-200',
    muted: 'text-gray-500 hover:text-gray-700 focus:ring-gray-200',
};

export const badgeToneClasses = {
    green: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
    yellow: 'bg-amber-50 text-amber-700 ring-amber-600/20',
    red: 'bg-red-50 text-red-700 ring-red-600/20',
    gray: 'bg-gray-50 text-gray-600 ring-gray-500/20',
    blue: 'bg-blue-50 text-blue-700 ring-blue-600/20',
    indigo: 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
    purple: 'bg-purple-50 text-purple-700 ring-purple-600/20',
};

export type BadgeTone = keyof typeof badgeToneClasses;

const HEX = /^#[0-9A-Fa-f]{6}$/;

export function isHexColor(value?: string): boolean {
    return !!value && HEX.test(value);
}

export function badgeStyle(color?: string): CSSProperties {
    if (!isHexColor(color)) {
        return {
            backgroundColor: 'transparent',
            borderColor: '#E5E7EB',
            color: '#9CA3AF',
        };
    }

    return {
        backgroundColor: color + '1A',
        borderColor: color + '33',
        color,
    };
}

export const badgeBaseClasses =
    'inline-flex items-center justify-center rounded-full px-2.5 py-1 text-[10px] font-medium capitalize';

export const popoverPanelClasses =
    'absolute z-20 mt-2 rounded-control border border-gray-200 bg-white shadow-lg';

export const overlayClasses = 'fixed inset-0 z-10';
