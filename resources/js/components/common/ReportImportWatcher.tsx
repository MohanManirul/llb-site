import { useEffect, useState } from 'react';
import { ArrowPathIcon } from '@heroicons/react/24/outline';
import api from '@/lib/api-client';
import { flash } from '@/lib/flash';
import {
    announceReportImportFinished,
    clearReportImport,
    onReportImportChange,
    readReportImport,
    type ReportImportTicket,
} from '@/lib/report-import';

const POLL_INTERVAL = 2500;
const MAX_DURATION = 60 * 60 * 1000;
const MAX_FAILURES = 3;

interface ImportState {
    status: 'queued' | 'processing' | 'finished' | 'failed';
    total?: number;
    imported?: number;
    skipped?: number;
    message?: string | null;
}

const STATUS_LABEL: Record<string, string> = {
    queued: 'Waiting in the queue',
    processing: 'Importing rows',
};

export default function ReportImportWatcher() {
    const [ticket, setTicket] = useState<ReportImportTicket | null>(() =>
        readReportImport(),
    );
    const [state, setState] = useState<ImportState | null>(null);

    useEffect(
        () => onReportImportChange(() => setTicket(readReportImport())),
        [],
    );

    useEffect(() => {
        if (!ticket) {
            setState(null);
            return undefined;
        }

        let cancelled = false;
        let timer: ReturnType<typeof setTimeout> | undefined;
        let failures = 0;

        async function poll() {
            if (!ticket) return;

            try {
                const { data } = await api.get(
                    `/admin/projects/${ticket.projectId}/sales-reports/import/${ticket.importId}`,
                );

                if (cancelled) return;

                failures = 0;

                const current: ImportState | undefined = data?.result;

                if (current?.status === 'finished') {
                    flash.success(current.message ?? 'Weekly reports imported.');
                    announceReportImportFinished(ticket.projectId);
                    clearReportImport();
                    return;
                }

                if (current?.status === 'failed') {
                    flash.error(
                        current.message ?? 'The report CSV could not be imported.',
                    );
                    clearReportImport();
                    return;
                }

                setState(current ?? null);
            } catch (error) {
                if (cancelled) return;

                if ((error as { response?: { status?: number } })?.response?.status === 404) {
                    announceReportImportFinished(ticket.projectId);
                    clearReportImport();
                    return;
                }

                failures += 1;

                if (failures >= MAX_FAILURES) {
                    flash.error('Could not check the report CSV import.');
                    clearReportImport();
                    return;
                }
            }

            if (Date.now() - ticket.startedAt > MAX_DURATION) {
                flash.error('The report CSV is still importing in the background.');
                clearReportImport();
                return;
            }

            timer = setTimeout(poll, POLL_INTERVAL);
        }

        timer = setTimeout(poll, POLL_INTERVAL);

        return () => {
            cancelled = true;
            if (timer) clearTimeout(timer);
        };
    }, [ticket?.projectId, ticket?.importId, ticket?.startedAt]);

    if (!ticket) return null;

    return (
        <div className="fixed bottom-6 right-4 z-40 sm:bottom-10 sm:right-10">
            <div className="flex max-w-[calc(100vw-2rem)] items-center gap-3 rounded-xl border-2 border-gray-100 bg-white px-4 py-3 shadow-xl sm:px-6 sm:py-4">
                <ArrowPathIcon className="h-6 w-6 shrink-0 animate-spin text-indigo-500" />
                <div className="min-w-0">
                    <p className="truncate text-sm font-medium text-gray-800">
                        Report CSV uploading in background
                    </p>
                    <p className="truncate text-xs text-gray-500">
                        {STATUS_LABEL[state?.status ?? 'queued'] ?? 'Importing rows'}
                        {state?.total ? ` — ${state.total} row(s)` : ''}
                    </p>
                </div>
            </div>
        </div>
    );
}
