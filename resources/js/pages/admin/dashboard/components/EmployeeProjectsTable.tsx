import { useCallback, useEffect, useMemo, useRef, useState, type ChangeEvent } from 'react';
import { router, usePage } from '@inertiajs/react';
import { DataTable, TableFilters, TableSelect, SalesProgress } from '@/components/ui';
import useStoredState from '@/hooks/useStoredState';
import api from '@/lib/api-client';
import { dataTablePagination, type ApiEnvelope, type SimpleResourcePaginator } from '@/lib/api-types';
import {
    BUSINESS_STATUS_OPTIONS,
    businessStatusBadgeStyle,
    businessStatusLabel,
} from '@/config/businessStatus';
import { flash, errorMessage } from '@/lib/flash';
import { formatDate, formatMoney } from '@/lib/format';
import type {
    BusinessStatusOption,
    ColumnDef,
    ProjectListRow,
} from '../types';

const HEALTH_BADGE: Record<string, string> = {
    green: 'bg-green-100 text-green-700',
    yellow: 'bg-yellow-100 text-yellow-700',
    red: 'bg-red-100 text-red-700',
    gray: 'bg-gray-100 text-gray-600',
};

const HEALTH_OPTIONS = [
    { value: 'upcoming', label: 'Upcoming' },
    { value: 'on_track', label: 'On Track' },
    { value: 'at_risk', label: 'At Risk' },
    { value: 'off_track', label: 'Off Track' },
];

export type EmployeeProjectsScope = 'assigned' | 'led';

interface EmployeeProjectsTableProps {
    from?: string;
    to?: string;
    scope?: EmployeeProjectsScope;
    title?: string;
    subtitle?: string;
    storageKey?: string;
}

