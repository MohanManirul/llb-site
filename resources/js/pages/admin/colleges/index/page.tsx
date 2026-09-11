import { ReactNode, useMemo, useState } from 'react';
import { Link } from '@inertiajs/react';
import { PencilSquareIcon, PlusIcon } from '@heroicons/react/24/outline';
import DashboardLayout from '@/components/common/DashboardLayout';
import PageHeader from '@/components/common/PageHeader';
import {
    Button,
    ConfirmationModal,
    DataTable,
    DeleteButton,
    StatusBadge,
    TableFilters,
    TableSelect,
} from '@/components/ui';
import useDeleteResource from '@/hooks/useDeleteResource';
import usePermissions from '@/hooks/usePermissions';
import useResourceIndex from '@/hooks/useResourceIndex';
import { College } from '../types';

interface Column {
    key: string;
    header: string;
    className?: string;
    sortable?: boolean;
    render?: (row: College) => ReactNode;
}

export default function CollegesIndex() {
    const { can } = usePermissions();
    const canCreate = can('create colleges');
    const canEdit = can('edit colleges');
    const canDelete = can('delete colleges');

    const [isActive, setIsActive] = useState('');

    const filters = useMemo(
        () => ({
            is_active: isActive || undefined,
        }),
        [isActive],
    );

    const { tableProps, refetch } = useResourceIndex<College>({
        url: '/admin/colleges',
        storageKey: 'colleges',
        errorMessage: 'Could not load colleges.',
        filters,
    });

    const remove = useDeleteResource<College>({
        url: (college) => `/admin/colleges/${college.id}`,
        onDeleted: refetch,
        successMessage: 'College deleted successfully.',
        errorMessage: 'Could not delete this college.',
    });

    const columns = useMemo<Column[]>(
        () => [
            {
                key: 'name_en',
                header: 'College',
                className: 'font-medium',
                sortable: true,
                render: (row) => (
                    <span className="flex flex-col">
                        <span>{row.name_bn}</span>
                        <span className="text-xs text-gray-500">{row.name_en ?? '—'}</span>
                    </span>
                ),
            },
            {
                key: 'eiin_code',
                header: 'EIIN',
                render: (row) => row.eiin_code ?? '—',
            },
            {
                key: 'district_en',
                header: 'District',
                render: (row) => row.district_en ?? row.district_bn ?? '—',
            },
            {
                key: 'students_count',
                header: 'Students',
                render: (row) => row.students_count ?? 0,
            },
            {
                key: 'teachers_count',
                header: 'Teachers',
                render: (row) => row.teachers_count ?? 0,
            },
            {
                key: 'is_active',
                header: 'Status',
                render: (row) => (
                    <StatusBadge
                        status={row.is_active ? 'Active' : 'Inactive'}
                        tone={row.is_active ? 'green' : 'gray'}
                    />
                ),
            },
            {
                key: 'actions',
                header: 'Actions',
                className: 'text-right',
                render: (row) => (
                    <div className="flex items-center justify-end gap-4">
                        {canEdit && (
                            <Link
                                href={`/admin/colleges/${row.id}/edit`}
                                className="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800"
                            >
                                <PencilSquareIcon className="h-4 w-4" />
                                Edit
                            </Link>
                        )}

                        {canDelete && <DeleteButton onDelete={() => remove.request(row)} />}
                    </div>
                ),
            },
        ],
        [canEdit, canDelete, remove.request],
    );

    return (
        <>
            <PageHeader
                title="Colleges"
                action={
                    canCreate ? (
                        <Link href="/admin/colleges/create">
                            <Button size="sm">
                                <PlusIcon className="h-4 w-4" />
                                Create
                            </Button>
                        </Link>
                    ) : undefined
                }
            />

            <div className="flex flex-col">
                <DataTable
                    columns={columns}
                    {...tableProps}
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
                                    <option value="">All</option>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </TableSelect>
                            </div>
                        </TableFilters>
                    }
                />
            </div>

            <ConfirmationModal
                show={remove.pending !== null}
                onClose={remove.cancel}
                onConfirm={remove.confirm}
                processing={remove.deleting}
                title="Delete college"
                confirmText="Delete"
            >
                Are you sure you want to delete{' '}
                <span className="font-medium">{remove.pending?.name_bn}</span>? Students,
                teachers and content under this college must be removed first.
            </ConfirmationModal>
        </>
    );
}

CollegesIndex.layout = (page: ReactNode) => <DashboardLayout wide>{page}</DashboardLayout>;
