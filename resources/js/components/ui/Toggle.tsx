import type { ReactNode } from 'react';

export interface ToggleProps {
    checked: boolean;
    onChange: (checked: boolean) => void;
    label?: ReactNode;
    disabled?: boolean;
    ariaLabel?: string;
}

export default function Toggle({
    checked,
    onChange,
    label,
    disabled = false,
    ariaLabel,
}: ToggleProps) {
    const track = checked ? 'bg-brand-accent' : 'bg-gray-200';
    const knob = checked ? 'translate-x-4' : 'translate-x-0.5';

    return (
        <span
            className={
                'inline-flex items-center gap-2 text-sm text-gray-600 ' +
                (disabled ? 'opacity-60' : '')
            }
        >
            {label}

            <button
                type="button"
                role="switch"
                aria-checked={checked}
                aria-label={ariaLabel}
                disabled={disabled}
                onClick={() => onChange(!checked)}
                className={
                    'relative inline-flex h-5 w-9 shrink-0 rounded-full transition ' +
                    'focus:outline-none focus:ring-2 focus:ring-brand-accent/30 ' +
                    'disabled:cursor-not-allowed ' +
                    (disabled ? '' : 'cursor-pointer ') +
                    track
                }
            >
                <span
                    className={
                        'my-auto h-4 w-4 rounded-full bg-white shadow transition ' + knob
                    }
                />
            </button>
        </span>
    );
}
