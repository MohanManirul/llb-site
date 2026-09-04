import { ReactNode, useCallback, useEffect, useMemo, useState } from 'react';
import {
    ArrowDownTrayIcon,
    EyeIcon,
    SignalIcon,
    UsersIcon,
} from '@heroicons/react/24/outline';
import DashboardLayout from '@/components/common/DashboardLayout';
import PageHeader from '@/components/common/PageHeader';
import {
    DataTable,
    StatusBadge,
    TableFilters,
    TableSelect,
    type BadgeTone,
} from '@/components/ui';
import api from '@/lib/api-client';
import type { ApiEnvelope } from '@/lib/api-types';
import { errorMessage, flash } from '@/lib/flash';
import { formatDateTime, formatNumber, toDateInput } from '@/lib/format';
import useResourceIndex from '@/hooks/useResourceIndex';

interface LiveReport {
    online_now: number;
    window_minutes: number;
    visitors_today: number;
    downloads_today: number;
    top_pages: Array<{ path: string; visitors: number }>;
}

interface DownloadRow {
    id: number;
    title_bn: string;
    title_en: string | null;
    type: string;
    subject: string | null;
    download_count: number;
    period_downloads: number;
    unique_visitors: number;
    view_count: number;
    last_downloaded_at: string | null;
}

interface Column {
    key: string;
    header: string;
    className?: string;
    sortable?: boolean;
    render?: (row: DownloadRow) => ReactNode;
}

const TYPE_TONES: Record<string, BadgeTone> = {
    suggestion: 'indigo',
    book: 'blue',
    note: 'green',
};

const LIVE_REFRESH_MS = 30_000;

function StatTile({
    icon,
    label,
    value,
    accent = false,
}: {
    icon: ReactNode;
    label: string;
    value: string;
    accent?: boolean;
}) {
    return (
        <div className="flex items-center gap-3 rounded-card border border-hairline bg-white p-4 shadow-sm">
            <span
                className={
                    'flex h-10 w-10 shrink-0 items-center justify-center rounded-chip ' +
                    (accent ? 'bg-emerald-50 text-emerald-600' : 'bg-brand/5 text-brand')
                }
            >
                {icon}
            </span>
            <div>
                <p className="text-2xl font-bold text-ink">{value}</p>
                <p className="text-xs text-ink-muted">{label}</p>
            </div>
        </div>
    );
}

export default function ReportsPage() {
    const [live, setLive] = useState<LiveReport | null>(null);
    const [type, setType] = useState('');
    const [dateFrom, setDateFrom] = useState('');
    const [dateTo, setDateTo] = useState('');

    const loadLive = useCallback(() => {
        api.get<ApiEnvelope<LiveReport>>('/admin/reports/live')
            .then(({ data }) => setLive(data.result))
            .catch((error) => flash.error(errorMessage(error, 'Could not load live visitors.')));
    }, []);

    useEffect(() => {
        loadLive();

        const timer = setInterval(loadLive, LIVE_REFRESH_MS);

        return () => clearInterval(timer);
    }, [loadLive]);

    const filters = useMemo(
        () => ({
            type: type || undefined,
            date_from: dateFrom || undefined,
            date_to: dateTo || undefined,
        }),
        [type, dateFrom, dateTo],
    );

    const { tableProps } = useResourceIndex<DownloadRow>({
        url: '/admin/reports/downloads',
        storageKey: 'download-report',
        errorMessage: 'Could not load the download report.',
        filters,
    });

    const activeFilterCount = (type ? 1 : 0) + (dateFrom || dateTo ? 1 : 0);

    const columns = useMemo<Column[]>(
        () => [
            {
                key: 'title_bn',
                header: 'Material',
                className: 'font-medium',
                render: (row) => (
                    <span className="flex flex-col">
                        <span>{row.title_bn}</span>
                        <span className="text-xs text-gray-500">{row.subject ?? ''}</span>
                    </span>
                ),
            },
            {
                key: 'type',
                header: 'Type',
                render: (row) => (
                    <StatusBadge status={row.type} tone={TYPE_TONES[row.type] ?? 'gray'} />
                ),
            },
            {
                key: 'download_count',
                header: 'Total downloads',
                sortable: true,
                render: (row) => formatNumber(row.download_count),
            },
            {
                key: 'period_downloads',
                header: 'In period',
                sortable: true,
                render: (row) => formatNumber(row.period_downloads),
            },
            {
                key: 'unique_visitors',
                header: 'Unique people',
                render: (row) => formatNumber(row.unique_visitors),
            },
            {
                key: 'view_count',
                header: 'Views',
                sortable: true,
                render: (row) => formatNumber(row.view_count),
            },
            {
                key: 'last_downloaded_at',
                header: 'Last download',
                render: (row) => formatDateTime(row.last_downloaded_at),
            },
        ],
        [],
    );

    return (
        <>
            <PageHeader title="Reports" />

            <div className="mb-5 grid gap-3 sm:grid-cols-3">
                <StatTile
                    icon={<SignalIcon className="h-5 w-5" />}
                    label={`Online now (last ${live?.window_minutes ?? 5} min)`}
                    value={live ? formatNumber(live.online_now) : '…'}
                    accent
                />
                <StatTile
                    icon={<UsersIcon className="h-5 w-5" />}
                    label="Visitors today"
                    value={live ? formatNumber(live.visitors_today) : '…'}
                />
                <StatTile
                    icon={<ArrowDownTrayIcon className="h-5 w-5" />}
                    label="Downloads today"
                    value={live ? formatNumber(live.downloads_today) : '…'}
                />
            </div>

            {live && live.top_pages.length > 0 && (
                <div className="mb-5 rounded-card border border-hairline bg-white p-4 shadow-sm">
                    <p className="mb-2 flex items-center gap-1.5 text-sm font-semibold text-ink">
                        <EyeIcon className="h-4 w-4" />
                        What visitors are reading right now
                    </p>
                    <ul className="space-y-1 text-sm">
                        {live.top_pages.map((page) => (
                            <li key={page.path} className="flex justify-between gap-3">
                                <span className="truncate text-ink">{page.path}</span>
                                <span className="shrink-0 text-ink-muted">
                                    {formatNumber(page.visitors)}
                                </span>
                            </li>
                        ))}
                    </ul>
                </div>
            )}

            <div className="flex flex-col">
                <DataTable
                    title="Downloads by material"
                    columns={columns}
                    {...tableProps}
                    filters={
                        <TableFilters
                            activeCount={activeFilterCount}
                            onClear={() => {
                                setType('');
                                setDateFrom('');
                                setDateTo('');
                            }}
                        >
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-700">
                                    Type
                                </label>
                                <TableSelect value={type} onChange={(e) => setType(e.target.value)}>
                                    <option value="">All types</option>
                                    <option value="suggestion">Suggestion</option>
                                    <option value="book">Book</option>
                                    <option value="note">Class Note</option>
                                </TableSelect>
                            </div>

                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-700">
                                    From
                                </label>
                                <input
                                    type="date"
                                    value={dateFrom}
                                    onChange={(e) => setDateFrom(toDateInput(e.target.value))}
                                    className="w-full rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-sm"
                                />
                            </div>

                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-700">
                                    To
                                </label>
                                <input
                                    type="date"
                                    value={dateTo}
                                    onChange={(e) => setDateTo(toDateInput(e.target.value))}
                                    className="w-full rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-sm"
                                />
                            </div>
                        </TableFilters>
                    }
                />
            </div>
        </>
    );
}

ReportsPage.layout = (page: ReactNode) => <DashboardLayout wide>{page}</DashboardLayout>;
