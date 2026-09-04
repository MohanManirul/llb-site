import { InputHTMLAttributes, ReactNode, useState } from 'react';
import { EyeIcon, EyeSlashIcon } from '@heroicons/react/24/outline';
import Field, { useFieldIds } from './Field';
import { ControlSize, controlClasses } from './tokens';

export interface TextInputProps
    extends Omit<InputHTMLAttributes<HTMLInputElement>, 'size'> {
    label?: ReactNode;
    name?: string;
    error?: string;
    hint?: ReactNode;
    required?: boolean;
    size?: ControlSize;
    className?: string;
}

export default function TextInput({
    label,
    name,
    id,
    type = 'text',
    error,
    hint,
    required = false,
    size = 'md',
    className = '',
    ...props
}: TextInputProps) {
    const [showPassword, setShowPassword] = useState(false);
    const { fieldId, hintId, errorId, describedBy } = useFieldIds({
        id,
        name,
        hint,
        error,
    });

    const isPassword = type === 'password';
    const inputType = isPassword && showPassword ? 'text' : type;

    const paddingClasses = isPassword ? 'pl-3 pr-10' : 'px-3';

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
                    type={inputType}
                    className={`${controlClasses(size, error)} ${paddingClasses} ${className}`}
                    aria-required={required || undefined}
                    aria-invalid={error ? 'true' : undefined}
                    aria-describedby={describedBy}
                    {...props}
                />

                {isPassword && (
                    <button
                        type="button"
                        onClick={() => setShowPassword((shown) => !shown)}
                        className="absolute inset-y-0 right-0 flex items-center rounded-r-lg px-3 text-gray-500 transition hover:text-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-accent/30"
                        aria-label={showPassword ? 'Hide password' : 'Show password'}
                        aria-controls={fieldId}
                    >
                        {showPassword ? (
                            <EyeSlashIcon className="h-5 w-5" />
                        ) : (
                            <EyeIcon className="h-5 w-5" />
                        )}
                    </button>
                )}
            </div>
        </Field>
    );
}
