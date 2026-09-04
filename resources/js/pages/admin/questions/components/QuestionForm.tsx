import { FormEvent, useEffect, useMemo, useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { PlusIcon, TrashIcon } from '@heroicons/react/24/outline';
import {
    Button,
    SearchableSelect,
    SelectInput,
    Textarea,
    TextInput,
} from '@/components/ui';
import api from '@/lib/api-client';
import type { ApiEnvelope } from '@/lib/api-types';
import { errorMessage, flash, validationErrors } from '@/lib/flash';
import {
    OptionRow,
    ProgramOption,
    Question,
    QuestionFilterOptions,
    QuestionFormData,
} from '../types';

interface QuestionFormProps {
    question?: Question;
}

let optionKey = 0;
const nextKey = () => `opt-${++optionKey}`;

const MIN_OPTIONS = 2;
const MAX_OPTIONS = 5;

function blankOption(): OptionRow {
    return { key: nextKey(), option_bn: '', option_en: '', is_correct: false };
}

function initialOptions(question?: Question): OptionRow[] {
    if (question?.options?.length) {
        return question.options.map((option) => ({
            key: nextKey(),
            id: option.id,
            option_bn: option.option_bn,
            option_en: option.option_en ?? '',
            is_correct: option.is_correct,
        }));
    }

    return Array.from({ length: 4 }, blankOption);
}

export default function QuestionForm({ question }: QuestionFormProps) {
    const isEdit = Boolean(question);

    const [programs, setPrograms] = useState<ProgramOption[]>([]);
    const [enums, setEnums] = useState<QuestionFilterOptions | null>(null);

    const [data, setDataState] = useState<QuestionFormData>({
        type: question?.type ?? 'mcq',
        program_id: question?.subject?.program ? String(question.subject.program.id) : '',
        program_level_id: '',
        subject_id: question?.subject_id ? String(question.subject_id) : '',
        exam_stage: question?.exam_stage ?? '',
        exam_year: question?.exam_year ? String(question.exam_year) : '',
        question_bn: question?.question_bn ?? '',
        question_en: question?.question_en ?? '',
        explanation_bn: question?.explanation_bn ?? '',
        explanation_en: question?.explanation_en ?? '',
        reference: question?.reference ?? '',
    });

    const [options, setOptions] = useState<OptionRow[]>(() => initialOptions(question));
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        let cancelled = false;

        Promise.all([
            api.get<ApiEnvelope<ProgramOption[]>>('/admin/programs/options'),
            api.get<ApiEnvelope<QuestionFilterOptions>>('/admin/questions/filters'),
        ])
            .then(([programsRes, enumsRes]) => {
                if (cancelled) return;
                setPrograms(programsRes.data.result);
                setEnums(enumsRes.data.result);
            })
            .catch((error) => {
                if (!cancelled) flash.error(errorMessage(error, 'Could not load form options.'));
            });

        return () => {
            cancelled = true;
        };
    }, []);

    const setData = <K extends keyof QuestionFormData>(field: K, value: QuestionFormData[K]) => {
        setDataState((current) => ({ ...current, [field]: value }));
    };

    const selectedProgram = useMemo(
        () => programs.find((program) => String(program.value) === data.program_id) ?? null,
        [programs, data.program_id],
    );

    const subjectFetchUrl = useMemo(() => {
        const params = new URLSearchParams();

        if (data.program_id) params.set('program_id', data.program_id);
        if (data.program_level_id) params.set('program_level_id', data.program_level_id);

        const query = params.toString();

        return `/v1/admin/subjects/options${query ? `?${query}` : ''}`;
    }, [data.program_id, data.program_level_id]);

    const updateOption = (key: string, patch: Partial<OptionRow>) => {
        setOptions((current) =>
            current.map((option) => (option.key === key ? { ...option, ...patch } : option)),
        );
    };

    const markCorrect = (key: string) => {
        setOptions((current) => current.map((option) => ({ ...option, is_correct: option.key === key })));
    };

    const removeOption = (key: string) => {
        setOptions((current) => current.filter((option) => option.key !== key));
    };

    const addOption = () => {
        setOptions((current) => (current.length >= MAX_OPTIONS ? current : [...current, blankOption()]));
    };

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        setSaving(true);
        setErrors({});

        const payload: Record<string, unknown> = {
            type: data.type,
            subject_id: data.subject_id || null,
            exam_stage: data.exam_stage || null,
            exam_year: data.exam_year || null,
            question_bn: data.question_bn,
            question_en: data.question_en || null,
            explanation_bn: data.explanation_bn || null,
            explanation_en: data.explanation_en || null,
            reference: data.reference || null,
        };

        if (data.type === 'mcq') {
            payload.options = options.map((option) => ({
                id: option.id ?? null,
                option_bn: option.option_bn,
                option_en: option.option_en || null,
                is_correct: option.is_correct,
            }));
        }

        try {
            if (isEdit && question) {
                await api.put(`/admin/questions/${question.id}`, payload);
                flash.success('Question updated successfully.');
            } else {
                await api.post('/admin/questions', payload);
                flash.success('Question created as a draft. Publish it when it is ready.');
            }

            router.visit('/admin/questions');
        } catch (error) {
            const status = (error as { response?: { status?: number } })?.response?.status;

            if (status === 422) {
                setErrors(validationErrors(error));
            } else {
                flash.error(errorMessage(error, 'Could not save the question.'));
            }
        } finally {
            setSaving(false);
        }
    };

    const optionError = (index: number, field: 'option_bn' | 'option_en') =>
        errors[`options.${index}.${field}`];

    return (
        <form onSubmit={submit} className="max-w-3xl">
            <div className="rounded-card border border-hairline bg-white shadow-sm">
                <div className="border-b border-hairline px-5 py-4">
                    <h2 className="font-semibold text-ink">Question</h2>
                </div>

                <div className="grid gap-4 p-5">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <SelectInput
                            label="Type"
                            value={data.type}
                            onChange={(e) => setData('type', e.target.value as QuestionFormData['type'])}
                            error={errors.type}
                            required
                        >
                            {(enums?.types ?? [
                                { value: 'mcq', label_en: 'MCQ', label_bn: '' },
                                { value: 'written', label_en: 'Written', label_bn: '' },
                            ]).map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label_en}
                                </option>
                            ))}
                        </SelectInput>

                        <TextInput
                            label="Reference"
                            value={data.reference}
                            onChange={(e) => setData('reference', e.target.value)}
                            error={errors.reference}
                            hint="Act, section or book. Optional."
                        />
                    </div>

                    <Textarea
                        label="Question (Bangla)"
                        rows={4}
                        value={data.question_bn}
                        onChange={(e) => setData('question_bn', e.target.value)}
                        error={errors.question_bn}
                        required
                    />

                    <Textarea
                        label="Question (English)"
                        rows={3}
                        value={data.question_en}
                        onChange={(e) => setData('question_en', e.target.value)}
                        error={errors.question_en}
                        hint="Optional — the Bangla text is shown when empty."
                    />
                </div>
            </div>

            {data.type === 'mcq' && (
                <div className="mt-5 rounded-card border border-hairline bg-white shadow-sm">
                    <div className="flex items-center justify-between border-b border-hairline px-5 py-4">
                        <div>
                            <h2 className="font-semibold text-ink">Options</h2>
                            <p className="mt-0.5 text-sm text-ink-muted">
                                Between {MIN_OPTIONS} and {MAX_OPTIONS} options; mark exactly one as correct.
                            </p>
                        </div>
                        <Button
                            type="button"
                            size="sm"
                            variant="secondary"
                            onClick={addOption}
                            disabled={options.length >= MAX_OPTIONS}
                        >
                            <PlusIcon className="h-4 w-4" />
                            Add option
                        </Button>
                    </div>

                    {errors.options && (
                        <p className="px-5 pt-4 text-sm text-red-600">{errors.options}</p>
                    )}

                    <div className="grid gap-3 p-5">
                        {options.map((option, index) => (
                            <div
                                key={option.key}
                                className={
                                    'grid gap-3 rounded-control border p-3 sm:grid-cols-[auto_1fr_1fr_auto] sm:items-start ' +
                                    (option.is_correct ? 'border-emerald-300 bg-emerald-50/40' : 'border-hairline')
                                }
                            >
                                <label className="flex items-center gap-2 pt-2 text-sm font-medium text-ink sm:pt-8">
                                    <input
                                        type="radio"
                                        name="correct_option"
                                        checked={option.is_correct}
                                        onChange={() => markCorrect(option.key)}
                                        className="h-4 w-4 accent-emerald-600"
                                    />
                                    {String.fromCharCode(65 + index)}
                                </label>

                                <TextInput
                                    label="Option (Bangla)"
                                    value={option.option_bn}
                                    onChange={(e) => updateOption(option.key, { option_bn: e.target.value })}
                                    error={optionError(index, 'option_bn')}
                                    required
                                />

                                <TextInput
                                    label="Option (English)"
                                    value={option.option_en}
                                    onChange={(e) => updateOption(option.key, { option_en: e.target.value })}
                                    error={optionError(index, 'option_en')}
                                />

                                <button
                                    type="button"
                                    onClick={() => removeOption(option.key)}
                                    disabled={options.length <= MIN_OPTIONS}
                                    aria-label="Remove option"
                                    className="mt-1 self-start rounded-control p-2 text-gray-400 hover:bg-red-50 hover:text-red-600 disabled:opacity-40 disabled:hover:bg-transparent disabled:hover:text-gray-400 sm:mt-7"
                                >
                                    <TrashIcon className="h-4 w-4" />
                                </button>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            <div className="mt-5 rounded-card border border-hairline bg-white shadow-sm">
                <div className="border-b border-hairline px-5 py-4">
                    <h2 className="font-semibold text-ink">Subject & exam</h2>
                </div>

                <div className="grid gap-4 p-5 sm:grid-cols-2">
                    <SelectInput
                        label="Program"
                        value={data.program_id}
                        onChange={(e) => {
                            setData('program_id', e.target.value);
                            setData('program_level_id', '');
                            setData('subject_id', '');
                        }}
                    >
                        <option value="">All programs</option>
                        {programs.map((program) => (
                            <option key={program.value} value={program.value}>
                                {program.label}
                            </option>
                        ))}
                    </SelectInput>

                    {selectedProgram?.has_levels && (
                        <SelectInput
                            label={selectedProgram.level_label.en ?? 'Level'}
                            value={data.program_level_id}
                            onChange={(e) => {
                                setData('program_level_id', e.target.value);
                                setData('subject_id', '');
                            }}
                        >
                            <option value="">All</option>
                            {selectedProgram.levels.map((level) => (
                                <option key={level.value} value={level.value}>
                                    {level.label}
                                </option>
                            ))}
                        </SelectInput>
                    )}

                    <div className="sm:col-span-2">
                        <SearchableSelect
                            key={`${data.program_id}:${data.program_level_id}`}
                            label="Subject"
                            value={data.subject_id}
                            onChange={(value) => setData('subject_id', value == null ? '' : String(value))}
                            fetchUrl={subjectFetchUrl}
                            selectedOption={
                                question?.subject
                                    ? { value: question.subject_id, label: question.subject.name_en }
                                    : undefined
                            }
                            error={errors.subject_id}
                            required
                        />
                    </div>

                    <SelectInput
                        label="Exam stage"
                        value={data.exam_stage}
                        onChange={(e) => setData('exam_stage', e.target.value)}
                        error={errors.exam_stage}
                    >
                        <option value="">Not specific</option>
                        {enums?.exam_stages.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label_en}
                            </option>
                        ))}
                    </SelectInput>

                    <TextInput
                        label="Exam year"
                        type="number"
                        inputMode="numeric"
                        min={1972}
                        max={2100}
                        value={data.exam_year}
                        onChange={(e) => setData('exam_year', e.target.value)}
                        error={errors.exam_year}
                        hint="Needed for the public MCQ archive. Optional."
                    />
                </div>
            </div>

            <div className="mt-5 rounded-card border border-hairline bg-white shadow-sm">
                <div className="border-b border-hairline px-5 py-4">
                    <h2 className="font-semibold text-ink">Explanation</h2>
                    <p className="mt-0.5 text-sm text-ink-muted">
                        Shown to students after answering, and in the public archive.
                    </p>
                </div>

                <div className="grid gap-4 p-5">
                    <Textarea
                        label="Explanation (Bangla)"
                        rows={4}
                        value={data.explanation_bn}
                        onChange={(e) => setData('explanation_bn', e.target.value)}
                        error={errors.explanation_bn}
                    />

                    <Textarea
                        label="Explanation (English)"
                        rows={3}
                        value={data.explanation_en}
                        onChange={(e) => setData('explanation_en', e.target.value)}
                        error={errors.explanation_en}
                    />
                </div>

                <div className="flex items-center justify-end gap-3 border-t border-hairline px-5 py-4">
                    <Link href="/admin/questions">
                        <Button type="button" variant="secondary">
                            Cancel
                        </Button>
                    </Link>
                    <Button type="submit" loading={saving}>
                        {isEdit ? 'Save changes' : 'Create draft'}
                    </Button>
                </div>
            </div>
        </form>
    );
}
