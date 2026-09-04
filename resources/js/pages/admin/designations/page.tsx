import { useCallback, useEffect, useMemo, useRef, useState, type ReactNode } from 'react';
import { PlusIcon, PencilSquareIcon } from '@heroicons/react/24/outline';
import DashboardLayout from '@/components/common/DashboardLayout';
import AdminPageLayout from '@/components/admin/AdminPageLayout';
import {
    Button,
    ConfirmationModal,
    DataTable,
    DeleteButton,
    TableFilters,
    TableSelect,
} from '@/components/ui';
import DesignationFormModal from './components/DesignationFormModal';
import useDeleteResource from '@/hooks/useDeleteResource';
import usePermissions from '@/hooks/usePermissions';
import useStoredState from '@/hooks/useStoredState';
import api from '@/lib/api-client';
import { dataTablePagination, type ApiEnvelope, type SimpleResourcePaginator } from '@/lib/api-types';
import { flash, errorMessage } from '@/lib/flash';
import { formatDate } from '@/lib/format';
import type { Designation } from './types';

interface Column {
    key: string;
    header: string;
    className?: string;
    sortable?: boolean;
    render?: (row: Designation) => ReactNode;
}

export default function DesignationsIndex() {
    const { can } = usePermissions();
    const canDelete = can('delete designations');

    const [search, setSearch] = useState('');
    const [isActive, setIsActive] = useState('');
    const [sortColumn, setSortColumn] = useState('');
    const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('desc');
    const [page, setPage] = useState(1);
    const [perPage, setPerPage] = useStoredState(
        'datatable:designations:per_page',
        10
    );
    const [hiddenColumns, setHiddenColumns] = useStoredState<string[]>(
        'datatable:designations:hidden_columns',
        []
    );

    const [paginator, setPaginator] = useState<SimpleResourcePaginator<Designation> | null>(null);
    const [loading, setLoading] = useState(true);

    const [formOpen, setFormOpen] = useState(false);
    const [formDesignation, setFormDesignation] = useState<Designation | null>(null);
    const [formKey, setFormKey] = useState(0);

    const openForm = useCallback((designation: Designation | null) => {
        setFormDesignation(designation);
        setFormKey((key) => key + 1);
        setFormOpen(true);
    }, []);

    const remove = useDeleteResource<Designation>({
        url: (designation) => `/admin/designations/${designation.id}`,
        onDeleted: () => fetchDesignations(),
        successMessage: 'Designation deleted successfully.',
        errorMessage: 'Could not delete this designation.',
    });

    const columns = useMemo<Column[]>(
        () => [
            {
                key: 'name',
                header: 'Name',
                className: 'font-medium',
                sortable: true,
                render: (row) => row.name,
            },
            {
                key: 'created_at',
                header: 'Created',
                sortable: true,
                render: (row) => formatDate(row.created_at),
            },
            {
                key: 'is_active',
                header: 'Status',
                sortable: true,
                render: (row) => (
                    <span
                        className={`inline-flex rounded-full px-2 py-1 text-xs font-medium ${
                            row.is_active
                                ? 'bg-green-100 text-green-700'
                                : 'bg-red-100 text-red-700'
                        }`}
                    >
                        {row.is_active ? 'Active' : 'Inactive'}
                    </span>
                ),
            },
            {
                key: 'actions',
                header: 'Actions',
                className: 'text-right',
                render: (row) => (
                    <div className="flex items-center justify-end gap-3">
                        <button
                            type="button"
                            onClick={() => openForm(row)}
                            className="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800"
                        >
                            <PencilSquareIcon className="h-4 w-4" />
                            Edit
                        </button>

                        {canDelete && (
                            <DeleteButton onDelete={() => remove.request(row)} />
                        )}
                    </div>
                ),
            },
        ],
        [openForm, canDelete, remove.request],
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
    }, [debouncedSearch, isActive, perPage, sortColumn, sortDirection]);

    const fetchDesignations = useCallback(async () => {
        setLoading(true);
        try {
            const { data } = await api.get<ApiEnvelope<SimpleResourcePaginator<Designation>>>('/admin/designations', {
                params: {
                    search: debouncedSearch,
                    is_active: isActive,
                    per_page: perPage,
                    sort: sortColumn,
                    direction: sortDirection,
                    page,
                },
            });
            setPaginator(data.result);
        } catch (error) {
            flash.error(errorMessage(error, 'Could not load designations.'));
        } finally {
            setLoading(false);
        }
    }, [debouncedSearch, isActive, perPage, sortColumn, sortDirection, page]);

    useEffect(() => {
        fetchDesignations();
    }, [fetchDesignations]);

    function changeSort(column: string) {
        const direction =
            sortColumn === column && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortColumn(column);
        setSortDirection(direction);
    }

    return (
        <AdminPageLayout
            action={
                <Button size="sm" onClick={() => openForm(null)}>
                    <PlusIcon className="h-4 w-4" />
                    Create
                </Button>
            }
        >
            <div className="flex flex-col p-4">
                <DataTable
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
                    filters={
                        <TableFilters
                            activeCount={isActive ? 1 : 0}
                            onClear={() => setIsActive('')}
                        >
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-700">
                                    Status
                                </label>
                                <TableSelect
                                    value={isActive}
                                    onChange={(e) => setIsActive(e.target.value)}
                                >
                                    <option value="">Status</option>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </TableSelect>
                            </div>
                        </TableFilters>
                    }
                />
            </div>

            <DesignationFormModal
                key={formKey}
                show={formOpen}
                designation={formDesignation}
                onClose={() => setFormOpen(false)}
                onSaved={() => {
                    setFormOpen(false);
                    fetchDesignations();
                }}
            />

            <ConfirmationModal
                show={remove.pending !== null}
                onClose={remove.cancel}
                onConfirm={remove.confirm}
                processing={remove.deleting}
                title="Delete designation"
                confirmText="Delete"
            >
                Are you sure you want to delete{' '}
                <span className="font-medium">{remove.pending?.name}</span>?
                This action cannot be undone.
            </ConfirmationModal>
        </AdminPageLayout>
    );
}

DesignationsIndex.layout = (page: ReactNode) => <DashboardLayout wide>{page}</DashboardLayout>;
