import { useEffect, useMemo, useState, type ReactNode } from 'react';
import { TrashIcon } from '@heroicons/react/24/outline';
import DashboardLayout from '@/components/common/DashboardLayout';
import AdminPageLayout from '@/components/admin/AdminPageLayout';
import { ConfirmationModal, DataTable, TableFilters, TableSelect } from '@/components/ui';
import usePermissions from '@/hooks/usePermissions';
import useResourceIndex from '@/hooks/useResourceIndex';
import api from '@/lib/api-client';
import { type ApiEnvelope } from '@/lib/api-types';
import { flash, errorMessage } from '@/lib/flash';

interface ActivityLog {
    id: number;
    description: string;
    subject_type: string | null;
    type: string;
    causer: string | null;
    impersonator: string | null;
    created_at: string;
}

interface FilterOption {
    value: string;
    label: string;
}

interface FilterOptions {
    types: FilterOption[];
    subject_types: FilterOption[];
}

interface Column {
    key: string;
    header: string;
    className?: string;
    sortable?: boolean;
    render?: (row: ActivityLog) => ReactNode;
}

function formatDateTime(value?: string | null) {
    if (!value) return '—';
    return new Date(value.replace(' ', 'T')).toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export default function ActivityLogsIndex() {
    const { can } = usePermissions();
    const canDelete = can('delete activity logs');

    const [type, setType] = useState('');
    const [subjectType, setSubjectType] = useState('');
    const [dateFrom, setDateFrom] = useState('');
    const [dateTo, setDateTo] = useState('');

    const { tableProps, refetch } = useResourceIndex<ActivityLog>({
        url: '/admin/activity-logs',
        storageKey: 'activity-logs',
        defaultSort: { column: 'created_at', direction: 'desc' },
        filters: {
            type,
            subject_type: subjectType,
            date_from: dateFrom,
            date_to: dateTo,
        },
        errorMessage: 'Could not load the activity log.',
    });

    const [options, setOptions] = useState<FilterOptions>({ types: [], subject_types: [] });

    const [pendingDelete, setPendingDelete] = useState<ActivityLog | null>(null);
    const [deleting, setDeleting] = useState(false);

    const columns = useMemo<Column[]>(
        () => [
            {
                key: 'description',
                header: 'Description',
                className: 'font-medium',
                render: (row) => row.description,
            },
            {
                key: 'subject_type',
                header: 'Subject',
                sortable: true,
                render: (row) => row.subject_type ?? 'N/A',
            },
            {
                key: 'type',
                header: 'Type',
                sortable: true,
                render: (row) => (
                    <span className="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">
                        {row.type}
                    </span>
                ),
            },
            {
                key: 'causer',
                header: 'Performer',
                render: (row) =>
                    row.impersonator ? (
                        <span className="inline-flex items-center gap-1.5">
                            <span>{row.causer ?? 'System'}</span>
                            <span className="whitespace-nowrap rounded-full bg-amber-50 px-1.5 py-0.5 text-xs font-medium text-amber-700">
                                via {row.impersonator}
                            </span>
                        </span>
                    ) : (
                        row.causer ?? 'System'
                    ),
            },
            {
                key: 'created_at',
                header: 'Date & Time',
                sortable: true,
                render: (row) => formatDateTime(row.created_at),
            },
            ...(canDelete
                ? [
                      {
                          key: 'actions',
                          header: 'Actions',
                          className: 'text-right',
                          render: (row: ActivityLog) =>
                              row.subject_type === 'ActivityLog' ? null : (
                                  <button
                                      type="button"
                                      onClick={() => setPendingDelete(row)}
                                      className="inline-flex items-center gap-1 text-red-600 hover:text-red-800"
                                      aria-label="Delete activity log"
                                  >
                                      <TrashIcon className="h-4 w-4" />
                                  </button>
                              ),
                      },
                  ]
                : []),
        ],
        [canDelete],
    );

    useEffect(() => {
        api.get<ApiEnvelope<FilterOptions>>('/admin/activity-logs/filters')
            .then(({ data }) => setOptions(data.result))
            .catch(() => {
            });
    }, []);

    function clearFilters() {
        setType('');
        setSubjectType('');
        setDateFrom('');
        setDateTo('');
    }

    const activeFilterCount = [type, subjectType, dateFrom, dateTo].filter(Boolean).length;

    async function confirmDelete() {
        if (!pendingDelete) return;

        setDeleting(true);
        try {
            const { data } = await api.delete(`/admin/activity-logs/${pendingDelete.id}`);
            flash.success(data?.message ?? 'Activity log deleted successfully.');
            setPendingDelete(null);
            await refetch();
        } catch (error) {
            flash.error(errorMessage(error, 'Could not delete the activity log.'));
        } finally {
            setDeleting(false);
        }
    }

    return (
        <AdminPageLayout>
            <div className="flex flex-col p-4">
                <DataTable
                    columns={columns}
                    {...tableProps}
                    filters={
                        <TableFilters
                            activeCount={activeFilterCount}
                            onClear={clearFilters}
                        >
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-700">
                                    Type
                                </label>
                                <TableSelect
                                    value={type}
                                    onChange={(e) => setType(e.target.value)}
                                >
                                    <option value="">All types</option>
                                    {options.types?.map((option) => (
                                        <option key={option.value} value={option.value}>
                                            {option.label}
                                        </option>
                                    ))}
                                </TableSelect>
                            </div>

                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-700">
                                    Subject
                                </label>
                                <TableSelect
                                    value={subjectType}
                                    onChange={(e) => setSubjectType(e.target.value)}
                                >
                                    <option value="">All subjects</option>
                                    {options.subject_types?.map((option) => (
                                        <option key={option.value} value={option.value}>
                                            {option.label}
                                        </option>
                                    ))}
                                </TableSelect>
                            </div>

                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-700">
                                    From
                                </label>
                                <input
                                    type="date"
                                    value={dateFrom}
                                    onChange={(e) => setDateFrom(e.target.value)}
                                    className="w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm"
                                />
                            </div>

                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-700">
                                    To
                                </label>
                                <input
                                    type="date"
                                    value={dateTo}
                                    onChange={(e) => setDateTo(e.target.value)}
                                    className="w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm"
                                />
                            </div>
                        </TableFilters>
                    }
                />
            </div>

            <ConfirmationModal
                show={pendingDelete !== null}
                onClose={() => setPendingDelete(null)}
                onConfirm={confirmDelete}
                processing={deleting}
                title="Delete activity log"
                confirmText="Delete"
            >
                Are you sure you want to delete this activity log? This action
                cannot be undone.
            </ConfirmationModal>
        </AdminPageLayout>
    );
}

ActivityLogsIndex.layout = (page: ReactNode) => <DashboardLayout wide>{page}</DashboardLayout>;
