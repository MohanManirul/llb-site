import { ReactNode, useMemo, useState } from 'react';
import { CheckCircleIcon, PencilSquareIcon, PlusIcon } from '@heroicons/react/24/outline';
import DashboardLayout from '@/components/common/DashboardLayout';
import PageHeader from '@/components/common/PageHeader';
import {
    Button,
    ConfirmationModal,
    DataTable,
    DeleteButton,
    StatusBadge,
} from '@/components/ui';
import api from '@/lib/api-client';
import { errorMessage, flash } from '@/lib/flash';
import useDeleteResource from '@/hooks/useDeleteResource';
import usePermissions from '@/hooks/usePermissions';
import useResourceIndex from '@/hooks/useResourceIndex';
import { AcademicSession } from '../types';
import SessionFormModal from '../components/SessionFormModal';

interface Column {
    key: string;
    header: string;
    className?: string;
    sortable?: boolean;
    render?: (row: AcademicSession) => ReactNode;
}

export default function AcademicSessionsIndex() {
    const { can } = usePermissions();
    const canCreate = can('create academic structure');
    const canEdit = can('edit academic structure');
    const canDelete = can('delete academic structure');

    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<AcademicSession | null>(null);
    const [markingCurrent, setMarkingCurrent] = useState<number | null>(null);

    const { tableProps, refetch } = useResourceIndex<AcademicSession>({
        url: '/admin/academic-sessions',
        storageKey: 'academic-sessions',
        errorMessage: 'Could not load sessions.',
    });

    const remove = useDeleteResource<AcademicSession>({
        url: (session) => `/admin/academic-sessions/${session.id}`,
        onDeleted: refetch,
        successMessage: 'Session deleted successfully.',
        errorMessage: 'Could not delete this session.',
    });

    const markCurrent = async (session: AcademicSession) => {
        setMarkingCurrent(session.id);

        try {
            await api.patch(`/admin/academic-sessions/${session.id}/current`);
            flash.success(`${session.label} is now the current session.`);
            refetch();
        } catch (error) {
            flash.error(errorMessage(error, 'Could not update the current session.'));
        } finally {
            setMarkingCurrent(null);
        }
    };

    const columns = useMemo<Column[]>(
        () => [
            {
                key: 'label',
                header: 'Session',
                className: 'font-medium',
                sortable: true,
                render: (row) => (
                    <span className="flex items-center gap-2">
                        {row.label}
                        {row.is_current && <StatusBadge status="Current" tone="green" />}
                    </span>
                ),
            },
            {
                key: 'start_year',
                header: 'Years',
                sortable: true,
                render: (row) => `${row.start_year} – ${row.end_year}`,
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
                        {canEdit && !row.is_current && (
                            <button
                                type="button"
                                onClick={() => markCurrent(row)}
                                disabled={markingCurrent === row.id}
                                className="inline-flex items-center gap-1 text-emerald-600 hover:text-emerald-800 disabled:opacity-50"
                            >
                                <CheckCircleIcon className="h-4 w-4" />
                                Make current
                            </button>
                        )}

                        {canEdit && (
                            <button
                                type="button"
                                onClick={() => {
                                    setEditing(row);
                                    setFormOpen(true);
                                }}
                                className="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800"
                            >
                                <PencilSquareIcon className="h-4 w-4" />
                                Edit
                            </button>
                        )}

                        {canDelete && <DeleteButton onDelete={() => remove.request(row)} />}
                    </div>
                ),
            },
        ],
        [canEdit, canDelete, markingCurrent, remove.request],
    );

    return (
        <>
            <PageHeader
                title="Academic Sessions"
                action={
                    canCreate ? (
                        <Button
                            size="sm"
                            onClick={() => {
                                setEditing(null);
                                setFormOpen(true);
                            }}
                        >
                            <PlusIcon className="h-4 w-4" />
                            Create
                        </Button>
                    ) : undefined
                }
            />

            <div className="flex flex-col">
                <DataTable columns={columns} {...tableProps} />
            </div>

            <SessionFormModal
                show={formOpen}
                session={editing}
                onClose={() => setFormOpen(false)}
                onSaved={() => {
                    setFormOpen(false);
                    refetch();
                }}
            />

            <ConfirmationModal
                show={remove.pending !== null}
                onClose={remove.cancel}
                onConfirm={remove.confirm}
                processing={remove.deleting}
                title="Delete session"
                confirmText="Delete"
            >
                Are you sure you want to delete{' '}
                <span className="font-medium">{remove.pending?.label}</span>? Content linked
                to this session must be removed first.
            </ConfirmationModal>
        </>
    );
}

AcademicSessionsIndex.layout = (page: ReactNode) => <DashboardLayout wide>{page}</DashboardLayout>;
