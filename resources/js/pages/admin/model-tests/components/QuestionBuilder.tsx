import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
    ArrowDownIcon,
    ArrowUpIcon,
    ArrowPathIcon,
    MagnifyingGlassIcon,
    PlusIcon,
    TrashIcon,
    XMarkIcon,
} from '@heroicons/react/24/outline';
import { Button, Checkbox, Modal, StatusBadge } from '@/components/ui';
import api from '@/lib/api-client';
import type { ApiEnvelope, SimpleResourcePaginator } from '@/lib/api-types';
import { errorMessage, flash } from '@/lib/flash';
import useDebouncedValue from '@/hooks/useDebouncedValue';
import { ModelTest, ModelTestQuestion, Question } from '../types';

interface QuestionBuilderProps {
    modelTest: ModelTest;
    onChange: (modelTest: ModelTest) => void;
    canEdit: boolean;
}

function sorted(questions: ModelTestQuestion[] | undefined): ModelTestQuestion[] {
    return [...(questions ?? [])].sort((a, b) => a.sort_order - b.sort_order);
}

export default function QuestionBuilder({ modelTest, onChange, canEdit }: QuestionBuilderProps) {
    const locked = (modelTest.attempts_count ?? 0) > 0;
    const editable = canEdit && !locked;

    const [rows, setRows] = useState<ModelTestQuestion[]>(() => sorted(modelTest.questions));
    const [marksDraft, setMarksDraft] = useState<Record<number, string>>({});
    const [busy, setBusy] = useState(false);
    const [pickerOpen, setPickerOpen] = useState(false);

    useEffect(() => {
        setRows(sorted(modelTest.questions));
        setMarksDraft({});
    }, [modelTest.questions]);

    const totalMarks = useMemo(
        () => rows.reduce((sum, row) => sum + Number(marksDraft[row.id] ?? row.marks), 0),
        [rows, marksDraft],
    );

    const dirty = useMemo(
        () =>
            rows.some((row, index) => row.sort_order !== index + 1) ||
            Object.entries(marksDraft).some(([id, value]) => {
                const row = rows.find((r) => r.id === Number(id));
                return row !== undefined && value !== '' && Number(value) !== Number(row.marks);
            }),
        [rows, marksDraft],
    );

    const move = (index: number, delta: number) => {
        setRows((current) => {
            const target = index + delta;
            if (target < 0 || target >= current.length) return current;

            const next = [...current];
            [next[index], next[target]] = [next[target], next[index]];
            return next;
        });
    };

    const saveOrder = async () => {
        setBusy(true);

        const marks: Record<number, number> = {};
        Object.entries(marksDraft).forEach(([id, value]) => {
            if (value !== '' && Number.isFinite(Number(value))) marks[Number(id)] = Number(value);
        });

        try {
            const { data } = await api.patch<ApiEnvelope<ModelTest>>(
                `/admin/model-tests/${modelTest.id}/questions/reorder`,
                { question_ids: rows.map((row) => row.id), marks },
            );
            flash.success('Question order saved.');
            onChange({ ...modelTest, ...data.result });
        } catch (error) {
            flash.error(errorMessage(error, 'Could not save the order.'));
        } finally {
            setBusy(false);
        }
    };

    const detach = async (question: ModelTestQuestion) => {
        setBusy(true);

        try {
            await api.delete(`/admin/model-tests/${modelTest.id}/questions/${question.id}`);
            flash.success('Question removed from the test.');
            onChange({
                ...modelTest,
                questions: rows.filter((row) => row.id !== question.id),
                questions_count: Math.max(0, (modelTest.questions_count ?? rows.length) - 1),
            });
        } catch (error) {
            flash.error(errorMessage(error, 'Could not remove the question.'));
        } finally {
            setBusy(false);
        }
    };

    const attached = async (ids: number[], marks: string) => {
        setBusy(true);

        try {
            const { data } = await api.post<ApiEnvelope<ModelTest>>(
                `/admin/model-tests/${modelTest.id}/questions`,
                { question_ids: ids, marks: marks === '' ? undefined : Number(marks) },
            );
            flash.success(`${ids.length} question${ids.length === 1 ? '' : 's'} attached.`);
            onChange({ ...modelTest, ...data.result });
            setPickerOpen(false);
        } catch (error) {
            flash.error(errorMessage(error, 'Could not attach the questions.'));
        } finally {
            setBusy(false);
        }
    };

    return (
        <div className="rounded-card border border-hairline bg-white shadow-sm">
            <div className="flex flex-wrap items-center justify-between gap-3 border-b border-hairline px-5 py-4">
                <div>
                    <h2 className="font-semibold text-ink">
                        Questions
                        <span className="ml-2 rounded-chip bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">
                            {rows.length} · {totalMarks} marks
                        </span>
                    </h2>
                    <p className="mt-0.5 text-sm text-ink-muted">
                        {locked
                            ? 'Students have already attempted this test, so its questions are frozen.'
                            : 'Only published MCQ questions can be added. Reorder with the arrows, then save.'}
                    </p>
                </div>

                {editable && (
                    <div className="flex items-center gap-2">
                        {dirty && (
                            <Button size="sm" variant="secondary" onClick={saveOrder} loading={busy}>
                                Save order & marks
                            </Button>
                        )}
                        <Button size="sm" onClick={() => setPickerOpen(true)} disabled={busy}>
                            <PlusIcon className="h-4 w-4" />
                            Add questions
                        </Button>
                    </div>
                )}
            </div>

            {rows.length === 0 ? (
                <p className="px-5 py-10 text-center text-sm text-ink-muted">
                    No questions yet. The test cannot be published until it has at least one.
                </p>
            ) : (
                <ol className="divide-y divide-hairline">
                    {rows.map((row, index) => {
                        const correct = row.options.find((option) => option.is_correct);

                        return (
                            <li key={row.id} className="flex items-start gap-3 px-5 py-3">
                                <span className="mt-0.5 w-7 shrink-0 text-right font-mono text-xs text-gray-400">
                                    {index + 1}.
                                </span>

                                <div className="min-w-0 flex-1">
                                    <p className="text-sm text-ink">{row.question_bn}</p>
                                    <p className="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                                        {correct && <span className="text-emerald-700">✓ {correct.option_bn}</span>}
                                        {row.exam_year && <span>· {row.exam_year}</span>}
                                        {row.status !== 'published' && (
                                            <StatusBadge status={row.status} className="ml-1" />
                                        )}
                                    </p>
                                </div>

                                <div className="flex shrink-0 items-center gap-1">
                                    {editable ? (
                                        <input
                                            type="number"
                                            min={0.25}
                                            max={100}
                                            step={0.25}
                                            value={marksDraft[row.id] ?? String(Number(row.marks))}
                                            onChange={(e) =>
                                                setMarksDraft((current) => ({ ...current, [row.id]: e.target.value }))
                                            }
                                            aria-label="Marks"
                                            className="h-8 w-16 rounded-control border border-hairline bg-field px-2 text-right text-sm text-ink focus:border-brand-accent focus:outline-none"
                                        />
                                    ) : (
                                        <span className="w-16 text-right text-sm text-ink">{Number(row.marks)}</span>
                                    )}

                                    {editable && (
                                        <>
                                            <button
                                                type="button"
                                                onClick={() => move(index, -1)}
                                                disabled={index === 0 || busy}
                                                aria-label="Move up"
                                                className="rounded-control p-1.5 text-gray-400 hover:bg-gray-100 hover:text-ink disabled:opacity-30"
                                            >
                                                <ArrowUpIcon className="h-4 w-4" />
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => move(index, 1)}
                                                disabled={index === rows.length - 1 || busy}
                                                aria-label="Move down"
                                                className="rounded-control p-1.5 text-gray-400 hover:bg-gray-100 hover:text-ink disabled:opacity-30"
                                            >
                                                <ArrowDownIcon className="h-4 w-4" />
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => detach(row)}
                                                disabled={busy}
                                                aria-label="Remove"
                                                className="rounded-control p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-600 disabled:opacity-30"
                                            >
                                                <TrashIcon className="h-4 w-4" />
                                            </button>
                                        </>
                                    )}
                                </div>
                            </li>
                        );
                    })}
                </ol>
            )}

            <QuestionPicker
                show={pickerOpen}
                modelTestId={modelTest.id}
                programId={modelTest.program_id}
                busy={busy}
                onClose={() => setPickerOpen(false)}
                onConfirm={attached}
            />
        </div>
    );
}

