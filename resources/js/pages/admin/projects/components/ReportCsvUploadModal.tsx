import { useCallback, useEffect, useRef, useState, type DragEvent } from 'react';
import {
    ArrowDownTrayIcon,
    ArrowPathIcon,
    ArrowUpTrayIcon,
    DocumentTextIcon,
    TableCellsIcon,
    XMarkIcon,
} from '@heroicons/react/24/outline';
import { Modal, Button } from '@/components/ui';
import api from '@/lib/api-client';
import { errorMessage, validationErrors } from '@/lib/flash';

export const REPORT_CSV_SAMPLE_URL = '/samples/sales-reports-import-sample.csv';

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

interface ReportCsvUploadModalProps {
    show: boolean;
    projectId: string;
    onQueued: (importId: string) => void;
    onClose?: () => void;
}

export default function ReportCsvUploadModal({
    show,
    projectId,
    onQueued,
    onClose,
}: ReportCsvUploadModalProps) {
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

        onClose?.();
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
            const { data } = await api.post(
                `/admin/projects/${projectId}/sales-reports/import`,
                formData,
                { headers: { 'Content-Type': 'multipart/form-data' } },
            );

            onQueued(data?.result?.import_id);
            onClose?.();
        } catch (e) {
            setError(
                validationErrors(e).file ||
                    errorMessage(e, 'The CSV file could not be uploaded.'),
            );
            setUploading(false);
        }
    }

    return (
        <Modal show={show} onClose={requestClose} maxWidth="lg">
            <div className="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                <div className="flex items-center gap-3">
                    <span className="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 text-white">
                        <TableCellsIcon className="h-5 w-5" />
                    </span>
                    <h3 className="text-lg font-semibold text-gray-800">
                        Upload Reports CSV
                    </h3>
                </div>

                <button
                    type="button"
                    onClick={requestClose}
                    disabled={uploading}
                    aria-label="Close"
                    className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:bg-gray-50 hover:text-gray-700 disabled:pointer-events-none disabled:opacity-50"
                >
                    <XMarkIcon className="h-5 w-5" />
                </button>
            </div>

            <div className="max-h-[70vh] space-y-4 overflow-y-auto px-6 py-5">
                <p className="text-sm text-gray-500">
                    The file needs a header row with{' '}
                    <span className="font-medium">Week Start</span>,{' '}
                    <span className="font-medium">Total Sales</span>,{' '}
                    <span className="font-medium">Total Order Quantity</span> and{' '}
                    <span className="font-medium">Total Amount Spent</span> columns.{' '}
                    <span className="font-medium">Description</span> is optional.
                </p>

                <p className="text-sm text-gray-500">
                    Every week runs 7 days, so week end is taken from week start — a
                    row whose week overlaps a report this project already has is
                    skipped, exactly as the form refuses it.
                </p>

                <a
                    href={REPORT_CSV_SAMPLE_URL}
                    download
                    className="inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-700"
                >
                    <ArrowDownTrayIcon className="h-4 w-4" />
                    Download a sample file
                </a>

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

            <div className="flex justify-end gap-3 border-t border-gray-200 px-6 py-4">
                <Button
                    type="button"
                    variant="secondary"
                    onClick={requestClose}
                    disabled={uploading}
                >
                    Cancel
                </Button>
                <Button type="button" onClick={submit} disabled={!file || uploading}>
                    {uploading && <ArrowPathIcon className="h-4 w-4 animate-spin" />}
                    {uploading ? 'Uploading…' : 'Upload CSV'}
                </Button>
            </div>
        </Modal>
    );
}
