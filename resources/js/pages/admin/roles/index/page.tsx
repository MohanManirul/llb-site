import { ReactNode, useMemo } from 'react';
import { Link } from '@inertiajs/react';
import { PlusIcon, PencilSquareIcon } from '@heroicons/react/24/outline';
import DashboardLayout from '@/components/common/DashboardLayout';
import PageHeader from '@/components/common/PageHeader';
import {
    Button,
    ConfirmationModal,
    DataTable,
    DeleteButton,
} from '@/components/ui';
import useDeleteResource from '@/hooks/useDeleteResource';
import usePermissions from '@/hooks/usePermissions';
import useResourceIndex from '@/hooks/useResourceIndex';
import { Role } from '../types';

interface Column {
    key: string;
    header: string;
    className?: string;
    sortable?: boolean;
    render?: (row: Role) => ReactNode;
}

export default function RolesIndex() {
    const { can } = usePermissions();
    const canDelete = can('delete roles');

    const { tableProps, refetch } = useResourceIndex<Role>({
        url: '/admin/roles',
        storageKey: 'roles',
        errorMessage: 'Could not load roles.',
    });

    const remove = useDeleteResource<Role>({
        url: (role) => `/admin/roles/${role.id}`,
        onDeleted: refetch,
        successMessage: 'Role deleted successfully.',
        errorMessage: 'Could not delete this role.',
    });

    const columns = useMemo<Column[]>(
        () => [
            {
                key: 'name',
                header: 'Role',
                className: 'font-medium',
                sortable: true,
                render: (row) => (
                    <span className="inline-flex items-center gap-2">
                        {row.name}
                        {row.protected && (
                            <span className="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">
                                protected
                            </span>
                        )}
                    </span>
                ),
            },
            {
                key: 'permissions_count',
                header: 'Permissions',
                render: (row) =>
                    row.protected ? (
                        <span className="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">
                            All Permissions
                        </span>
                    ) : (
                        row.permissions_count
                    ),
            },
            { key: 'users_count', header: 'Users' },
            {
                key: 'actions',
                header: 'Actions',
                className: 'text-right',
                render: (row) =>
                    row.protected ? (
                        <span className="text-gray-400">—</span>
                    ) : (
                        <div className="flex items-center justify-end gap-3">
                            <Link
                                href={`/admin/roles/${row.id}/edit`}
                                className="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800"
                            >
                                <PencilSquareIcon className="h-4 w-4" />
                                Edit
                            </Link>

                            {canDelete && (
                                <DeleteButton
                                    onDelete={() => remove.request(row)}
                                />
                            )}
                        </div>
                    ),
            },
        ],
        [canDelete, remove.request],
    );

    return (
        <>
            <PageHeader
                title="Roles"
            action={
                <Link href="/admin/roles/create">
                    <Button size="sm">
                        <PlusIcon className="h-4 w-4" />
                        Create
                    </Button>
                </Link>
                }
            />

            <div className="flex flex-col">
                <DataTable columns={columns} {...tableProps} />
            </div>

            <ConfirmationModal
                show={remove.pending !== null}
                onClose={remove.cancel}
                onConfirm={remove.confirm}
                processing={remove.deleting}
                title="Delete role"
                confirmText="Delete"
            >
                Are you sure you want to delete the{' '}
                <span className="font-medium">{remove.pending?.name}</span>{' '}
                role? Users holding it lose every permission it grants.
            </ConfirmationModal>
        </>
    );
}

RolesIndex.layout = (page: ReactNode) => <DashboardLayout wide>{page}</DashboardLayout>;
