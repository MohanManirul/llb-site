import { FormEvent, ReactNode, useState } from 'react';
import { Link } from '@inertiajs/react';
import { ArrowDownTrayIcon, CheckCircleIcon, ExclamationTriangleIcon } from '@heroicons/react/24/outline';
import DashboardLayout from '@/components/common/DashboardLayout';
import PageHeader from '@/components/common/PageHeader';
import { Button, FileUpload } from '@/components/ui';
import api from '@/lib/api-client';
import type { ApiEnvelope } from '@/lib/api-types';
import { errorMessage, flash, validationErrors } from '@/lib/flash';
import { ImportReport } from '../types';

const TEMPLATE_URL = '/v1/admin/questions/import/template';

const COLUMNS: Array<[string, string]> = [
    ['subject', 'Subject slug or numeric id. Required.'],
    ['type', '`mcq` or `written`. Required.'],
    ['exam_stage', '`preliminary`, `mcq`, `written` or `viva`. Optional.'],
    ['exam_year', 'Four-digit year, e.g. 2023. Optional.'],
    ['question_bn / question_en', 'Question text. Bangla is required.'],
    ['option_1 … option_5', 'MCQ options in order. At least two for MCQ, blank for written.'],
    ['correct_option', 'Number of the correct option (1–5). Required for MCQ.'],
    ['explanation_bn / explanation_en', 'Optional explanation shown after answering.'],
    ['reference', 'Act, section or book. Optional.'],
];

export default function QuestionImport() {
    const [file, setFile] = useState<File | null>(null);
    const [error, setError] = useState<string | undefined>();
    const [progress, setProgress] = useState<number | null>(null);
    const [uploading, setUploading] = useState(false);
    const [report, setReport] = useState<ImportReport | null>(null);

    const submit = async (event: FormEvent) => {
        event.preventDefault();

        if (!file) {
            setError('Choose a CSV file first.');
            return;
        }

        setUploading(true);
        setError(undefined);
        setReport(null);

        const payload = new FormData();
        payload.append('file', file);

        try {
            const { data } = await api.post<ApiEnvelope<ImportReport>>('/admin/questions/import', payload, {
                onUploadProgress: (e) => {
                    if (e.total) setProgress(Math.round((e.loaded / e.total) * 100));
                },
            });

            setReport(data.result);

            if (data.result.imported > 0) {
                flash.success(`Imported ${data.result.imported} questions as drafts.`);
                setFile(null);
            }
        } catch (err) {
            const status = (err as { response?: { status?: number } })?.response?.status;

            if (status === 422) {
                setError(validationErrors(err).file ?? errorMessage(err));
            } else {
                flash.error(errorMessage(err, 'Could not import the file.'));
            }
        } finally {
            setUploading(false);
            setProgress(null);
        }
    };

    return (
        <>
            <PageHeader
                title="Import Questions"
                backHref="/admin/questions"
                action={
                    <a href={TEMPLATE_URL} download>
                        <Button size="sm" variant="secondary" type="button">
                            <ArrowDownTrayIcon className="h-4 w-4" />
                            Download template
                        </Button>
                    </a>
                }
            />

            <div className="grid max-w-5xl gap-5 lg:grid-cols-[1fr_minmax(0,22rem)]">
                <form onSubmit={submit} className="rounded-card border border-hairline bg-white shadow-sm">
                    <div className="border-b border-hairline px-5 py-4">
                        <h2 className="font-semibold text-ink">Upload CSV</h2>
                        <p className="mt-0.5 text-sm text-ink-muted">
                            Up to 1,000 rows per file. Every question is created as a draft; nothing is imported
                            if any row fails validation.
                        </p>
                    </div>

                    <div className="p-5">
                        <FileUpload
                            label="CSV file"
                            value={file}
                            accept=".csv,text/csv"
                            maxSizeMb={5}
                            progress={progress}
                            error={error}
                            onChange={(next) => {
                                setFile(next);
                                setError(undefined);
                            }}
                            onRemove={() => setFile(null)}
                        />
                    </div>

                    <div className="flex items-center justify-end gap-3 border-t border-hairline px-5 py-4">
                        <Link href="/admin/questions">
                            <Button type="button" variant="secondary">
                                Back
                            </Button>
                        </Link>
                        <Button type="submit" loading={uploading} disabled={!file}>
                            Import
                        </Button>
                    </div>
                </form>

                <aside className="rounded-card border border-hairline bg-white shadow-sm">
                    <div className="border-b border-hairline px-5 py-4">
                        <h2 className="font-semibold text-ink">Columns</h2>
                    </div>
                    <dl className="divide-y divide-hairline px-5 text-sm">
                        {COLUMNS.map(([name, description]) => (
                            <div key={name} className="py-2.5">
                                <dt className="font-mono text-xs font-semibold text-ink">{name}</dt>
                                <dd className="mt-0.5 text-ink-muted">{description}</dd>
                            </div>
                        ))}
                    </dl>
                </aside>
            </div>

            {report && (
                <div className="mt-5 max-w-5xl rounded-card border border-hairline bg-white shadow-sm">
                    <div className="flex items-center gap-3 border-b border-hairline px-5 py-4">
                        {report.failed === 0 ? (
                            <CheckCircleIcon className="h-6 w-6 text-emerald-500" />
                        ) : (
                            <ExclamationTriangleIcon className="h-6 w-6 text-amber-500" />
                        )}
                        <div>
                            <h2 className="font-semibold text-ink">
                                {report.failed === 0
                                    ? `${report.imported} questions imported`
                                    : `${report.failed} rows failed — nothing was imported`}
                            </h2>
                            {report.failed > 0 && (
                                <p className="text-sm text-ink-muted">
                                    Fix the rows below and upload the file again.
                                </p>
                            )}
                        </div>
                    </div>

                    {report.errors.length > 0 && (
                        <div className="overflow-x-auto">
                            <table className="min-w-full text-sm">
                                <thead className="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                                    <tr>
                                        <th className="px-5 py-2">Row</th>
                                        <th className="px-5 py-2">Problems</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-hairline">
                                    {report.errors.map((row) => (
                                        <tr key={row.row}>
                                            <td className="px-5 py-2 font-mono text-xs">{row.row}</td>
                                            <td className="px-5 py-2">
                                                <ul className="list-disc space-y-0.5 pl-4 text-red-700">
                                                    {row.messages.map((message, index) => (
                                                        <li key={index}>{message}</li>
                                                    ))}
                                                </ul>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            )}
        </>
    );
}

QuestionImport.layout = (page: ReactNode) => <DashboardLayout>{page}</DashboardLayout>;