interface QuestionPickerProps {
    show: boolean;
    modelTestId: number;
    programId: number;
    busy: boolean;
    onClose: () => void;
    onConfirm: (ids: number[], marks: string) => void;
}

function QuestionPicker({ show, modelTestId, programId, busy, onClose, onConfirm }: QuestionPickerProps) {
    const [search, setSearch] = useState('');
    const debouncedSearch = useDebouncedValue(search, 300);
    const [onlyProgram, setOnlyProgram] = useState(true);
    const [page, setPage] = useState(1);
    const [paginator, setPaginator] = useState<SimpleResourcePaginator<Question> | null>(null);
    const [loading, setLoading] = useState(false);
    const [selected, setSelected] = useState<number[]>([]);
    const [marks, setMarks] = useState('1');
    const abortRef = useRef<AbortController | null>(null);

    const load = useCallback(async () => {
        abortRef.current?.abort();
        const controller = new AbortController();
        abortRef.current = controller;

        setLoading(true);

        try {
            const { data } = await api.get<ApiEnvelope<SimpleResourcePaginator<Question>>>('/admin/questions', {
                params: {
                    type: 'mcq',
                    status: 'published',
                    exclude_model_test: modelTestId,
                    program_id: onlyProgram ? programId : undefined,
                    search: debouncedSearch || undefined,
                    per_page: 15,
                    page,
                },
                signal: controller.signal,
            });
            setPaginator(data.result);
        } catch (error) {
            if ((error as { name?: string })?.name !== 'CanceledError') {
                flash.error(errorMessage(error, 'Could not load questions.'));
            }
        } finally {
            if (abortRef.current === controller) setLoading(false);
        }
    }, [modelTestId, programId, onlyProgram, debouncedSearch, page]);

    useEffect(() => {
        if (!show) return;
        load();
        return () => abortRef.current?.abort();
    }, [show, load]);

    useEffect(() => {
        if (show) {
            setSelected([]);
            setSearch('');
            setPage(1);
        }
    }, [show]);

    useEffect(() => {
        setPage(1);
    }, [debouncedSearch, onlyProgram]);

    const toggle = (id: number) => {
        setSelected((current) =>
            current.includes(id) ? current.filter((value) => value !== id) : [...current, id],
        );
    };

    const rows = paginator?.data ?? [];
    const hasNext = Boolean(paginator?.links?.next);
    const hasPrev = Boolean(paginator?.links?.prev);

    return (
        <Modal show={show} onClose={onClose} maxWidth="2xl" label="Add questions">
            <div className="flex items-center justify-between border-b border-hairline px-5 py-4">
                <h3 className="font-semibold text-ink">Add questions</h3>
                <button
                    type="button"
                    onClick={onClose}
                    aria-label="Close"
                    className="rounded-control p-1.5 text-gray-400 hover:bg-gray-100 hover:text-ink"
                >
                    <XMarkIcon className="h-5 w-5" />
                </button>
            </div>

            <div className="flex flex-wrap items-center gap-3 border-b border-hairline px-5 py-3">
                <label className="relative min-w-0 flex-1">
                    <MagnifyingGlassIcon className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <input
                        type="search"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Search published MCQ…"
                        className="h-9 w-full rounded-control border border-hairline bg-field pl-9 pr-3 text-sm text-ink focus:border-brand-accent focus:outline-none"
                    />
                </label>
                <Checkbox
                    label="Only this program"
                    checked={onlyProgram}
                    onChange={(e) => setOnlyProgram(e.target.checked)}
                />
            </div>

            <div className="max-h-[50vh] overflow-y-auto">
                {loading && rows.length === 0 ? (
                    <div className="flex justify-center py-10">
                        <ArrowPathIcon className="h-6 w-6 animate-spin text-gray-400" />
                    </div>
                ) : rows.length === 0 ? (
                    <p className="px-5 py-10 text-center text-sm text-ink-muted">
                        No published MCQ questions match.
                    </p>
                ) : (
                    <ul className="divide-y divide-hairline">
                        {rows.map((question) => (
                            <li key={question.id}>
                                <label className="flex cursor-pointer items-start gap-3 px-5 py-3 hover:bg-gray-50">
                                    <input
                                        type="checkbox"
                                        checked={selected.includes(question.id)}
                                        onChange={() => toggle(question.id)}
                                        className="mt-1 h-4 w-4 accent-brand-accent"
                                    />
                                    <span className="min-w-0 flex-1">
                                        <span className="block text-sm text-ink">{question.question_bn}</span>
                                        <span className="mt-0.5 block text-xs text-gray-500">
                                            {question.subject?.name_en ?? '—'}
                                            {question.subject?.program ? ` · ${question.subject.program.name_en}` : ''}
                                            {question.exam_year ? ` · ${question.exam_year}` : ''}
                                        </span>
                                    </span>
                                </label>
                            </li>
                        ))}
                    </ul>
                )}
            </div>

            <div className="flex flex-wrap items-center justify-between gap-3 border-t border-hairline px-5 py-3">
                <div className="flex items-center gap-2 text-sm">
                    <button
                        type="button"
                        onClick={() => setPage((p) => Math.max(1, p - 1))}
                        disabled={!hasPrev || loading}
                        className="rounded-control border border-hairline px-2.5 py-1 text-xs hover:bg-gray-50 disabled:opacity-40"
                    >
                        Prev
                    </button>
                    <span className="text-xs text-gray-500">Page {page}</span>
                    <button
                        type="button"
                        onClick={() => setPage((p) => p + 1)}
                        disabled={!hasNext || loading}
                        className="rounded-control border border-hairline px-2.5 py-1 text-xs hover:bg-gray-50 disabled:opacity-40"
                    >
                        Next
                    </button>
                </div>

                <div className="flex items-center gap-3">
                    <label className="flex items-center gap-2 text-sm text-ink">
                        Marks each
                        <input
                            type="number"
                            min={0.25}
                            max={100}
                            step={0.25}
                            value={marks}
                            onChange={(e) => setMarks(e.target.value)}
                            className="h-8 w-16 rounded-control border border-hairline bg-field px-2 text-right text-sm focus:border-brand-accent focus:outline-none"
                        />
                    </label>
                    <Button
                        size="sm"
                        onClick={() => onConfirm(selected, marks)}
                        disabled={selected.length === 0}
                        loading={busy}
                    >
                        Add {selected.length > 0 ? `(${selected.length})` : ''}
                    </Button>
                </div>
            </div>
        </Modal>
    );
}
