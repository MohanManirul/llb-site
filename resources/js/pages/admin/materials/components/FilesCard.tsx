import { useState } from 'react';
import { DocumentTextIcon } from '@heroicons/react/24/outline';
import { Button, ConfirmationModal, FileUpload, TextInput } from '@/components/ui';
import api from '@/lib/api-client';
import { errorMessage, flash, validationErrors } from '@/lib/flash';
import { formatBytes } from '@/lib/format';
import { MaterialFileRow, StudyMaterial } from '../types';

interface FilesCardProps {
    material: StudyMaterial;
    onChanged: () => void;
}

export default function FilesCard({ material, onChanged }: FilesCardProps) {
    const [newFile, setNewFile] = useState<File | null>(null);
    const [labelBn, setLabelBn] = useState('');
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [uploading, setUploading] = useState(false);
    const [uploadProgress, setUploadProgress] = useState<number | null>(null);
    const [pendingDelete, setPendingDelete] = useState<MaterialFileRow | null>(null);
    const [deleting, setDeleting] = useState(false);

    const files = material.files ?? [];

    const upload = async () => {
        if (!newFile) return;

        setUploading(true);
        setErrors({});

        const payload = new FormData();
        payload.append('file', newFile);
        if (labelBn) payload.append('label_bn', labelBn);

        try {
            await api.post(`/admin/study-materials/${material.id}/files`, payload, {
                onUploadProgress: (event) => {
                    if (event.total) {
                        setUploadProgress(Math.round((event.loaded / event.total) * 100));
                    }
                },
            });

            flash.success('File uploaded successfully.');
            setNewFile(null);
            setLabelBn('');
            onChanged();
        } catch (error) {
            const status = (error as { response?: { status?: number } })?.response?.status;

            if (status === 422) {
                setErrors(validationErrors(error));
            } else {
                flash.error(errorMessage(error, 'Could not upload the file.'));
            }
        } finally {
            setUploading(false);
            setUploadProgress(null);
        }
    };

    const confirmDelete = async () => {
        if (!pendingDelete) return;

        setDeleting(true);

        try {
            await api.delete(`/admin/study-materials/${material.id}/files/${pendingDelete.id}`);
            flash.success('File deleted.');
            setPendingDelete(null);
            onChanged();
        } catch (error) {
            flash.error(errorMessage(error, 'Could not delete this file.'));
        } finally {
            setDeleting(false);
        }
    };

    return (
        <div className="mt-5 max-w-3xl rounded-card border border-hairline bg-white shadow-sm">
            <div className="border-b border-hairline px-5 py-4">
                <h2 className="font-semibold text-ink">PDF files</h2>
                <p className="mt-0.5 text-sm text-ink-muted">
                    A published material must keep at least one file.
                </p>
            </div>

            <div className="p-5">
                <ul className="space-y-2">
                    {files.map((file) => (
                        <li
                            key={file.id}
                            className="flex items-center gap-3 rounded-control border border-hairline bg-field px-3 py-2.5"
                        >
                            <DocumentTextIcon className="h-7 w-7 shrink-0 text-brand-accent" />

                            <div className="min-w-0 flex-1">
                                <a
                                    href={file.preview_url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="block truncate text-sm font-medium text-brand-accent hover:underline"
                                >
                                    {file.label_bn || file.original_name}
                                </a>
                                <p className="text-xs text-ink-muted">
                                    {formatBytes(file.size)} · {file.download_count} downloads
                                </p>
                            </div>

                            <button
                                type="button"
                                onClick={() => setPendingDelete(file)}
                                className="shrink-0 text-sm font-medium text-red-600 hover:text-red-800"
                            >
                                Delete
                            </button>
                        </li>
                    ))}

                    {files.length === 0 && (
                        <li className="rounded-control border border-dashed border-hairline px-3 py-4 text-center text-sm text-ink-muted">
                            No files yet — the material cannot be published without one.
                        </li>
                    )}
                </ul>

                <div className="mt-4 rounded-control border border-hairline p-3">
                    <p className="mb-3 text-sm font-medium text-ink">Add a file</p>

                    <div className="space-y-3">
                        <FileUpload
                            value={newFile}
                            progress={uploadProgress}
                            error={errors.file}
                            onChange={setNewFile}
                        />
                        <TextInput
                            label="Label (Bangla)"
                            placeholder="২য় খণ্ড"
                            value={labelBn}
                            onChange={(e) => setLabelBn(e.target.value)}
                            error={errors.label_bn}
                        />
                        <div className="flex justify-end">
                            <Button
                                type="button"
                                size="sm"
                                disabled={!newFile}
                                loading={uploading}
                                onClick={upload}
                            >
                                Upload
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <ConfirmationModal
                show={pendingDelete !== null}
                onClose={() => setPendingDelete(null)}
                onConfirm={confirmDelete}
                processing={deleting}
                title="Delete file"
                confirmText="Delete"
            >
                Are you sure you want to delete{' '}
                <span className="font-medium">
                    {pendingDelete?.label_bn || pendingDelete?.original_name}
                </span>
                ? The PDF is removed from storage permanently.
            </ConfirmationModal>
        </div>
    );
}
