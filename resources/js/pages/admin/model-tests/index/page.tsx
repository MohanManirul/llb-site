import { ReactNode, useEffect, useMemo, useState } from 'react';
import { Link } from '@inertiajs/react';
import {
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
} from '@/components/ui';
import api from '@/lib/api-client';
import type { ApiEnvelope } from '@/lib/api-types';
import { errorMessage, flash } from '@/lib/flash';
import { formatDate } from '@/lib/format';
import useDeleteResource from '@/hooks/useDeleteResource';
import usePermissions from '@/hooks/usePermissions';
import useResourceIndex from '@/hooks/useResourceIndex';
import { ModelTest, ModelTestFilterOptions, ProgramOption } from '../types';

interface Column {
    key: string;
    header: string;
    className?: string;
    sortable?: boolean;
    render?: (row: ModelTest) => ReactNode;
}

export default function ModelTestsIndex() {
    const { can } = usePermissions();
    const canCreate = can('create model tests');
    const canEdit = can('edit model tests');
    const canPublish = can('publish model tests');
    const canDelete = can('delete model tests');

    const [options, setOptions] = useState<ModelTestFilterOptions | null>(null);
    const [programs, setPrograms] = useState<ProgramOption[]>([]);
    const [statusTab, setStatusTab] = useState('');
    const [programId, setProgramId] = useState('');
    const [examStage, setExamStage] = useState('');
    const [togglingId, setTogglingId] = useState<number | null>(null);

    useEffect(() => {
        let cancelled = false;

        Promise.all([
            api.get<ApiEnvelope<ModelTestFilterOptions>>('/admin/model-tests/filters'),
            api.get<ApiEnvelope<ProgramOption[]>>('/admin/programs/options'),
        ])
            .then(([filtersRes, programsRes]) => {
                if (cancelled) return;
                setOptions(filtersRes.data.result);
                setPrograms(programsRes.data.result);
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
            program_id: programId || undefined,
            exam_stage: examStage || undefined,
        }),
        [statusTab, programId, examStage],
    );

    const { tableProps, paginator, refetch } = useResourceIndex<ModelTest>({
        url: '/admin/model-tests',
        storageKey: 'model-tests',
        errorMessage: 'Could not load model tests.',
        filters,
    });

    const remove = useDeleteResource<ModelTest>({
        url: (modelTest) => `/admin/model-tests/${modelTest.id}`,
        onDeleted: refetch,
        successMessage: 'Model test deleted successfully.',
        errorMessage: 'Could not delete this model test.',
    });

    const statusCount = paginator?.status_count ?? {};

    const tabs = useMemo(
        () => [
            {
                value: '',
                label: `All (${(statusCount.draft ?? 0) + (statusCount.published ?? 0) + (statusCount.archived ?? 0)})`,
            },
            { value: 'draft', label: `Draft (${statusCount.draft ?? 0})` },
            { value: 'published', label: `Published (${statusCount.published ?? 0})` },
            { value: 'archived', label: `Archived (${statusCount.archived ?? 0})` },
        ],
        [statusCount.draft, statusCount.published, statusCount.archived],
    );

    const togglePublish = async (modelTest: ModelTest) => {
        setTogglingId(modelTest.id);

        const publishing = modelTest.status !== 'published';

        try {
            await api.patch(`/admin/model-tests/${modelTest.id}/${publishing ? 'publish' : 'unpublish'}`);
            flash.success(publishing ? 'Model test published.' : 'Model test unpublished.');
            refetch();
        } catch (error) {
            flash.error(errorMessage(error, 'Could not change the publish state.'));
        } finally {
            setTogglingId(null);
        }
    };

    const stageLabel = (value: string | null) =>
        options?.exam_stages.find((stage) => stage.value === value)?.label_en ?? value ?? '';

    const columns = useMemo<Column[]>(
        () => [
            {
                key: 'title_bn',
                header: 'Title',
                className: 'font-medium',
                render: (row) => (
                    <span className="flex flex-col">
                        <span>{row.title_bn}</span>
                        <span className="text-xs text-gray-500">
                            {row.program?.name_en ?? '—'}
                            {row.exam_stage ? ` · ${stageLabel(row.exam_stage)}` : ''}
                        </span>
                    </span>
                ),
            },
            {
                key: 'questions_count',
                header: 'Questions',
                render: (row) => row.questions_count ?? 0,
            },
            {
                key: 'duration_minutes',
                header: 'Duration',
                render: (row) => `${row.duration_minutes} min`,
            },
            {
                key: 'negative_mark',
                header: 'Negative',
                render: (row) => (Number(row.negative_mark) > 0 ? `-${Number(row.negative_mark)}` : '—'),
            },
            {
                key: 'attempts_count',
                header: 'Attempts',
                render: (row) => row.attempts_count ?? 0,
            },
            {
                key: 'status',
                header: 'Status',
                render: (row) => <StatusBadge status={row.status} />,
            },
            {
                key: 'published_at',
                header: 'Published',
                sortable: true,
                render: (row) => formatDate(row.published_at),
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
                                href={`/admin/model-tests/${row.id}/edit`}
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
        [canPublish, canEdit, canDelete, togglingId, remove.request, options],
    );

    return (
        <>
            <PageHeader
                title="Model Tests"
                action={
                    canCreate ? (
                        <Link href="/admin/model-tests/create">
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
                    onTabChange={(value) => setStatusTab(value)}
                    filters={
                        <TableFilters
                            activeCount={[programId, examStage].filter(Boolean).length}
                            onClear={() => {
                                setProgramId('');
                                setExamStage('');
                            }}
                        >
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-700">Program</label>
                                <TableSelect value={programId} onChange={(e) => setProgramId(e.target.value)}>
                                    <option value="">All programs</option>
                                    {programs.map((program) => (
                                        <option key={program.value} value={program.value}>
                                            {program.label}
                                        </option>
                                    ))}
                                </TableSelect>
                            </div>

                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-700">Exam stage</label>
                                <TableSelect value={examStage} onChange={(e) => setExamStage(e.target.value)}>
                                    <option value="">All stages</option>
                                    {options?.exam_stages.map((option) => (
                                        <option key={option.value} value={option.value}>
                                            {option.label_en}
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
                title="Delete model test"
                confirmText="Delete"
            >
                Are you sure you want to delete{' '}
                <span className="font-medium">{remove.pending?.title_bn}</span>? Student attempts on it will be
                removed too.
            </ConfirmationModal>
        </>
    );
}

ModelTestsIndex.layout = (page: ReactNode) => <DashboardLayout wide>{page}</DashboardLayout>;
