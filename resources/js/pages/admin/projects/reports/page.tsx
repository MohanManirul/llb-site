import { useEffect, useState, type ReactNode } from 'react';
import {
    ArrowDownTrayIcon,
    ArrowUpTrayIcon,
    TableCellsIcon,
} from '@heroicons/react/24/outline';
import DashboardLayout from '@/components/common/DashboardLayout';
import PageHeader from '@/components/common/PageHeader';
import { Button } from '@/components/ui';
import WeeklyReports from '../components/WeeklyReports';
import { REPORT_CSV_SAMPLE_URL } from '../components/ReportCsvUploadModal';
import api from '@/lib/api-client';

interface ReportsProps {
    projectId: string;
    canSubmitReports?: boolean;
}

export default function Reports({ projectId, canSubmitReports = false }: ReportsProps) {
    const [projectName, setProjectName] = useState('');
    const [submitOpen, setSubmitOpen] = useState(false);
    const [csvOpen, setCsvOpen] = useState(false);

    useEffect(() => {
        let cancelled = false;

        api.get(`/admin/projects/${projectId}`)
            .then(({ data }) => {
                if (!cancelled) {
                    setProjectName(data?.result?.business_name ?? '');
                }
            })
            .catch(() => {
                if (!cancelled) setProjectName('');
            });

        return () => {
            cancelled = true;
        };
    }, [projectId]);

    return (
        <>
            <PageHeader
                title={
                    projectName
                        ? `${projectName} — Weekly Reports`
                        : 'Weekly Reports'
                }
                backHref="/admin/projects"
                backLabel="Back to projects"
                action={
                    canSubmitReports ? (
                        <div className="flex items-center gap-2">
                            <a
                                href={REPORT_CSV_SAMPLE_URL}
                                download
                                className="inline-flex items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50/60 px-3 py-1.5 text-xs font-medium text-indigo-600 shadow-sm transition hover:border-indigo-300 hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-1"
                            >
                                <ArrowDownTrayIcon className="h-4 w-4" />
                                Sample CSV
                            </a>
                            <Button
                                size="sm"
                                variant="secondary"
                                onClick={() => setCsvOpen(true)}
                            >
                                <TableCellsIcon className="h-4 w-4" />
                                CSV Upload
                            </Button>
                            <Button size="sm" onClick={() => setSubmitOpen(true)}>
                                <ArrowUpTrayIcon className="h-4 w-4" />
                                Single Submit
                            </Button>
                        </div>
                    ) : undefined
                }
            />

            <div>
                <WeeklyReports
                    projectId={projectId}
                    canSubmit={canSubmitReports}
                    submitOpen={submitOpen}
                    onSubmitOpenChange={setSubmitOpen}
                    csvOpen={csvOpen}
                    onCsvOpenChange={setCsvOpen}
                />
            </div>
        </>
    );
}

Reports.layout = (page: ReactNode) => <DashboardLayout wide>{page}</DashboardLayout>;
