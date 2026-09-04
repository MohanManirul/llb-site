import { useCallback, useEffect, useMemo, useState, type ComponentType, type ReactNode } from 'react';
import {
    ArrowDownTrayIcon,
    ArrowPathIcon,
    ArrowTrendingDownIcon,
    ArrowTrendingUpIcon,
    ArrowUpTrayIcon,
    ChevronRightIcon,
    CreditCardIcon,
    CubeIcon,
    CurrencyBangladeshiIcon,
    DocumentTextIcon,
    TableCellsIcon,
} from '@heroicons/react/24/outline';
import api from '@/lib/api-client';
import { flash, errorMessage } from '@/lib/flash';
import { DateRangeInput, Pagination, TableSelect, type PaginationLinks } from '@/components/ui';
import useStoredState from '@/hooks/useStoredState';
import SubmitReportModal from './SubmitReportModal';
import ReportCsvUploadModal, { REPORT_CSV_SAMPLE_URL } from './ReportCsvUploadModal';
import { onReportImportFinished, startReportImport } from '@/lib/report-import';
import { formatDate, formatMoney, formatNumber, formatQuantity } from '@/lib/format';
import type { SalesReport } from '../types';

const PER_PAGE_OPTIONS = [10, 25, 50, 100];

interface SalesChange {
    percent: number;
    increased: boolean;
    flat: boolean;
}

function salesChange(report: SalesReport, previous?: SalesReport): SalesChange | null {
    if (!previous) return null;

    const current = Number(report.total_sales);
    const before = Number(previous.total_sales);

    if (!Number.isFinite(current) || !Number.isFinite(before) || before === 0) {
        return null;
    }

    const percent = ((current - before) / before) * 100;

    return { percent, increased: percent > 0, flat: percent === 0 };
}

function TrendBadge({ change }: { change: SalesChange | null }) {
    if (!change || change.flat) return null;

    const TrendIcon = change.increased
        ? ArrowTrendingUpIcon
        : ArrowTrendingDownIcon;

    return (
        <span
            className={
                'inline-flex shrink-0 items-center gap-1 whitespace-nowrap rounded-full px-2 py-0.5 text-[11px] font-medium ' +
                (change.increased
                    ? 'bg-green-50 text-green-700'
                    : 'bg-red-50 text-red-600')
            }
        >
            <TrendIcon className="h-3.5 w-3.5" />
            {Math.abs(change.percent).toFixed(1)}%{' '}
            {change.increased ? 'increase' : 'decrease'}
        </span>
    );
}

