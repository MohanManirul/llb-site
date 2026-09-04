import { ReactNode, useMemo, useState } from 'react';
import { PencilSquareIcon, PlusIcon, QueueListIcon } from '@heroicons/react/24/outline';
import DashboardLayout from '@/components/common/DashboardLayout';
import PageHeader from '@/components/common/PageHeader';
import {
    Button,
    ConfirmationModal,
    DataTable,
    DeleteButton,
    StatusBadge,
} from '@/components/ui';
import useDeleteResource from '@/hooks/useDeleteResource';
import usePermissions from '@/hooks/usePermissions';
import useResourceIndex from '@/hooks/useResourceIndex';
import { Program } from '../types';
import ProgramFormModal from '../components/ProgramFormModal';
import LevelsModal from '../components/LevelsModal';

interface Column {
    key: string;
    header: string;
    className?: string;
    sortable?: boolean;
    render?: (row: Program) => ReactNode;
}

export default function ProgramsIndex() {
    const { can } = usePermissions();
    const canCreate = can('create academic structure');
    const canEdit = can('edit academic structure');
    const canDelete = can('delete academic structure');

    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<Program | null>(null);
    const [managingLevels, setManagingLevels] = useState<Program | null>(null);

    const { tableProps, refetch } = useResourceIndex<Program>({
        url: '/admin/programs',
        storageKey: 'programs',
        errorMessage: 'Could not load programs.',
    });

    const remove = useDeleteResource<Program>({
        url: (program) => `/admin/programs/${program.id}`,
        onDeleted: refetch,
        successMessage: 'Program deleted successfully.',
        errorMessage: 'Could not delete this program.',
    });

    const columns = useMemo<Column[]>(
        () => [
            {
                key: 'name_en',
                header: 'Program',
                className: 'font-medium',
                sortable: true,
                render: (row) => (
                    <span className="flex flex-col">
                        <span>{row.name_bn}</span>
                        <span className="text-xs text-gray-500">{row.name_en}</span>
                    </span>
                ),
            },
            {
                key: 'structure',
                header: 'Structure',
                render: (row) => (
                    <span className="flex flex-wrap gap-1">
                        {row.has_levels ? (
                            <StatusBadge
                                status={`${row.levels_count ?? 0} ${row.level_label_en ?? 'level'}s`}
                                tone="blue"
                            />
                        ) : (
                            <StatusBadge status="No levels" tone="gray" />
                        )}
                        {row.has_exam_stages && <StatusBadge status="Exam stages" tone="yellow" />}
                        {row.has_sessions && <StatusBadge status="Sessions" tone="green" />}
                    </span>
                ),
            },
            {
                key: 'subjects_count',
                header: 'Subjects',
                render: (row) => row.subjects_count ?? 0,
            },
            {
                key: 'sort_order',
                header: 'Order',
                sortable: true,
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
                        {row.has_levels && canEdit && (
                            <button
                                type="button"
                                onClick={() => setManagingLevels(row)}
                                className="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800"
                            >
                                <QueueListIcon className="h-4 w-4" />
                                Levels
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
        [canEdit, canDelete, remove.request],
    );

    return (
        <>
            <PageHeader
                title="Programs"
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

            <ProgramFormModal
                show={formOpen}
                program={editing}
                onClose={() => setFormOpen(false)}
                onSaved={() => {
                    setFormOpen(false);
                    refetch();
                }}
            />

            <LevelsModal
                show={managingLevels !== null}
                program={managingLevels}
                onClose={() => setManagingLevels(null)}
                onChanged={refetch}
            />

            <ConfirmationModal
                show={remove.pending !== null}
                onClose={remove.cancel}
                onConfirm={remove.confirm}
                processing={remove.deleting}
                title="Delete program"
                confirmText="Delete"
            >
                Are you sure you want to delete{' '}
                <span className="font-medium">{remove.pending?.name_en}</span>? Its levels,
                subjects and content must be removed first.
            </ConfirmationModal>
        </>
    );
}

ProgramsIndex.layout = (page: ReactNode) => <DashboardLayout wide>{page}</DashboardLayout>;
