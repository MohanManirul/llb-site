import { ReactNode, useId } from 'react';
import Field, { useFieldIds } from './Field';

interface StatusOption {
    value: boolean;
    label: string;
}

const options: StatusOption[] = [
    { value: true, label: 'Active' },
    { value: false, label: 'Inactive' },
];

export interface StatusRadioProps {
    value: boolean;
    onChange: (value: boolean) => void;
    error?: string;
    hint?: ReactNode;
    label?: ReactNode;
    name?: string;
    required?: boolean;
    disabled?: boolean;
}

export default function StatusRadio({
    value,
    onChange,
    error,
    hint,
    label = 'Status',
    name,
    required = false,
    disabled = false,
}: StatusRadioProps) {
    const autoName = useId();
    const groupName = name ?? autoName;
    const { fieldId, hintId, errorId, describedBy } = useFieldIds({ hint, error });
    const labelId = `${fieldId}-label`;

    return (
        <Field
            label={label}
            labelId={labelId}
            labelAs="span"
            hint={hint}
            error={error}
            required={required}
            hintId={hintId}
            errorId={errorId}
        >
            <div
                role="radiogroup"
                aria-labelledby={labelId}
                aria-describedby={describedBy}
                aria-required={required || undefined}
                aria-invalid={error ? 'true' : undefined}
                className="grid grid-cols-2 gap-3"
            >
                {options.map((option) => {
                    const selected = value === option.value;

                    return (
                        <label
                            key={option.label}
                            className={
                                'flex items-center gap-2.5 rounded-lg border px-4 py-2.5 text-sm transition ' +
                                (disabled
                                    ? 'cursor-not-allowed opacity-60 '
                                    : 'cursor-pointer ') +
                                (selected
                                    ? 'border-brand-accent bg-brand-accent/5 font-medium text-gray-800'
                                    : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50')
                            }
                        >
                            <input
                                type="radio"
                                name={groupName}
                                className="h-4 w-4 border-gray-300 text-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-accent/30 disabled:cursor-not-allowed"
                                checked={selected}
                                disabled={disabled}
                                onChange={() => onChange(option.value)}
                            />
                            {option.label}
                        </label>
                    );
                })}
            </div>
        </Field>
    );
}
