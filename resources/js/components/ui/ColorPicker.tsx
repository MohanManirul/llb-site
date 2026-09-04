import { ReactNode } from 'react';
import Field, { useFieldIds } from './Field';
import { badgeStyle, fieldStateClasses, isHexColor } from './tokens';

const FALLBACK_COLOR = '#3B82F6';

export interface ColorPickerProps {
    label?: ReactNode;
    name?: string;
    id?: string;
    value?: string;
    onChange: (value: string) => void;
    error?: string;
    hint?: ReactNode;
    required?: boolean;
    disabled?: boolean;
    previewLabel?: string;
}

export default function ColorPicker({
    label = 'Status Color',
    name = 'color',
    id,
    value = '',
    onChange,
    error,
    hint,
    required = false,
    disabled = false,
    previewLabel,
}: ColorPickerProps) {
    const { fieldId, hintId, errorId, describedBy } = useFieldIds({
        id,
        hint,
        error,
    });

    const swatchValue = isHexColor(value) ? value : FALLBACK_COLOR;

    function changeHex(input: string) {
        const next = input.startsWith('#') ? input : `#${input}`;
        onChange(next.slice(0, 7));
    }

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
            <div className="flex items-center gap-3">
                <label
                    className={
                        'relative h-11 w-11 shrink-0 overflow-hidden rounded-lg border border-gray-300 bg-white p-1.5 shadow-sm ' +
                        (disabled ? 'cursor-not-allowed opacity-60' : 'cursor-pointer')
                    }
                    aria-label="Pick a colour"
                >
                    <span
                        className="block h-full w-full rounded-full"
                        style={{ backgroundColor: swatchValue }}
                    />
                    <input
                        type="color"
                        value={swatchValue}
                        disabled={disabled}
                        onChange={(e) => onChange(e.target.value)}
                        className="absolute inset-0 h-full w-full cursor-pointer opacity-0 disabled:cursor-not-allowed"
                    />
                </label>

                <input
                    id={fieldId}
                    name={name}
                    type="text"
                    value={value}
                    disabled={disabled}
                    onChange={(e) => changeHex(e.target.value)}
                    placeholder={FALLBACK_COLOR}
                    spellCheck="false"
                    aria-required={required || undefined}
                    aria-invalid={error ? 'true' : undefined}
                    aria-describedby={describedBy}
                    className={
                        'h-11 w-full rounded-lg border bg-white px-3 text-sm uppercase text-gray-700 shadow-sm transition ' +
                        'disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500 ' +
                        fieldStateClasses(error)
                    }
                />

                <span
                    className="flex h-11 min-w-24 shrink-0 items-center justify-center rounded-lg border px-3 text-xs font-medium"
                    style={badgeStyle(value)}
                >
                    {previewLabel?.trim() || 'Preview'}
                </span>
            </div>
        </Field>
    );
}
