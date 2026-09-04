import { ReactNode, useCallback, useMemo, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import {
    ArrowRightEndOnRectangleIcon,
    PencilSquareIcon,
    PlusIcon,
} from '@heroicons/react/24/outline';
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
import { formatDate } from '@/lib/format';
import { User } from '../types';

interface Column {
    key: string;
    header: string;
    className?: string;
    sortable?: boolean;
    render?: (row: User) => ReactNode;
}

export default function UsersIndex() {
    const { can } = usePermissions();
    const canImpersonate = can('impersonate users');
    const canDelete = can('delete users');
    const currentUserId = usePage().props.auth?.user?.id ?? null;

    const [impersonating, setImpersonating] = useState<User | null>(null);
    const [submitting, setSubmitting] = useState(false);

    const { tableProps, refetch } = useResourceIndex<User>({
        url: '/admin/users',
        storageKey: 'users',
        errorMessage: 'Could not load users.',
    });

    const remove = useDeleteResource<User>({
        url: (user) => `/admin/users/${user.id}`,
        onDeleted: refetch,
        successMessage: 'User deleted successfully.',
        errorMessage: 'Could not delete this user.',
    });

    const confirmImpersonate = useCallback(() => {
        if (!impersonating) return;

        setSubmitting(true);

        router.post(
            `/admin/users/${impersonating.id}/impersonate`,
            {},
            {
                onFinish: () => {
                    setSubmitting(false);
                    setImpersonating(null);
                },
            },
        );
    }, [impersonating]);

    const columns = useMemo<Column[]>(
        () => [
            {
                key: 'name',
                header: 'Name',
                className: 'font-medium',
                sortable: true,
                render: (row) => (
                    <span className="flex items-center gap-2">
                        {row.thumbnail_url ? (
                            <img
                                src={row.thumbnail_url}
                                alt=""
                                className="h-7 w-7 shrink-0 rounded-full object-cover"
                            />
                        ) : (
                            <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-medium text-gray-500">
                                {row.name?.charAt(0)?.toUpperCase() ?? '?'}
                            </span>
                        )}
                        {row.name}
                    </span>
                ),
            },
            { key: 'email', header: 'Email', sortable: true },
            { key: 'phone', header: 'Phone', render: (row) => row.phone || '—' },
            {
                key: 'roles',
                header: 'Roles',
                render: (row) =>
                    row.roles?.length ? (
                        <span className="flex flex-wrap gap-1">
                            {row.roles.map((role) => (
                                <span
                                    key={role}
                                    className="rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700"
                                >
                                    {role}
                                </span>
                            ))}
                        </span>
                    ) : (
                        '—'
                    ),
            },
            {
                key: 'created_at',
                header: 'Joined',
                sortable: true,
                render: (row) => formatDate(row.created_at),
            },
            {
                key: 'actions',
                header: 'Actions',
                className: 'text-right',
                render: (row) => (
                    <div className="flex items-center justify-end gap-4">
                        <Link
                            href={`/admin/users/${row.id}/edit`}
                            className="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800"
                        >
                            <PencilSquareIcon className="h-4 w-4" />
                            Edit
                        </Link>

                        {canImpersonate &&
                            (row.can_impersonate ? (
                                <button
                                    type="button"
                                    onClick={() => setImpersonating(row)}
                                    className="inline-flex items-center gap-1 text-amber-600 hover:text-amber-800"
                                >
                                    <ArrowRightEndOnRectangleIcon className="h-4 w-4" />
                                    Login as
                                </button>
                            ) : (
                                <span className="text-gray-400">—</span>
                            ))}

                        {canDelete && row.id !== currentUserId && (
                            <DeleteButton onDelete={() => remove.request(row)} />
                        )}
                    </div>
                ),
            },
        ],
        [canImpersonate, canDelete, currentUserId, remove.request],
    );

    return (
        <>
            <PageHeader
                title="Users"
            action={
                <Link href="/admin/users/create">
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
                show={impersonating !== null}
                onClose={() => setImpersonating(null)}
                onConfirm={confirmImpersonate}
                processing={submitting}
                variant="danger"
                title={`Sign in as ${impersonating?.name ?? ''}?`}
                confirmText="Sign in as this user"
            >
                <p>
                    You will be signed in as {impersonating?.name}, and everything
                    you do next is recorded under their name, not yours.
                </p>

                <p className="mt-2">
                    All of it goes to the audit log, from the moment the session
                    starts to the moment it ends.
                </p>

                <p className="mt-2">
                    The way back is the banner at the top of the page — press
                    Return to my account.
                </p>
            </ConfirmationModal>

            <ConfirmationModal
                show={remove.pending !== null}
                onClose={remove.cancel}
                onConfirm={remove.confirm}
                processing={remove.deleting}
                title="Delete user"
                confirmText="Delete"
            >
                Are you sure you want to delete{' '}
                <span className="font-medium">{remove.pending?.name}</span>?
                This action cannot be undone.
            </ConfirmationModal>
        </>
    );
}

UsersIndex.layout = (page: ReactNode) => <DashboardLayout wide>{page}</DashboardLayout>;
