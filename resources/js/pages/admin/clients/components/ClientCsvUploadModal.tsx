import { useCallback, useEffect, useRef, useState, type DragEvent } from 'react';
import {
    ArrowDownTrayIcon,
    ArrowPathIcon,
    ArrowUpTrayIcon,
    DocumentTextIcon,
    XMarkIcon,
} from '@heroicons/react/24/outline';
import { Modal, Button } from '@/components/ui';
import api from '@/lib/api-client';
import { flash, errorMessage } from '@/lib/flash';

const SAMPLE_URL = '/samples/clients-import-sample.csv';

function formatBytes(size: number): string {
    if (size < 1024) return `${size} B`;
    if (size < 1024 * 1024) return `${(size / 1024).toFixed(1)} KB`;
    return `${(size / 1024 / 1024).toFixed(1)} MB`;
}

function isCsv(file: File): boolean {
    return (
        file.name.toLowerCase().endsWith('.csv') ||
        ['text/csv', 'application/csv', 'application/vnd.ms-excel'].includes(file.type)
    );
}

interface ClientCsvUploadModalProps {
    show: boolean;
    onClose: () => void;
    onImported?: () => void;
}

export default function ClientCsvUploadModal({
    show,
    onClose,
    onImported,
}: ClientCsvUploadModalProps) {
    const fileInput = useRef<HTMLInputElement>(null);
    const [file, setFile] = useState<File | null>(null);
    const [dragging, setDragging] = useState(false);
    const [uploading, setUploading] = useState(false);
    const [error, setError] = useState('');

    useEffect(() => {
        if (show) return;

        setFile(null);
        setDragging(false);
        setUploading(false);
        setError('');
    }, [show]);

    const pickFile = useCallback((selected?: File | null) => {
        if (!selected) return;

        if (!isCsv(selected)) {
            setError('Only CSV files are allowed.');
            return;
        }

        setError('');
        setFile(selected);
    }, []);

    function requestClose() {
        if (uploading) return;

        onClose();
    }

    function handleDrop(e: DragEvent<HTMLDivElement>) {
        e.preventDefault();
        setDragging(false);
        pickFile(e.dataTransfer.files?.[0]);
    }

    async function submit() {
        if (!file || uploading) return;

        setUploading(true);
        setError('');

        const formData = new FormData();
        formData.append('file', file);

        try {
            const { data } = await api.post('/admin/clients/import', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            flash.success(data.message);
            onImported?.();
            onClose?.();
        } catch (e) {
            setError(errorMessage(e, 'The CSV file could not be uploaded.'));
        } finally {
            setUploading(false);
        }
    }

    return (
        <Modal show={show} onClose={requestClose} maxWidth="lg">
            <div className="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h3 className="text-md font-semibold text-gray-800">Upload clients CSV</h3>
                <button
                    type="button"
                    onClick={requestClose}
                    disabled={uploading}
                    className="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 disabled:pointer-events-none disabled:opacity-50"
                >
                    <XMarkIcon className="h-5 w-5" />
                </button>
            </div>

            <div className="space-y-4 px-5 py-5">
                <p className="text-sm text-gray-500">
                    The file needs a header row with <span className="font-medium">Name</span>,{' '}
                    <span className="font-medium">Email</span>, <span className="font-medium">Phone</span> and{' '}
                    <span className="font-medium">Password</span> columns. Rows whose email or
                    phone already exists are skipped.
                </p>

                <a
                    href={SAMPLE_URL}
                    download
                    className="inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-700"
                >
                    <ArrowDownTrayIcon className="h-4 w-4" />
                    Download a sample file
                </a>

                <p className="text-xs text-gray-400">
                    The sample only shows the shape. Replace every row with your
                    own before uploading — its addresses are unreachable and its
                    password is a placeholder, not a usable login.
                </p>

                <div
                    onDragOver={(e) => {
                        e.preventDefault();
                        setDragging(true);
                    }}
                    onDragLeave={() => setDragging(false)}
                    onDrop={handleDrop}
                    onClick={() => fileInput.current?.click()}
                    className={
                        'flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed px-6 py-10 text-center transition ' +
                        (dragging
                            ? 'border-indigo-400 bg-indigo-50'
                            : 'border-gray-300 bg-gray-50 hover:border-indigo-300 hover:bg-indigo-50')
                    }
                >
                    <ArrowUpTrayIcon className="h-8 w-8 text-gray-400" />
                    <p className="text-sm font-medium text-gray-700">
                        Drag and drop your CSV here
                    </p>
                    <p className="text-xs text-gray-500">or click to browse — max 10 MB</p>

                    <input
                        ref={fileInput}
                        type="file"
                        accept=".csv,text/csv"
                        className="hidden"
                        onChange={(e) => pickFile(e.target.files?.[0])}
                    />
                </div>

                {file && (
                    <div className="flex items-center justify-between rounded-lg border border-gray-200 px-3 py-2">
                        <div className="flex items-center gap-2 text-sm text-gray-700">
                            <DocumentTextIcon className="h-5 w-5 text-gray-400" />
                            <span className="font-medium">{file.name}</span>
                            <span className="text-xs text-gray-500">
                                {formatBytes(file.size)}
                            </span>
                        </div>
                        <button
                            type="button"
                            onClick={(e) => {
                                e.stopPropagation();
                                setFile(null);
                                if (fileInput.current) fileInput.current.value = '';
                            }}
                            className="text-xs font-medium text-red-600 hover:text-red-700"
                        >
                            Remove
                        </button>
                    </div>
                )}

                {error && (
                    <p className="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">
                        {error}
                    </p>
                )}
            </div>

            <div className="flex justify-end gap-2 border-t border-gray-100 bg-gray-50 px-5 py-3">
                <Button variant="secondary" size="sm" onClick={requestClose} disabled={uploading}>
                    Cancel
                </Button>
                <Button size="sm" onClick={submit} disabled={!file || uploading}>
                    {uploading && <ArrowPathIcon className="h-4 w-4 animate-spin" />}
                    {uploading ? 'Uploading…' : 'Upload CSV'}
                </Button>
            </div>
        </Modal>
    );
}
