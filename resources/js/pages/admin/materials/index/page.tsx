import { ReactNode, useEffect, useMemo, useState } from 'react';
import { Link } from '@inertiajs/react';
import {
    ArrowDownTrayIcon,
    ArrowUturnLeftIcon,
    CheckCircleIcon,
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
    StatusBadge,
    TableFilters,
    TableSelect,
    type BadgeTone,
} from '@/components/ui';
import api from '@/lib/api-client';
import type { ApiEnvelope } from '@/lib/api-types';
import { errorMessage, flash } from '@/lib/flash';
import { formatDate } from '@/lib/format';
import useDeleteResource from '@/hooks/useDeleteResource';
import usePermissions from '@/hooks/usePermissions';
import useResourceIndex from '@/hooks/useResourceIndex';
import type { ProgramOption } from '../../subjects/types';
import {
    ContentStatus,
    MaterialFilterOptions,
    SessionOption,
    StudyMaterial,
} from '../types';

interface Column {
    key: string;
    header: string;
    className?: string;
    sortable?: boolean;
    render?: (row: StudyMaterial) => ReactNode;
}

const TYPE_TONES: Record<string, BadgeTone> = {
    suggestion: 'indigo',
    book: 'blue',
    note: 'green',
};

export default function MaterialsIndex() {
    const { can } = usePermissions();
    const canCreate = can('create study materials');
    const canEdit = can('edit study materials');
    const canPublish = can('publish study materials');
    const canDelete = can('delete study materials');

    const [options, setOptions] = useState<MaterialFilterOptions | null>(null);
    const [programs, setPrograms] = useState<ProgramOption[]>([]);
    const [sessions, setSessions] = useState<SessionOption[]>([]);

    const [statusTab, setStatusTab] = useState('');
    const [type, setType] = useState('');
    const [programId, setProgramId] = useState('');
    const [sessionId, setSessionId] = useState('');
    const [togglingId, setTogglingId] = useState<number | null>(null);

    useEffect(() => {
        let cancelled = false;

        Promise.all([
            api.get<ApiEnvelope<MaterialFilterOptions>>('/admin/study-materials/filters'),
            api.get<ApiEnvelope<ProgramOption[]>>('/admin/programs/options'),
            api.get<ApiEnvelope<SessionOption[]>>('/admin/academic-sessions/options'),
        ])
            .then(([optionsRes, programsRes, sessionsRes]) => {
                if (cancelled) return;

                setOptions(optionsRes.data.result);
                setPrograms(programsRes.data.result);
                setSessions(sessionsRes.data.result);
            })
            .catch((error) => {
                if (!cancelled) flash.error(errorMessage(error, 'Could not load filters.'));
            });

        return () => {
            cancelled = true;
        };
    }, []);

    const filters = useMemo(
        () => ({
            status: statusTab || undefined,
            type: type || undefined,
            program_id: programId || undefined,
            academic_session_id: sessionId || undefined,
        }),
        [statusTab, type, programId, sessionId],
    );

    const { tableProps, paginator, refetch } = useResourceIndex<StudyMaterial>({
        url: '/admin/study-materials',
        storageKey: 'study-materials',
        errorMessage: 'Could not load study materials.',
        filters,
    });

    const remove = useDeleteResource<StudyMaterial>({
        url: (material) => `/admin/study-materials/${material.id}`,
        onDeleted: refetch,
        successMessage: 'Material deleted successfully.',
        errorMessage: 'Could not delete this material.',
    });

    const statusCount = paginator?.status_count ?? {};

    const tabs = useMemo(
        () => [
            { value: '', label: `All (${(statusCount.draft ?? 0) + (statusCount.published ?? 0) + (statusCount.archived ?? 0)})` },
            { value: 'draft', label: `Draft (${statusCount.draft ?? 0})` },
            { value: 'published', label: `Published (${statusCount.published ?? 0})` },
            { value: 'archived', label: `Archived (${statusCount.archived ?? 0})` },
        ],
        [statusCount.draft, statusCount.published, statusCount.archived],
    );

    const togglePublish = async (material: StudyMaterial) => {
        setTogglingId(material.id);

        const publishing = material.status !== 'published';

        try {
            await api.patch(
                `/admin/study-materials/${material.id}/${publishing ? 'publish' : 'unpublish'}`,
            );
            flash.success(publishing ? 'Material published.' : 'Material unpublished.');
            refetch();
        } catch (error) {
            flash.error(errorMessage(error, 'Could not change the publish state.'));
        } finally {
            setTogglingId(null);
        }
    };

    const activeFilterCount =
        (type ? 1 : 0) + (programId ? 1 : 0) + (sessionId ? 1 : 0);

    const columns = useMemo<Column[]>(
        () => [
            {
                key: 'title_bn',
                header: 'Title',
                className: 'font-medium',
                sortable: true,
                render: (row) => (
                    <span className="flex flex-col">
                        <span>{row.title_bn}</span>
                        <span className="text-xs text-gray-500">
                            {row.subject?.name_bn}
                            {row.session ? ` · ${row.session.label}` : ''}
                        </span>
                    </span>
                ),
            },
            {
                key: 'type',
                header: 'Type',
                sortable: true,
                render: (row) => (
                    <StatusBadge status={row.type} tone={TYPE_TONES[row.type] ?? 'gray'} />
                ),
            },
            {
                key: 'files_count',
                header: 'Files',
                render: (row) => row.files_count ?? 0,
            },
            {
                key: 'download_count',
                header: 'Downloads',
                sortable: true,
                render: (row) => (
                    <span className="inline-flex items-center gap-1">
                        <ArrowDownTrayIcon className="h-3.5 w-3.5 text-gray-400" />
                        {row.download_count}
                    </span>
                ),
            },
            {
                key: 'status',
                header: 'Status',
                render: (row) => <StatusBadge status={row.status} />,
            },
            {
                key: 'created_at',
                header: 'Created',
                sortable: true,
                render: (row) => formatDate(row.created_at),
            },
            {
                key: 'actions',
                header: 'Actions',
                className: 'text-right',
                render: (row) => (
                    <div className="flex items-center justify-end gap-4">
                        {canPublish &&
                            (row.status === 'published' ? (
                                <button
                                    type="button"
                                    onClick={() => togglePublish(row)}
                                    disabled={togglingId === row.id}
                                    className="inline-flex items-center gap-1 text-amber-600 hover:text-amber-800 disabled:opacity-50"
                                >
                                    <ArrowUturnLeftIcon className="h-4 w-4" />
                                    Unpublish
                                </button>
                            ) : (
                                <button
                                    type="button"
                                    onClick={() => togglePublish(row)}
                                    disabled={togglingId === row.id}
                                    className="inline-flex items-center gap-1 text-emerald-600 hover:text-emerald-800 disabled:opacity-50"
                                >
                                    <CheckCircleIcon className="h-4 w-4" />
                                    Publish
                                </button>
                            ))}

                        {canEdit && (
                            <Link
                                href={`/admin/study-materials/${row.id}/edit`}
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
        [canPublish, canEdit, canDelete, togglingId, remove.request],
    );

    return (
        <>
            <PageHeader
                title="Study Materials"
                action={
                    canCreate ? (
                        <Link href="/admin/study-materials/create">
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
                    tabs={tabs}
                    activeTab={statusTab}
                    onTabChange={(value) => setStatusTab(value as ContentStatus | '')}
                    filters={
                        <TableFilters
                            activeCount={activeFilterCount}
                            onClear={() => {
                                setType('');
                                setProgramId('');
                                setSessionId('');
                            }}
                        >
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-700">
                                    Type
                                </label>
                                <TableSelect value={type} onChange={(e) => setType(e.target.value)}>
                                    <option value="">All types</option>
                                    {options?.types.map((option) => (
                                        <option key={option.value} value={option.value}>
                                            {option.label_en}
                                        </option>
                                    ))}
                                </TableSelect>
                            </div>

                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-700">
                                    Program
                                </label>
                                <TableSelect
                                    value={programId}
                                    onChange={(e) => setProgramId(e.target.value)}
                                >
                                    <option value="">All programs</option>
                                    {programs.map((program) => (
                                        <option key={program.value} value={program.value}>
                                            {program.label}
                                        </option>
                                    ))}
                                </TableSelect>
                            </div>

                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-700">
                                    Session
                                </label>
                                <TableSelect
                                    value={sessionId}
                                    onChange={(e) => setSessionId(e.target.value)}
                                >
                                    <option value="">All sessions</option>
                                    {sessions.map((session) => (
                                        <option key={session.value} value={session.value}>
                                            {session.label}
                                        </option>
                                    ))}
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
                title="Delete material"
                confirmText="Delete"
            >
                Are you sure you want to delete{' '}
                <span className="font-medium">{remove.pending?.title_bn}</span>? It disappears
                from the site immediately.
            </ConfirmationModal>
        </>
    );
}

MaterialsIndex.layout = (page: ReactNode) => <DashboardLayout wide>{page}</DashboardLayout>;
