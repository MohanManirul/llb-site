import { ReactNode, useId } from 'react';
import {
    errorTextClasses,
    hintClasses,
    labelClasses,
    requiredMarkClasses,
} from './tokens';

export interface FieldIds {
    fieldId: string;
    hintId: string;
    errorId: string;
    describedBy?: string;
}

export interface UseFieldIdsOptions {
    id?: string;
    name?: string;
    hint?: ReactNode;
    error?: string;
}

export function useFieldIds({ id, name, hint, error }: UseFieldIdsOptions = {}): FieldIds {
    const autoId = useId();
    const fieldId = id ?? name ?? autoId;
    const hintId = `${fieldId}-hint`;
    const errorId = `${fieldId}-error`;
    const described = [hint ? hintId : null, error ? errorId : null].filter(Boolean);

    return {
        fieldId,
        hintId,
        errorId,
        describedBy: described.length > 0 ? described.join(' ') : undefined,
    };
}

export interface FieldProps {
    htmlFor?: string;
    label?: ReactNode;
    labelId?: string;
    hint?: ReactNode;
    error?: string;
    required?: boolean;
    hintId?: string;
    errorId?: string;
    labelAs?: 'label' | 'span';
    className?: string;
    children: ReactNode;
}

export default function Field({
    htmlFor,
    label,
    labelId,
    hint,
    error,
    required = false,
    hintId,
    errorId,
    labelAs = 'label',
    className = '',
    children,
}: FieldProps) {
    const LabelTag = labelAs;

    return (
        <div className={className}>
            {label && (
                <LabelTag
                    {...(labelAs === 'label' ? { htmlFor } : {})}
                    id={labelId}
                    className={labelClasses}
                >
                    {label}
                    {required && <span className={requiredMarkClasses}> *</span>}
                </LabelTag>
            )}

            {children}

            {hint && (
                <p id={hintId} className={hintClasses}>
                    {hint}
                </p>
            )}
            {error && (
                <p id={errorId} className={errorTextClasses}>
                    {error}
                </p>
            )}
        </div>
    );
}
