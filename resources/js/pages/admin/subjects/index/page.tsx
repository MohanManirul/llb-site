import { ReactNode, useEffect, useMemo, useState } from 'react';
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
import api from '@/lib/api-client';
import type { ApiEnvelope } from '@/lib/api-types';
import { errorMessage, flash } from '@/lib/flash';
import useDeleteResource from '@/hooks/useDeleteResource';
import usePermissions from '@/hooks/usePermissions';
import useResourceIndex from '@/hooks/useResourceIndex';
import { ProgramOption, Subject } from '../types';

interface Column {
    key: string;
    header: string;
    className?: string;
    sortable?: boolean;
    render?: (row: Subject) => ReactNode;
}

export default function SubjectsIndex() {
    const { can } = usePermissions();
    const canCreate = can('create academic structure');
    const canEdit = can('edit academic structure');
    const canDelete = can('delete academic structure');

    const [programs, setPrograms] = useState<ProgramOption[]>([]);
    const [programId, setProgramId] = useState('');
    const [levelId, setLevelId] = useState('');

    useEffect(() => {
        let cancelled = false;

        api.get<ApiEnvelope<ProgramOption[]>>('/admin/programs/options')
            .then(({ data }) => {
                if (!cancelled) setPrograms(data.result);
            })
            .catch((error) => {
                if (!cancelled) flash.error(errorMessage(error, 'Could not load programs.'));
            });

        return () => {
            cancelled = true;
        };
    }, []);

    const filters = useMemo(
        () => ({
            program_id: programId || undefined,
            program_level_id: levelId || undefined,
        }),
        [programId, levelId],
    );

    const { tableProps, refetch } = useResourceIndex<Subject>({
        url: '/admin/subjects',
        storageKey: 'subjects',
        errorMessage: 'Could not load subjects.',
        filters,
    });

    const remove = useDeleteResource<Subject>({
        url: (subject) => `/admin/subjects/${subject.id}`,
        onDeleted: refetch,
        successMessage: 'Subject deleted successfully.',
        errorMessage: 'Could not delete this subject.',
    });

    const selectedProgram = useMemo(
        () => programs.find((program) => String(program.value) === programId) ?? null,
        [programs, programId],
    );

    const activeFilterCount = (programId ? 1 : 0) + (levelId ? 1 : 0);

    const columns = useMemo<Column[]>(
        () => [
            {
                key: 'name_en',
                header: 'Subject',
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
                key: 'code',
                header: 'Code',
                sortable: true,
                render: (row) => row.code ?? '—',
            },
            {
                key: 'program',
                header: 'Program',
                render: (row) => row.program?.name_en ?? '—',
            },
            {
                key: 'level',
                header: 'Year / Part',
                render: (row) => row.level?.name_en ?? '—',
            },
            {
                key: 'marks',
                header: 'Marks',
                render: (row) => row.marks ?? '—',
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
                                href={`/admin/academic/subjects/${row.id}/edit`}
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
                title="Subjects"
                action={
                    canCreate ? (
                        <Link href="/admin/academic/subjects/create">
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
                            activeCount={activeFilterCount}
                            onClear={() => {
                                setProgramId('');
                                setLevelId('');
                            }}
                        >
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-700">
                                    Program
                                </label>
                                <TableSelect
                                    value={programId}
                                    onChange={(e) => {
                                        setProgramId(e.target.value);
                                        setLevelId('');
                                    }}
                                >
                                    <option value="">All programs</option>
                                    {programs.map((program) => (
                                        <option key={program.value} value={program.value}>
                                            {program.label}
                                        </option>
                                    ))}
                                </TableSelect>
                            </div>

                            {selectedProgram?.has_levels && (
                                <div>
                                    <label className="mb-1 block text-xs font-medium text-gray-700">
                                        {selectedProgram.level_label.en ?? 'Level'}
                                    </label>
                                    <TableSelect
                                        value={levelId}
                                        onChange={(e) => setLevelId(e.target.value)}
                                    >
                                        <option value="">All</option>
                                        {selectedProgram.levels.map((level) => (
                                            <option key={level.value} value={level.value}>
                                                {level.label}
                                            </option>
                                        ))}
                                    </TableSelect>
                                </div>
                            )}
                        </TableFilters>
                    }
                />
            </div>

            <ConfirmationModal
                show={remove.pending !== null}
                onClose={remove.cancel}
                onConfirm={remove.confirm}
                processing={remove.deleting}
                title="Delete subject"
                confirmText="Delete"
            >
                Are you sure you want to delete{' '}
                <span className="font-medium">{remove.pending?.name_en}</span>? Content under
                this subject must be removed first.
            </ConfirmationModal>
        </>
    );
}

SubjectsIndex.layout = (page: ReactNode) => <DashboardLayout wide>{page}</DashboardLayout>;
