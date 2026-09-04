import { ChangeEvent, ReactNode, useEffect, useId, useRef, useState } from 'react';
import { CameraIcon } from '@heroicons/react/24/outline';
import Button from './Button';
import { errorTextClasses, hintClasses } from './tokens';

export interface ImageUploadProps {
    label?: ReactNode;
    name?: string;
    id?: string;
    value?: string | File | null;
    onChange: (file: File) => void;
    onRemove?: () => void;
    removeLabel?: string;
    helperText?: ReactNode;
    error?: string;
    disabled?: boolean;
    layout?: 'stacked' | 'inline';
}

export default function ImageUpload({
    label = 'Logo',
    name = 'image',
    id,
    value = null,
    onChange,
    onRemove,
    removeLabel = 'Remove photo',
    helperText = 'PNG or JPG. Click the image to upload.',
    error,
    disabled = false,
    layout = 'stacked',
}: ImageUploadProps) {
    const autoId = useId();
    const fieldId = id ?? autoId;
    const objectUrlRef = useRef<string | null>(null);
    const [preview, setPreview] = useState<string | null>(
        typeof value === 'string' && value ? value : null,
    );

    function trackObjectUrl(url: string | null) {
        if (objectUrlRef.current) {
            URL.revokeObjectURL(objectUrlRef.current);
        }
        objectUrlRef.current = url;
    }

    useEffect(() => {
        if (value instanceof File) {
            const url = URL.createObjectURL(value);
            trackObjectUrl(url);
            setPreview(url);
            return;
        }

        trackObjectUrl(null);
        setPreview(typeof value === 'string' && value ? value : null);
    }, [value]);

    useEffect(() => {
        return () => {
            if (objectUrlRef.current) {
                URL.revokeObjectURL(objectUrlRef.current);
                objectUrlRef.current = null;
            }
        };
    }, []);

    const handleChange = (e: ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];

        if (!file) return;

        const url = URL.createObjectURL(file);
        trackObjectUrl(url);
        setPreview(url);

        onChange(file);
    };

    const fileInput = (
        <input
            id={fieldId}
            name={name}
            type="file"
            accept="image/*"
            disabled={disabled}
            className="sr-only"
            aria-describedby={error ? `${fieldId}-error` : `${fieldId}-hint`}
            onChange={handleChange}
        />
    );

    const removeButton = onRemove && preview && (
        <Button
            variant="link"
            tone="danger"
            size="sm"
            className="mt-2"
            disabled={disabled}
            onClick={onRemove}
        >
            {removeLabel}
        </Button>
    );

    const placeholder = <CameraIcon className="h-6 w-6 text-gray-400" />;

    const triggerClasses = disabled
        ? 'cursor-not-allowed opacity-60'
        : 'cursor-pointer';

    if (layout === 'inline') {
        return (
            <div>
                <div className="flex items-center gap-4">
                    <label
                        htmlFor={fieldId}
                        className={`shrink-0 ${triggerClasses}`}
                    >
                        <div className="flex h-16 w-16 items-center justify-center overflow-hidden rounded-full border-2 border-dashed border-gray-300 bg-gray-50 transition hover:border-brand-accent/40 hover:bg-brand-accent/5">
                            {preview ? (
                                <img
                                    src={preview}
                                    alt=""
                                    className="h-full w-full object-cover"
                                />
                            ) : (
                                placeholder
                            )}
                        </div>
                    </label>

                    {fileInput}

                    <div className="min-w-0">
                        <label
                            htmlFor={fieldId}
                            className={`block text-sm font-medium text-gray-800 ${triggerClasses}`}
                        >
                            {label}
                        </label>
                        <span id={`${fieldId}-hint`} className={`block ${hintClasses}`}>
                            {helperText}
                        </span>
                        {removeButton}
                    </div>
                </div>

                {error && (
                    <p id={`${fieldId}-error`} className={errorTextClasses}>
                        {error}
                    </p>
                )}
            </div>
        );
    }

    return (
        <div className="flex flex-col items-center text-center">
            <label htmlFor={fieldId} className={triggerClasses}>
                <div className="flex h-32 w-32 items-center justify-center overflow-hidden rounded-full border-2 border-dashed border-gray-300 bg-gray-50 transition hover:border-brand-accent/40 hover:bg-brand-accent/5">
                    {preview ? (
                        <img
                            src={preview}
                            alt=""
                            className="h-full w-full object-cover"
                        />
                    ) : (
                        <span className="flex flex-col items-center gap-1 text-xs text-gray-400">
                            {placeholder}
                            Upload
                        </span>
                    )}
                </div>
            </label>

            {fileInput}

            <p id={`${fieldId}-hint`} className="mt-3 text-xs text-gray-400">
                {helperText}
            </p>

            {removeButton}

            {error && (
                <p id={`${fieldId}-error`} className={errorTextClasses}>
                    {error}
                </p>
            )}
        </div>
    );
}
