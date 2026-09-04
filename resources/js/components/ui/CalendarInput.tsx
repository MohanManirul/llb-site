import { InputHTMLAttributes, ReactNode } from 'react';
import { CalendarDaysIcon } from '@heroicons/react/24/outline';
import Field, { useFieldIds } from './Field';
import { ControlSize, controlClasses } from './tokens';

export interface CalendarInputProps
    extends Omit<InputHTMLAttributes<HTMLInputElement>, 'value' | 'type' | 'size'> {
    label?: ReactNode;
    name?: string;
    value?: string | null;
    error?: string;
    hint?: ReactNode;
    required?: boolean;
    size?: ControlSize;
    className?: string;
}

export default function CalendarInput({
    label,
    name,
    id,
    value,
    onChange,
    error,
    hint,
    required = false,
    size = 'md',
    className = '',
    ...props
}: CalendarInputProps) {
    const { fieldId, hintId, errorId, describedBy } = useFieldIds({
        id,
        name,
        hint,
        error,
    });

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
                    type="date"
                    value={value ?? ''}
                    onChange={onChange}
                    className={`${controlClasses(size, error)} pl-3 pr-10 ${className}`}
                    aria-required={required || undefined}
                    aria-invalid={error ? 'true' : undefined}
                    aria-describedby={describedBy}
                    {...props}
                />

                <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                    <CalendarDaysIcon className="h-5 w-5" />
                </div>
            </div>
        </Field>
    );
}
