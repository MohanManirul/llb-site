import { ReactNode, TextareaHTMLAttributes } from 'react';
import Field, { useFieldIds } from './Field';
import { ControlSize, controlClasses } from './tokens';

export interface TextareaProps
    extends Omit<TextareaHTMLAttributes<HTMLTextAreaElement>, 'value'> {
    label?: ReactNode;
    name?: string;
    error?: string;
    hint?: ReactNode;
    required?: boolean;
    rows?: number;
    maxCharacters?: number;
    size?: ControlSize;
    className?: string;
    value?: string;
}

export default function Textarea({
    label,
    name,
    id,
    error,
    hint,
    required = false,
    rows = 3,
    maxCharacters,
    size = 'md',
    className = '',
    value = '',
    ...props
}: TextareaProps) {
    const { fieldId, hintId, errorId, describedBy } = useFieldIds({
        id,
        name,
        hint,
        error,
    });

    const paddingClasses = maxCharacters ? 'px-3 pb-6' : 'px-3';

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
                <textarea
                    id={fieldId}
                    name={name}
                    rows={rows}
                    maxLength={maxCharacters}
                    value={value}
                    className={`${controlClasses(size, error)} ${paddingClasses} ${className}`}
                    aria-required={required || undefined}
                    aria-invalid={error ? 'true' : undefined}
                    aria-describedby={describedBy}
                    {...props}
                />

                {maxCharacters && (
                    <span
                        className="absolute bottom-2 right-2 text-xs text-gray-400"
                        aria-live="polite"
                    >
                        {value.length}/{maxCharacters}
                    </span>
                )}
            </div>
        </Field>
    );
}