export default function EmployeeProjectsTable({
    from = '',
    to = '',
    scope,
    title = 'My projects',
    subtitle,
    storageKey = 'employee-projects',
}: EmployeeProjectsTableProps) {
    const { auth } = usePage().props;
    const canViewClient =
        (auth?.user?.roles?.includes('super-admin') ?? false) ||
        (auth?.user?.permissions ?? []).includes('view project client');

    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const [perPage, setPerPage] = useStoredState(
        `datatable:${storageKey}:per_page`,
        10,
    );
    const [hiddenColumns, setHiddenColumns] = useStoredState<string[]>(
        `datatable:${storageKey}:hidden_columns`,
        [],
    );
    const [healthStatus, setHealthStatus] = useState('');
    const [businessStatusId, setBusinessStatusId] = useState('');
    const [sortColumn, setSortColumn] = useState('');
    const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('desc');
    const [paginator, setPaginator] = useState<SimpleResourcePaginator<ProjectListRow> | null>(null);
    const [loading, setLoading] = useState(true);
    const columns = useMemo<ColumnDef<ProjectListRow>[]>(
        () =>
            [
                {
                    key: 'project_name',
                    header: 'Project',
                    className: 'font-medium',
                    sortable: true,
                    render: (row) => (
                        <div className="flex max-w-56 flex-col">
                            <span className="truncate" title={row.project_name ?? undefined}>{row.project_name}</span>
                            {canViewClient && row.client && (
                                <span className="truncate text-xs text-gray-500" title={row.client ?? undefined}>
                                    {row.client}
                                    </span>
                            )}
                        </div>
                    ),
                },
                canViewClient && {
                    key: 'client',
                    header: 'Client',
                    render: (row) =>
                        row.client ? (
                            <div className="flex max-w-56 flex-col">
                                <span className="truncate" title={row.client ?? undefined}>{row.client}</span>
                                {row.client_email && (
                                    <span className="truncate text-xs text-gray-500" title={row.client_email ?? undefined}>
                                        {row.client_email}
                                        </span>
                                )}
                                {row.client_phone && (
                                    <span className="truncate text-xs text-gray-500" title={row.client_phone ?? undefined}>
                                        {row.client_phone}
                                        </span>
                                )}
                            </div>
                        ) : (
                            '—'
                        ),
                },
                {
                    key: 'company',
                    header: 'Company',
                    render: (row) => row.company ?? '—',
                },
                {
                    key: 'department',
                    header: 'Department',
                    render: (row) => row.department ?? '—',
                },
                {
                    key: 'team',
                    header: 'Team',
                    render: (row) => row.team ?? '—',
                },
                {
                    key: 'package_amount',
                    header: 'Package',
                    sortable: true,
                    className: 'text-right',
                    render: (row) => formatMoney(row.package_amount),
                },
                {
                    key: 'amount_due',
                    header: 'Due',
                    sortable: true,
                    className: 'text-right',
                    render: (row) => formatMoney(row.amount_due),
                },
                {
                    key: 'achieved_sales',
                    header: 'Sales (this month)',
                    sortable: true,
                    className: 'text-right',
                    render: (row) => (
                        <SalesProgress
                            achieved={row.achieved_sales}
                            target={row.monthly_target ?? row.sales_target}
                            projectType={row.project_type}
                        />
                    ),
                },
                {
                    key: 'health_status',
                    header: 'Health',
                    sortable: true,
                    render: (row) => (
                        <span
                            className={`inline-flex rounded-full px-2 py-1 text-xs font-medium ${
                                HEALTH_BADGE[row.health_color ?? ''] ?? HEALTH_BADGE.gray
                            }`}
                        >
                            {row.health_label}
                        </span>
                    ),
                },
                {
                    key: 'business_status',
                    header: 'Status',
                    sortable: true,
                    render: (row) => (
                        <span
                            style={businessStatusBadgeStyle(row.business_status)}
                            className="inline-flex rounded-full border px-2 py-1 text-xs font-medium"
                        >
                            {businessStatusLabel(row.business_status)}
                        </span>
                    ),
                },
                {
                    key: 'end_date',
                    header: 'Ends',
                    sortable: true,
                    render: (row) => formatDate(row.end_date),
                },
            ].filter(Boolean) as ColumnDef<ProjectListRow>[],
        [canViewClient],
    );

    const [debouncedSearch, setDebouncedSearch] = useState('');
    useEffect(() => {
        const timer = setTimeout(() => setDebouncedSearch(search), 300);
        return () => clearTimeout(timer);
    }, [search]);

    const isFirstRender = useRef(true);
    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }
        setPage(1);
    }, [
        debouncedSearch,
        healthStatus,
        businessStatusId,
        perPage,
        sortColumn,
        sortDirection,
        from,
        to,
        scope,
    ]);

    const fetchProjects = useCallback(async () => {
        setLoading(true);
        try {
            const { data } = await api.get<ApiEnvelope<SimpleResourcePaginator<ProjectListRow>>>('/admin/projects', {
                params: {
                    search: debouncedSearch || undefined,
                    health_status: healthStatus || undefined,
                    business_status: businessStatusId || undefined,
                    date_from: from || undefined,
                    date_to: to || undefined,
                    scope: scope || undefined,
                    page,
                    per_page: perPage,
                    sort: sortColumn,
                    direction: sortDirection,
                },
            });
            setPaginator(data.result);
        } catch (error) {
            flash.error(errorMessage(error, 'Could not load your projects.'));
        } finally {
            setLoading(false);
        }
    }, [
        debouncedSearch,
        healthStatus,
        businessStatusId,
        from,
        to,
        scope,
        page,
        perPage,
        sortColumn,
        sortDirection,
    ]);

    useEffect(() => {
        fetchProjects();
    }, [fetchProjects]);

    function changeSort(column: string) {
        const direction =
            sortColumn === column && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortColumn(column);
        setSortDirection(direction);
    }

    const activeFilters =
        (healthStatus ? 1 : 0) + (businessStatusId ? 1 : 0);

    return (
        <div className="mt-6 flex flex-col p-4">
            <DataTable
                title={
                    subtitle ? (
                        <span className="flex flex-col">
                            <span>{title}</span>
                            <span className="text-xs font-normal text-gray-500">
                                {subtitle}
                            </span>
                        </span>
                    ) : (
                        title
                    )
                }
                columns={columns}
                rows={paginator?.data ?? []}
                pagination={dataTablePagination(paginator)}
                onPageChange={setPage}
                search={search}
                onSearchChange={setSearch}
                perPage={perPage}
                onPerPageChange={setPerPage}
                hiddenColumns={hiddenColumns}
                onHiddenColumnsChange={setHiddenColumns}
                loading={loading}
                sort={{ column: sortColumn, direction: sortDirection }}
                onSort={changeSort}
                onRowClick={(row: ProjectListRow) => router.visit(`/admin/projects/${row.id}`)}
                filters={
                    <TableFilters
                        activeCount={activeFilters}
                        onClear={() => {
                            setHealthStatus('');
                            setBusinessStatusId('');
                        }}
                    >
                        <div>
                            <label className="mb-1 block text-xs font-medium text-gray-700">
                                Health
                            </label>
                            <TableSelect
                                value={healthStatus}
                                onChange={(e: ChangeEvent<HTMLSelectElement>) =>
                                    setHealthStatus(e.target.value)
                                }
                            >
                                <option value="">All health</option>
                                {HEALTH_OPTIONS.map((s) => (
                                    <option key={s.value} value={s.value}>
                                        {s.label}
                                    </option>
                                ))}
                            </TableSelect>
                        </div>
                        <div>
                            <label className="mb-1 block text-xs font-medium text-gray-700">
                                Status
                            </label>
                            <TableSelect
                                value={businessStatusId}
                                onChange={(e: ChangeEvent<HTMLSelectElement>) =>
                                    setBusinessStatusId(e.target.value)
                                }
                            >
                                <option value="">All statuses</option>
                                {BUSINESS_STATUS_OPTIONS.map((s) => (
                                    <option
                                        key={s.value}
                                        value={s.value}
                                    >
                                        {s.label}
                                    </option>
                                ))}
                            </TableSelect>
                        </div>
                    </TableFilters>
                }
            />
        </div>
    );
}
