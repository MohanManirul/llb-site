import { ChangeEvent, DragEvent, ReactNode, useRef, useState } from 'react';
import { ArrowUpTrayIcon, DocumentTextIcon, XMarkIcon } from '@heroicons/react/24/outline';
import Field, { useFieldIds } from './Field';
import { formatBytes } from '@/lib/format';

export interface FileUploadProps {
    label?: ReactNode;
    name?: string;
    id?: string;
    value?: File | null;
    currentName?: string | null;
    currentUrl?: string | null;
    currentSize?: number | null;
    accept?: string;
    maxSizeMb?: number;
    progress?: number | null;
    hint?: ReactNode;
    error?: string;
    required?: boolean;
    disabled?: boolean;
    onChange: (file: File | null) => void;
    onRemove?: () => void;
}

const DEFAULT_ACCEPT = 'application/pdf,.pdf';

function isPdf(file: File): boolean {
    return file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
}

export default function FileUpload({
    label,
    name,
    id,
    value = null,
    currentName = null,
    currentUrl = null,
    currentSize = null,
    accept = DEFAULT_ACCEPT,
    maxSizeMb = 50,
    progress = null,
    hint,
    error,
    required = false,
    disabled = false,
    onChange,
    onRemove,
}: FileUploadProps) {
    const { fieldId, hintId, errorId, describedBy } = useFieldIds({ id, name, hint, error });
    const inputRef = useRef<HTMLInputElement>(null);
    const [localError, setLocalError] = useState<string | null>(null);
    const [dragging, setDragging] = useState(false);

    const acceptsPdfOnly = accept === DEFAULT_ACCEPT;

    const select = (file: File | null) => {
        if (!file) return;

        if (acceptsPdfOnly && !isPdf(file)) {
            setLocalError('Only PDF files are allowed.');
            return;
        }

        if (file.size > maxSizeMb * 1024 * 1024) {
            setLocalError(`The file is larger than ${maxSizeMb} MB.`);
            return;
        }

        setLocalError(null);
        onChange(file);
    };

    const handleInput = (event: ChangeEvent<HTMLInputElement>) => {
        select(event.target.files?.[0] ?? null);
        event.target.value = '';
    };

    const handleDrop = (event: DragEvent<HTMLLabelElement>) => {
        event.preventDefault();
        setDragging(false);

        if (disabled) return;

        select(event.dataTransfer.files?.[0] ?? null);
    };

    const clear = () => {
        setLocalError(null);
        onChange(null);
        onRemove?.();
    };

    const displayName = value?.name ?? currentName;
    const displaySize = value?.size ?? currentSize;
    const shownError = error ?? localError ?? undefined;

    return (
        <Field
            htmlFor={fieldId}
            label={label}
            hint={hint}
            error={shownError}
            required={required}
            hintId={hintId}
            errorId={errorId}
        >
            <input
                ref={inputRef}
                id={fieldId}
                name={name}
                type="file"
                accept={accept}
                className="sr-only"
                disabled={disabled}
                aria-describedby={describedBy}
                aria-invalid={shownError ? true : undefined}
                onChange={handleInput}
            />

            {displayName ? (
                <div className="flex items-center gap-3 rounded-control border border-hairline bg-field px-3 py-2.5">
                    <DocumentTextIcon className="h-8 w-8 shrink-0 text-brand-accent" />

                    <div className="min-w-0 flex-1">
                        {currentUrl && !value ? (
                            <a
                                href={currentUrl}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="block truncate text-sm font-medium text-brand-accent hover:underline"
                            >
                                {displayName}
                            </a>
                        ) : (
                            <p className="truncate text-sm font-medium text-ink">{displayName}</p>
                        )}

                        <p className="text-xs text-ink-muted">
                            {displaySize != null ? formatBytes(displaySize) : 'PDF'}
                        </p>

                        {progress != null && (
                            <div
                                role="progressbar"
                                aria-valuenow={progress}
                                aria-valuemin={0}
                                aria-valuemax={100}
                                className="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-gray-200"
                            >
                                <div
                                    className="h-full rounded-full bg-brand-accent transition-all"
                                    style={{ width: `${progress}%` }}
                                />
                            </div>
                        )}
                    </div>

                    {!disabled && (
                        <div className="flex shrink-0 items-center gap-2">
                            <button
                                type="button"
                                onClick={() => inputRef.current?.click()}
                                className="text-sm font-medium text-brand-accent hover:underline"
                            >
                                Replace
                            </button>
                            <button
                                type="button"
                                onClick={clear}
                                aria-label="Remove file"
                                className="rounded-full p-1 text-gray-400 hover:bg-gray-100 hover:text-red-600"
                            >
                                <XMarkIcon className="h-4 w-4" />
                            </button>
                        </div>
                    )}
                </div>
            ) : (
                <label
                    htmlFor={fieldId}
                    onDragOver={(event) => {
                        event.preventDefault();
                        if (!disabled) setDragging(true);
                    }}
                    onDragLeave={() => setDragging(false)}
                    onDrop={handleDrop}
                    className={
                        'flex cursor-pointer flex-col items-center justify-center gap-1.5 rounded-control border-2 border-dashed px-4 py-6 text-center transition ' +
                        (dragging
                            ? 'border-brand-accent bg-brand-accent/5'
                            : 'border-hairline bg-field hover:border-brand-muted') +
                        (disabled ? ' cursor-not-allowed opacity-60' : '')
                    }
                >
                    <ArrowUpTrayIcon className="h-6 w-6 text-gray-400" />
                    <span className="text-sm font-medium text-ink">
                        Click to choose a PDF, or drag it here
                    </span>
                    <span className="text-xs text-ink-muted">Up to {maxSizeMb} MB</span>
                </label>
            )}
        </Field>
    );
}
