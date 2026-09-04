import { ReactNode, SelectHTMLAttributes } from 'react';
import { ChevronDownIcon } from '@heroicons/react/24/outline';
import Field, { useFieldIds } from './Field';
import { ControlSize, controlClasses } from './tokens';

export interface SelectInputProps
    extends Omit<SelectHTMLAttributes<HTMLSelectElement>, 'size'> {
    label?: ReactNode;
    name?: string;
    error?: string;
    hint?: ReactNode;
    required?: boolean;
    size?: ControlSize;
    className?: string;
}

export default function SelectInput({
    label,
    name,
    id,
    error,
    hint,
    required = false,
    size = 'md',
    className = '',
    children,
    ...props
}: SelectInputProps) {
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
                <select
                    id={fieldId}
                    name={name}
                    className={`${controlClasses(size, error)} appearance-none pl-3 pr-10 ${className}`}
                    aria-required={required || undefined}
                    aria-invalid={error ? 'true' : undefined}
                    aria-describedby={describedBy}
                    {...props}
                >
                    {children}
                </select>

                <ChevronDownIcon className="pointer-events-none absolute inset-y-0 right-3 my-auto h-4 w-4 text-gray-500" />
            </div>
        </Field>
    );
}
