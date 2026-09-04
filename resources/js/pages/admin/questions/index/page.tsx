import { ReactNode, useEffect, useMemo, useState } from 'react';
import { Link } from '@inertiajs/react';
import {
    ArrowUpTrayIcon,
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
    SearchableSelect,
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
import { ProgramOption, Question, QuestionFilterOptions } from '../types';

interface Column {
    key: string;
    header: string;
    className?: string;
    sortable?: boolean;
    render?: (row: Question) => ReactNode;
}

export default function QuestionsIndex() {
    const { can } = usePermissions();
    const canCreate = can('create questions');
    const canEdit = can('edit questions');
    const canPublish = can('publish questions');
    const canDelete = can('delete questions');

    const [options, setOptions] = useState<QuestionFilterOptions | null>(null);
    const [programs, setPrograms] = useState<ProgramOption[]>([]);
    const [statusTab, setStatusTab] = useState('');
    const [type, setType] = useState('');
    const [programId, setProgramId] = useState('');
    const [subjectId, setSubjectId] = useState('');
    const [examStage, setExamStage] = useState('');
    const [examYear, setExamYear] = useState('');
    const [togglingId, setTogglingId] = useState<number | null>(null);

    useEffect(() => {
        let cancelled = false;

        Promise.all([
            api.get<ApiEnvelope<QuestionFilterOptions>>('/admin/questions/filters'),
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
            type: type || undefined,
            program_id: programId || undefined,
            subject_id: subjectId || undefined,
            exam_stage: examStage || undefined,
            exam_year: examYear.length === 4 ? examYear : undefined,
        }),
        [statusTab, type, programId, subjectId, examStage, examYear],
    );

    const { tableProps, paginator, refetch } = useResourceIndex<Question>({
        url: '/admin/questions',
        storageKey: 'questions',
        errorMessage: 'Could not load questions.',
        filters,
    });

    const remove = useDeleteResource<Question>({
        url: (question) => `/admin/questions/${question.id}`,
        onDeleted: refetch,
        successMessage: 'Question deleted successfully.',
        errorMessage: 'Could not delete this question.',
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

    const togglePublish = async (question: Question) => {
        setTogglingId(question.id);

        const publishing = question.status !== 'published';

        try {
            await api.patch(`/admin/questions/${question.id}/${publishing ? 'publish' : 'unpublish'}`);
            flash.success(publishing ? 'Question published.' : 'Question unpublished.');
            refetch();
        } catch (error) {
            flash.error(errorMessage(error, 'Could not change the publish state.'));
        } finally {
            setTogglingId(null);
        }
    };

    const stageLabel = (value: string | null) =>
        options?.exam_stages.find((stage) => stage.value === value)?.label_en ?? value ?? '';

    const subjectFetchUrl = useMemo(
        () => `/v1/admin/subjects/options${programId ? `?program_id=${programId}` : ''}`,
        [programId],
    );

    const activeFilterCount = [type, programId, subjectId, examStage, examYear].filter(Boolean).length;

    const clearFilters = () => {
        setType('');
        setProgramId('');
        setSubjectId('');
        setExamStage('');
        setExamYear('');
    };

    const columns = useMemo<Column[]>(
        () => [
            {
                key: 'question_bn',
                header: 'Question',
                className: 'font-medium',
                render: (row) => (
                    <span className="flex max-w-xl flex-col">
                        <span className="line-clamp-2">{row.question_bn}</span>
                        <span className="text-xs text-gray-500">
                            {row.subject?.name_en ?? '—'}
                            {row.subject?.program ? ` · ${row.subject.program.name_en}` : ''}
                        </span>
                    </span>
                ),
            },
            {
                key: 'type',
                header: 'Type',
                render: (row) => (
                    <StatusBadge status={row.type.toUpperCase()} tone={row.type === 'mcq' ? 'blue' : 'purple'} />
                ),
            },
            {
                key: 'exam_year',
                header: 'Exam',
                sortable: true,
                render: (row) => (
                    <span className="flex flex-col text-sm">
                        <span>{row.exam_year ?? '—'}</span>
                        {row.exam_stage && (
                            <span className="text-xs text-gray-500">{stageLabel(row.exam_stage)}</span>
                        )}
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
                                href={`/admin/questions/${row.id}/edit`}
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
                title="Question Bank"
                action={
                    canCreate ? (
                        <>
                            <Link href="/admin/questions/import">
                                <Button size="sm" variant="secondary">
                                    <ArrowUpTrayIcon className="h-4 w-4" />
                                    Import CSV
                                </Button>
                            </Link>
                            <Link href="/admin/questions/create">
                                <Button size="sm">
                                    <PlusIcon className="h-4 w-4" />
                                    Create
                                </Button>
                            </Link>
                        </>
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
                        <TableFilters activeCount={activeFilterCount} onClear={clearFilters}>
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-700">Type</label>
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
                                <label className="mb-1 block text-xs font-medium text-gray-700">Program</label>
                                <TableSelect
                                    value={programId}
                                    onChange={(e) => {
                                        setProgramId(e.target.value);
                                        setSubjectId('');
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

                            <div className="min-w-56">
                                <SearchableSelect
                                    key={programId}
                                    label="Subject"
                                    size="sm"
                                    value={subjectId}
                                    onChange={(value) => setSubjectId(value == null ? '' : String(value))}
                                    fetchUrl={subjectFetchUrl}
                                    allOptionLabel="All subjects"
                                    clearable
                                />
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

                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-700">Exam year</label>
                                <input
                                    type="number"
                                    inputMode="numeric"
                                    min={1972}
                                    max={2100}
                                    value={examYear}
                                    onChange={(e) => setExamYear(e.target.value)}
                                    placeholder="e.g. 2023"
                                    className="h-9 w-32 rounded-control border border-hairline bg-field px-3 text-sm text-ink focus:border-brand-accent focus:outline-none"
                                />
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
                title="Delete question"
                confirmText="Delete"
            >
                Are you sure you want to delete this question?{' '}
                <span className="block mt-1 font-medium">{remove.pending?.question_bn}</span>
            </ConfirmationModal>
        </>
    );
}

QuestionsIndex.layout = (page: ReactNode) => <DashboardLayout wide>{page}</DashboardLayout>;
