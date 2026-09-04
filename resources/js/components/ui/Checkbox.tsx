import { InputHTMLAttributes, ReactNode, useEffect, useRef } from 'react';
import { useFieldIds } from './Field';
import { errorTextClasses } from './tokens';

export interface CheckboxProps extends InputHTMLAttributes<HTMLInputElement> {
    label?: ReactNode;
    name?: string;
    error?: string;
    indeterminate?: boolean;
    className?: string;
}

export default function Checkbox({
    label,
    name,
    id,
    error,
    indeterminate = false,
    disabled = false,
    className = '',
    ...props
}: CheckboxProps) {
    const inputRef = useRef<HTMLInputElement>(null);
    const { fieldId, errorId, describedBy } = useFieldIds({ id, name, error });

    useEffect(() => {
        if (inputRef.current) {
            inputRef.current.indeterminate = indeterminate;
        }
    }, [indeterminate]);

    return (
        <div>
            <label
                className={
                    'flex select-none items-center gap-2 text-sm text-gray-600 ' +
                    (disabled ? 'cursor-not-allowed opacity-60' : 'cursor-pointer')
                }
            >
                <input
                    ref={inputRef}
                    type="checkbox"
                    id={fieldId}
                    name={name}
                    disabled={disabled}
                    className={
                        'h-4 w-4 rounded border-gray-400 text-brand-accent transition ' +
                        'focus:outline-none focus:ring-2 focus:ring-brand-accent/30 ' +
                        'disabled:cursor-not-allowed ' +
                        (disabled ? '' : 'cursor-pointer ') +
                        className
                    }
                    aria-invalid={error ? 'true' : undefined}
                    aria-describedby={describedBy}
                    {...props}
                />
                {label}
            </label>

            {error && (
                <p id={errorId} className={errorTextClasses}>
                    {error}
                </p>
            )}
        </div>
    );
}