function Collapsible({ open, children }: { open: boolean; children?: ReactNode }) {
    return (
        <div
            className={
                'grid transition-all duration-300 ease-out motion-reduce:transition-none ' +
                (open ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0')
            }
        >
            <div className="overflow-hidden">{children}</div>
        </div>
    );
}

interface CellProps {
    label: string;
    value: ReactNode;
    expanded: boolean;
    className?: string;
    children?: ReactNode;
}

function Cell({ label, value, expanded, className = '', children }: CellProps) {
    return (
        <div className="min-w-0">
            <div
                className={
                    'grid transition-all duration-300 ease-out motion-reduce:transition-none ' +
                    (expanded
                        ? 'grid-rows-[1fr] opacity-100'
                        : 'grid-rows-[1fr] opacity-100 lg:grid-rows-[0fr] lg:opacity-0')
                }
            >
                <div className="overflow-hidden">
                    <p className="mb-1 truncate text-xs font-medium text-gray-500">
                        {label}
                    </p>
                </div>
            </div>

            <div className="flex min-w-0 flex-nowrap items-center gap-x-1.5">
                <span
                    className={
                        'truncate whitespace-nowrap text-sm font-medium text-gray-800 ' +
                        className
                    }
                >
                    {value}
                </span>
                {children}
            </div>
        </div>
    );
}

interface StatCardProps {
    label: string;
    value: ReactNode;
    icon: ComponentType<{ className?: string }>;
}

function StatCard({ label, value, icon: Icon }: StatCardProps) {
    return (
        <div className="rounded-xl border border-gray-200 bg-white px-5 py-4 shadow-sm">
            <div className="flex items-start justify-between gap-3">
                <p className="text-sm text-gray-500">{label}</p>
                <Icon className="h-5 w-5 shrink-0 text-gray-400" />
            </div>
            <p className="mt-2 flex items-center truncate text-xl font-semibold text-gray-900">
                {value}
            </p>
        </div>
    );
}

function SummaryCards({ reports }: { reports: SalesReport[] }) {
    const totals = useMemo(
        () =>
            reports.reduce(
                (sum, report) => ({
                    sales: sum.sales + (Number(report.total_sales) || 0),
                    quantity:
                        sum.quantity + (Number(report.total_order_quantity) || 0),
                    spent: sum.spent + (Number(report.total_amount_spent) || 0),
                }),
                { sales: 0, quantity: 0, spent: 0 },
            ),
        [reports],
    );

    return (
        <div className="mb-4 grid grid-cols-1 gap-4 md:grid-cols-3">
            <StatCard
                label="Total Sales"
                value={formatMoney(totals.sales)}
                icon={CurrencyBangladeshiIcon}
            />
            <StatCard
                label="Total Order Quantity"
                value={formatNumber(totals.quantity)}
                icon={CubeIcon}
            />
            <StatCard
                label="Total Amount Spent"
                value={formatMoney(totals.spent)}
                icon={CreditCardIcon}
            />
        </div>
    );
}

interface ReportRowProps {
    report: SalesReport;
    expanded: boolean;
    onToggle: () => void;
    previous?: SalesReport;
}

function ReportRow({ report, expanded, onToggle, previous }: ReportRowProps) {
    const change = salesChange(report, previous);
    const trendUp = !change || change.increased || change.flat;

    return (
        <div className="group">
            <div
                onClick={onToggle}
                onKeyDown={(e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        onToggle();
                    }
                }}
                role="button"
                tabIndex={0}
                aria-expanded={expanded}
                className="flex cursor-pointer items-center gap-3 px-4 py-4 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-indigo-200 sm:gap-4 sm:px-6"
            >
                <div className="grid min-w-0 flex-1 grid-cols-2 gap-x-0 gap-y-3 sm:grid-cols-3 lg:grid-cols-5">
                    <Cell
                        label="Week Start"
                        value={formatDate(report.week_start)}
                        expanded={expanded}
                    />
                    <Cell
                        label="Week End"
                        value={formatDate(report.week_end)}
                        expanded={expanded}
                    />
                    <Cell
                        label="Total Sales"
                        value={formatMoney(report.total_sales)}
                        expanded={expanded}
                        className={trendUp ? 'text-green-600' : 'text-red-500'}
                    />
                    <Cell
                        label="Total Order Quantity"
                        value={formatQuantity(report.total_order_quantity)}
                        expanded={expanded}
                    />
                    <Cell
                        label="Total Amount Spent"
                        value={formatMoney(report.total_amount_spent)}
                        expanded={expanded}
                    >
                        <TrendBadge change={change} />
                    </Cell>
                </div>

                <span className="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-gray-200 text-gray-400 transition-colors group-hover:bg-gray-50">
                    <ChevronRightIcon
                        className={
                            'h-4 w-4 transition-transform duration-300 ease-out motion-reduce:transition-none ' +
                            (expanded ? 'rotate-90' : 'rotate-0')
                        }
                    />
                </span>
            </div>

            <Collapsible open={expanded}>
                <div className="mx-4 mb-4 flex items-start gap-3 rounded-lg bg-gray-50 p-4 sm:mx-6">
                    <span className="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-gray-500 shadow-sm">
                        <DocumentTextIcon className="h-5 w-5" />
                    </span>
                    <div className="min-w-0 flex-1">
                        <p className="text-xs font-medium text-gray-500">
                            Description
                        </p>
                        <p className="mt-1 text-sm text-gray-700">
                            {report.description || 'No description for this week.'}
                        </p>
                    </div>
                </div>
            </Collapsible>
        </div>
    );
}

