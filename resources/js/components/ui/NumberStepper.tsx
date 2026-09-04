import { ReactNode } from 'react';
import { MinusIcon, PlusIcon } from '@heroicons/react/24/outline';
import Field, { useFieldIds } from './Field';
import { ControlSize, controlClasses } from './tokens';

export interface NumberStepperProps {
    label?: ReactNode;
    name?: string;
    id?: string;
    value: string | number;
    onChange: (value: string) => void;
    min?: number;
    max?: number;
    step?: number;
    error?: string;
    hint?: ReactNode;
    required?: boolean;
    disabled?: boolean;
    size?: ControlSize;
    className?: string;
}

const stepperButtonClasses =
    'flex h-6 w-6 items-center justify-center rounded-md border border-gray-200 ' +
    'text-gray-500 transition hover:border-brand-accent/40 hover:text-brand-accent ' +
    'focus:outline-none focus:ring-2 focus:ring-brand-accent/30 ' +
    'disabled:cursor-not-allowed disabled:opacity-40';

export default function NumberStepper({
    label,
    name,
    id,
    value,
    onChange,
    min = 0,
    max = 999,
    step = 1,
    error,
    hint,
    required = false,
    disabled = false,
    size = 'md',
    className = '',
}: NumberStepperProps) {
    const { fieldId, hintId, errorId, describedBy } = useFieldIds({
        id,
        name,
        hint,
        error,
    });

    const clamp = (next: number) => Math.min(max, Math.max(min, next));

    const current = () => {
        const parsed = parseInt(String(value), 10);
        return Number.isNaN(parsed) ? min : parsed;
    };

    const nudge = (delta: number) => onChange(String(clamp(current() + delta * step)));

    const labelText = typeof label === 'string' ? label : 'value';

    return (
        <Field
            htmlFor={fieldId}
            label={label}
            hint={hint}
            error={error}
            required={required}
            hintId={hintId}
            errorId={errorId}
        >
            <div className="relative">
                <input
                    id={fieldId}
                    name={name}
                    type="number"
                    inputMode="numeric"
                    min={min}
                    max={max}
                    step={step}
                    value={value}
                    disabled={disabled}
                    onChange={(e) => onChange(e.target.value)}
                    onBlur={(e) =>
                        onChange(
                            e.target.value === '' ? '' : String(clamp(current())),
                        )
                    }
                    aria-required={required || undefined}
                    aria-invalid={error ? 'true' : undefined}
                    aria-describedby={describedBy}
                    className={
                        `${controlClasses(size, error)} pl-3 pr-20 ` +
                        '[appearance:textfield] ' +
                        '[&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none ' +
                        className
                    }
                />

                <div className="absolute inset-y-0 right-2 flex items-center gap-1">
                    <button
                        type="button"
                        onClick={() => nudge(-1)}
                        disabled={disabled || current() <= min}
                        className={stepperButtonClasses}
                        aria-label={`Decrease ${labelText}`}
                        aria-controls={fieldId}
                    >
                        <MinusIcon className="h-3.5 w-3.5" />
                    </button>
                    <button
                        type="button"
                        onClick={() => nudge(1)}
                        disabled={disabled || current() >= max}
                        className={stepperButtonClasses}
                        aria-label={`Increase ${labelText}`}
                        aria-controls={fieldId}
                    >
                        <PlusIcon className="h-3.5 w-3.5" />
                    </button>
                </div>
            </div>
        </Field>
    );
}
