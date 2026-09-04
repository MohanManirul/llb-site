import { useEffect, useMemo, useRef, useState, type ReactNode } from 'react';
import { CheckCircleIcon, XCircleIcon } from '@heroicons/react/24/outline';
import PublicLayout from '@/components/public/PublicLayout';
import PublicPageHeader from '@/components/public/PublicPageHeader';
import AppLink from '@/components/public/AppLink';
import { ErrorCard, LoadingBlock } from '@/components/public/helpers';
import { Button } from '@/components/ui';
import api from '@/lib/api-client';
import type { ApiEnvelope, ResourcePaginator } from '@/lib/api-types';
import { errorMessage, flash } from '@/lib/flash';
import useTranslation from '@/hooks/useTranslation';
import { EXAM_STAGE_LABELS, type PracticeQuestion } from '../../types';

interface RunParams {
    subject: string;
    count: string;
    exam_stage: string;
    exam_year: string;
}

function readParams(): RunParams {
    const query = new URLSearchParams(window.location.search);

    return {
        subject: query.get('subject') ?? '',
        count: query.get('count') ?? '10',
        exam_stage: query.get('exam_stage') ?? '',
        exam_year: query.get('exam_year') ?? '',
    };
}

export default function PracticeRun() {
    const { t, tx, n, localeHref } = useTranslation();
    const params = useMemo(readParams, []);

    const [questions, setQuestions] = useState<PracticeQuestion[] | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [index, setIndex] = useState(0);
    const [selected, setSelected] = useState<number | null>(null);
    const [checked, setChecked] = useState(false);
    const [correct, setCorrect] = useState(0);
    const [finished, setFinished] = useState(false);
    const recordedRef = useRef(false);

    const load = () => {
        setError(null);
        setQuestions(null);
        setIndex(0);
        setSelected(null);
        setChecked(false);
        setCorrect(0);
        setFinished(false);
        recordedRef.current = false;

        api.get<ApiEnvelope<ResourcePaginator<PracticeQuestion> | PracticeQuestion[]>>('/student/practice/questions', {
            params: {
                subject_id: params.subject,
                count: params.count,
                exam_stage: params.exam_stage || undefined,
                exam_year: params.exam_year || undefined,
            },
        })
            .then(({ data }) => {
                const result = data.result;
                setQuestions(Array.isArray(result) ? result : (result.data ?? []));
            })
            .catch((err) => setError(errorMessage(err, t('browse.load_error'))));
    };

    useEffect(load, []); // eslint-disable-line react-hooks/exhaustive-deps

    useEffect(() => {
        if (!finished || recordedRef.current || !questions || questions.length === 0) return;
        recordedRef.current = true;

        api.post('/student/practice/sessions', {
            subject_id: params.subject,
            question_count: questions.length,
            correct_count: correct,
        })
            .then(() => flash.success(t('practice.recorded')))
            .catch((err) => flash.error(errorMessage(err, t('common.error'))));
    }, [finished, questions, correct, params.subject, t]);

    const current = questions?.[index];
    const correctOption = current?.options?.find((option) => option.is_correct) ?? null;

    const check = () => {
        if (selected === null || !current) return;

        setChecked(true);

        if (correctOption && selected === correctOption.id) {
            setCorrect((value) => value + 1);
        }
    };

    const next = () => {
        if (!questions) return;

        if (index >= questions.length - 1) {
            setFinished(true);
            return;
        }

        setIndex((value) => value + 1);
        setSelected(null);
        setChecked(false);
    };

    const backHref = '/practice';

    if (error) {
        return (
            <>
                <PublicPageHeader title={t('practice.title')} />
                <ErrorCard message={error} onRetry={load} />
            </>
        );
    }

    if (!questions) {
        return (
            <>
                <PublicPageHeader title={t('practice.title')} />
                <LoadingBlock />
            </>
        );
    }

    if (questions.length === 0) {
        return (
            <>
                <PublicPageHeader title={t('practice.title')} />
                <div className="rounded-card border border-dashed border-hairline bg-white px-4 py-10 text-center">
                    <p className="text-sm text-ink-muted">{t('practice.no_questions')}</p>
                    <AppLink href={backHref} className="mt-3 inline-block text-sm font-medium text-brand-accent hover:underline">
                        {t('practice.back')}
                    </AppLink>
                </div>
            </>
        );
    }

    if (finished) {
        const percent = Math.round((correct / questions.length) * 100);

        return (
            <>
                <PublicPageHeader title={t('practice.done_title')} crumbs={[{ label: t('nav.practice'), href: backHref }]} />

                <div className="mx-auto max-w-md rounded-card border border-hairline bg-white p-8 text-center shadow-sm">
                    <p className="text-5xl font-bold text-brand">{n(percent)}%</p>
                    <p className="mt-2 text-ink">{t('practice.done_score', { correct: n(correct), total: n(questions.length) })}</p>

                    <div className="mt-6 flex flex-col gap-2 sm:flex-row sm:justify-center">
                        <Button onClick={load}>{t('practice.again')}</Button>
                        <AppLink
                            href={backHref}
                            className="inline-flex items-center justify-center rounded-control border border-hairline bg-white px-4 py-2 text-sm font-medium text-ink hover:bg-gray-50"
                        >
                            {t('practice.back')}
                        </AppLink>
                    </div>
                </div>
            </>
        );
    }

    if (!current) {
        return null;
    }

    const meta = [
        current.exam_stage ? tx(EXAM_STAGE_LABELS[current.exam_stage] ?? null) || current.exam_stage : null,
        current.exam_year ? n(current.exam_year) : null,
    ].filter(Boolean);

    return (
        <>
            <PublicPageHeader
                title={t('practice.title')}
                crumbs={[{ label: t('nav.practice'), href: backHref }]}
                aside={
                    <span className="rounded-chip bg-brass-soft px-3 py-1 text-sm font-semibold text-brass-deep">
                        {n(correct)} / {n(index + (checked ? 1 : 0))}
                    </span>
                }
            >
                <p className="mt-1 text-sm text-ink-muted">
                    {t('runner.question_n', { n: n(index + 1), total: n(questions.length) })}
                </p>
                <div className="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-gray-200">
                    <div
                        className="h-full rounded-full bg-linear-to-r from-brass to-banyan transition-all"
                        style={{ width: `${Math.round(((index + (checked ? 1 : 0)) / questions.length) * 100)}%` }}
                    />
                </div>
            </PublicPageHeader>

            <section className="mx-auto max-w-3xl rounded-card border border-hairline bg-white p-5 shadow-sm md:p-6">
                <p className="whitespace-pre-line text-[15px] font-medium leading-relaxed text-ink md:text-base">
                    {tx(current.question)}
                </p>
                {meta.length > 0 && <p className="mt-2 text-xs text-ink-muted">{meta.join(' · ')}</p>}

                <ul className="mt-4 space-y-2">
                    {(current.options ?? []).map((option, optionIndex) => {
                        const active = selected === option.id;
                        let cls = active
                            ? 'border-brand bg-brand/5'
                            : 'border-hairline hover:border-brand-muted hover:bg-gray-50';

                        if (checked) {
                            cls = option.is_correct
                                ? 'border-emerald-400 bg-emerald-50 text-emerald-900'
                                : active
                                  ? 'border-red-400 bg-red-50 text-red-900'
                                  : 'border-hairline opacity-70';
                        }

                        return (
                            <li key={option.id}>
                                <label className={`flex items-start gap-3 rounded-control border px-4 py-3 text-sm text-ink transition ${checked ? '' : 'cursor-pointer'} ${cls}`}>
                                    <input
                                        type="radio"
                                        name={`practice-${current.id}`}
                                        checked={active}
                                        onChange={() => setSelected(option.id)}
                                        disabled={checked}
                                        className="mt-0.5 h-4 w-4 accent-brand"
                                    />
                                    <span className="shrink-0 font-semibold text-ink-muted">{String.fromCharCode(65 + optionIndex)}.</span>
                                    <span className="flex-1">{tx(option.option)}</span>
                                    {checked && option.is_correct && <CheckCircleIcon className="h-5 w-5 shrink-0 text-emerald-600" />}
                                    {checked && active && !option.is_correct && <XCircleIcon className="h-5 w-5 shrink-0 text-red-500" />}
                                </label>
                            </li>
                        );
                    })}
                </ul>

                {checked && (
                    <div
                        className={
                            'mt-4 rounded-control px-4 py-3 text-sm ' +
                            (correctOption && selected === correctOption.id
                                ? 'bg-emerald-50 text-emerald-900'
                                : 'bg-red-50 text-red-900')
                        }
                    >
                        <p className="font-semibold">
                            {correctOption && selected === correctOption.id ? t('practice.correct') : t('practice.wrong')}
                        </p>
                        {tx(current.explanation) && (
                            <p className="mt-1 whitespace-pre-line text-ink">
                                <span className="font-semibold">{t('archive.explanation')}: </span>
                                {tx(current.explanation)}
                            </p>
                        )}
                        {current.reference && (
                            <p className="mt-1 text-xs text-ink-muted">
                                {t('archive.reference')}: {current.reference}
                            </p>
                        )}
                    </div>
                )}

                <div className="mt-5 flex items-center justify-end gap-2 border-t border-hairline pt-4">
                    {!checked ? (
                        <Button onClick={check} disabled={selected === null}>
                            {t('practice.check')}
                        </Button>
                    ) : (
                        <Button onClick={next}>
                            {index >= questions.length - 1 ? t('practice.finish') : t('practice.next')}
                        </Button>
                    )}
                </div>
            </section>
        </>
    );
}

PracticeRun.layout = (page: ReactNode) => <PublicLayout>{page}</PublicLayout>;