function buildLinks(page: number, lastPage: number): PaginationLinks {
    if (lastPage <= 1) return {};

    return {
        prev: page > 1 ? `?page=${page - 1}` : null,
        next: page < lastPage ? `?page=${page + 1}` : null,
    };
}

interface WeeklyReportsProps {
    submitOpen?: boolean;
    onSubmitOpenChange?: (open: boolean) => void;
    csvOpen?: boolean;
    onCsvOpenChange?: (open: boolean) => void;
    projectId: string;
    canSubmit?: boolean;
}

export default function WeeklyReports({
    projectId,
    canSubmit = false,
    submitOpen,
    onSubmitOpenChange,
    csvOpen,
    onCsvOpenChange,
}: WeeklyReportsProps) {
    const [reports, setReports] = useState<SalesReport[]>([]);
    const [reportsLoading, setReportsLoading] = useState(true);
    const [openId, setOpenId] = useState<number | string | null>(null);
    const [ownSubmitting, setOwnSubmitting] = useState(false);
    const [ownCsvOpen, setOwnCsvOpen] = useState(false);
    const hostControlsSubmit = submitOpen !== undefined;
    const submitting = hostControlsSubmit ? submitOpen : ownSubmitting;
    const uploadingCsv = csvOpen !== undefined ? csvOpen : ownCsvOpen;

    function setSubmitting(open: boolean) {
        if (hostControlsSubmit) {
            onSubmitOpenChange?.(open);
        } else {
            setOwnSubmitting(open);
        }
    }

    function setUploadingCsv(open: boolean) {
        if (csvOpen !== undefined) {
            onCsvOpenChange?.(open);
        } else {
            setOwnCsvOpen(open);
        }
    }
    const [page, setPage] = useState(1);
    const [perPage, setPerPage] = useStoredState(
        'datatable:sales-reports:per_page',
        10,
    );

    const [weekFrom, setWeekFrom] = useState('');
    const [weekTo, setWeekTo] = useState('');

    const fetchReports = useCallback(async () => {
        setReportsLoading(true);

        try {
            const { data } = await api.get(`/admin/projects/${projectId}/sales-reports`,
                {
                    params: {
                        week_start: weekFrom,
                        week_end: weekTo,
                    },
                },
            );
            setReports(data?.result?.data ?? []);
        } catch (error) {
            flash.error(errorMessage(error, 'Could not load weekly reports.'));
            setReports([]);
        } finally {
            setReportsLoading(false);
        }
    }, [projectId, weekFrom, weekTo]);

    useEffect(() => {
        fetchReports();
    }, [fetchReports]);

    useEffect(
        () =>
            onReportImportFinished((finishedProjectId) => {
                if (String(finishedProjectId) === String(projectId)) {
                    fetchReports();
                }
            }),
        [projectId, fetchReports],
    );

    function changeWeekRange(from: string, to: string) {
        setWeekFrom(from);
        setWeekTo(to);
        setOpenId(null);
        setPage(1);
    }

    function changePerPage(value: number) {
        setPerPage(value);
        setOpenId(null);
        setPage(1);
    }

    const hasActiveRange = Boolean(weekFrom && weekTo);

    const previousWeeks = useMemo(
        () => new Map(reports.map((report, index) => [report, reports[index + 1]])),
        [reports],
    );

    const lastPage = Math.max(1, Math.ceil(reports.length / perPage));

    useEffect(() => {
        if (page > lastPage) setPage(lastPage);
    }, [page, lastPage]);

    const visible = useMemo(() => {
        const start = (page - 1) * perPage;
        return reports.slice(start, start + perPage);
    }, [reports, page, perPage]);

    const from = reports.length === 0 ? 0 : (page - 1) * perPage + 1;
    const to = Math.min(page * perPage, reports.length);

    return (
        <section className="mb-6">
            {canSubmit && !hostControlsSubmit && (
                <div className="mb-4 flex flex-wrap items-center justify-end gap-3">
                    <a
                        href={REPORT_CSV_SAMPLE_URL}
                        download
                        className="inline-flex items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50/60 px-4 py-2 text-sm font-medium text-indigo-600 shadow-sm transition hover:border-indigo-300 hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-1"
                    >
                        <ArrowDownTrayIcon className="h-4 w-4" />
                        Sample CSV
                    </a>

                    <button
                        type="button"
                        onClick={() => setUploadingCsv(true)}
                        className="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-1"
                    >
                        CSV Upload
                        <TableCellsIcon className="h-4 w-4" />
                    </button>

                    <button
                        type="button"
                        onClick={() => setSubmitting(true)}
                        className="inline-flex items-center gap-2 rounded-lg bg-indigo-500 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-1"
                    >
                        Single Submit
                        <ArrowUpTrayIcon className="h-4 w-4" />
                    </button>
                </div>
            )}

            <SummaryCards reports={reports} />

            <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <DateRangeInput
                    from={weekFrom}
                    to={weekTo}
                    onChange={changeWeekRange}
                    placeholder="All weeks"
                />

                <div className="flex items-center gap-2">
                    <span className="whitespace-nowrap text-sm text-gray-600">
                        Show per page
                    </span>
                    <TableSelect
                        className="w-20"
                        value={perPage}
                        onChange={(e: React.ChangeEvent<HTMLSelectElement>) => changePerPage(Number(e.target.value))}
                    >
                        {PER_PAGE_OPTIONS.map((n) => (
                            <option key={n} value={n}>
                                {n}
                            </option>
                        ))}
                    </TableSelect>
                </div>
            </div>

            <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                {reportsLoading && reports.length === 0 && (
                    <div className="flex items-center justify-center gap-2 px-6 py-12 text-sm text-gray-500">
                        <ArrowPathIcon className="h-4 w-4 animate-spin" />
                        Loading weekly reports…
                    </div>
                )}

                {!(reportsLoading && reports.length === 0) &&
                    visible.length === 0 && (
                        <div className="px-6 py-12 text-center text-sm text-gray-500">
                            {hasActiveRange
                                ? 'No weekly reports found for the selected range.'
                                : 'No weekly reports yet.'}
                        </div>
                    )}

                <div className="divide-y divide-gray-100">
                    {visible.map((report, index) => {
                        const key = report.id ?? `${page}-${index}`;

                        return (
                            <ReportRow
                                key={key}
                                report={report}
                                expanded={openId === key}
                                onToggle={() =>
                                    setOpenId(openId === key ? null : key)
                                }
                                previous={previousWeeks.get(report)}
                            />
                        );
                    })}
                </div>
            </div>

            {reports.length > 0 && (
                <div className="mt-4 flex flex-col gap-3 text-sm text-gray-600 sm:flex-row sm:items-center sm:justify-between">
                    <span>
                        Showing {from} to {to} of {reports.length} results
                    </span>
                    <Pagination
                        links={buildLinks(page, lastPage)}
                        onPageChange={setPage}
                    />
                </div>
            )}

            {canSubmit && (
                <SubmitReportModal
                    show={submitting}
                    projectId={projectId}
                    onClose={() => setSubmitting(false)}
                    onCreated={(report) =>
                        setReports((current) =>
                            report ? [report, ...current] : current,
                        )
                    }
                />
            )}

            {canSubmit && (
                <ReportCsvUploadModal
                    show={uploadingCsv}
                    projectId={projectId}
                    onQueued={(importId) => startReportImport(projectId, importId)}
                    onClose={() => setUploadingCsv(false)}
                />
            )}
        </section>
    );
}
