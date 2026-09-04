import { useCallback, useEffect, useMemo, useRef, useState, type ReactNode } from 'react';
import { router } from '@inertiajs/react';
import { ChevronLeftIcon, ChevronRightIcon, ClockIcon, PaperAirplaneIcon } from '@heroicons/react/24/outline';
import PublicLayout from '@/components/public/PublicLayout';
import PublicPageHeader from '@/components/public/PublicPageHeader';
import { ErrorCard, LoadingBlock, PublicEmptyState } from '@/components/public/helpers';
import { Button, ConfirmationModal } from '@/components/ui';
import api from '@/lib/api-client';
import type { ApiEnvelope } from '@/lib/api-types';
import { errorMessage, flash } from '@/lib/flash';
import usePublicResource from '@/hooks/usePublicResource';
import useTranslation from '@/hooks/useTranslation';
import type { StudentAttempt } from '../../types';

interface RunnerProps {
    modelTestSlug: string;
    attemptId: number | string;
}

function formatClock(seconds: number): string {
    const s = Math.max(0, seconds);
    const h = Math.floor(s / 3600);
    const m = Math.floor((s % 3600) / 60);
    const sec = s % 60;
    const pad = (value: number) => String(value).padStart(2, '0');

    return h > 0 ? `${h}:${pad(m)}:${pad(sec)}` : `${pad(m)}:${pad(sec)}`;
}

export default function ModelTestRunner({ attemptId }: RunnerProps) {
    const { t, tx, n, localeHref } = useTranslation();

    const { data: attempt, loading, error, notFound, refetch } = usePublicResource<StudentAttempt>(
        `/student/attempts/${attemptId}`,
        { errorMessage: t('browse.load_error') },
    );

    const [answers, setAnswers] = useState<Record<number, number | null>>({});
    const [index, setIndex] = useState(0);
    const [remaining, setRemaining] = useState<number | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [savingId, setSavingId] = useState<number | null>(null);
    const deadlineRef = useRef<number | null>(null);
    const submittedRef = useRef(false);

    const resultHref = localeHref(`/account/attempts/${attemptId}`);

    useEffect(() => {
        if (!attempt) return;

        if (attempt.status !== 'in_progress') {
            router.visit(resultHref, { replace: true });
            return;
        }

        const initial: Record<number, number | null> = {};
        Object.entries(attempt.answers ?? {}).forEach(([questionId, optionId]) => {
            initial[Number(questionId)] = optionId;
        });
        setAnswers(initial);

        const seconds = attempt.remaining_seconds ?? 0;
        deadlineRef.current = Date.now() + seconds * 1000;
        setRemaining(seconds);
    }, [attempt, resultHref]);

    const submit = useCallback(async () => {
        if (submittedRef.current) return;
        submittedRef.current = true;
        setSubmitting(true);
        setConfirmOpen(false);

        try {
            await api.post(`/student/attempts/${attemptId}/submit`);
            router.visit(resultHref, { replace: true });
        } catch (err) {
            const status = (err as { response?: { status?: number } })?.response?.status;

            if (status === 422) {
                router.visit(resultHref, { replace: true });
                return;
            }

            submittedRef.current = false;
            setSubmitting(false);
            flash.error(errorMessage(err, t('common.error')));
        }
    }, [attemptId, resultHref, t]);

    useEffect(() => {
        if (remaining === null || deadlineRef.current === null) return;

        const timer = window.setInterval(() => {
            const left = Math.ceil((deadlineRef.current! - Date.now()) / 1000);
            setRemaining(left);

            if (left <= 0) {
                window.clearInterval(timer);
                flash.error(t('runner.time_up'));
                submit();
            }
        }, 1000);

        return () => window.clearInterval(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [remaining === null, submit]);

    const questions = useMemo(() => attempt?.questions ?? [], [attempt]);
    const current = questions[index];
    const answeredCount = useMemo(
        () => questions.filter((question) => answers[question.id] != null).length,
        [questions, answers],
    );

    const choose = async (questionId: number, optionId: number | null) => {
        const previous = answers[questionId] ?? null;
        setAnswers((state) => ({ ...state, [questionId]: optionId }));
        setSavingId(questionId);

        try {
            await api.put(`/student/attempts/${attemptId}/answers`, {
                question_id: questionId,
                question_option_id: optionId,
            });
        } catch (err) {
            const status = (err as { response?: { status?: number } })?.response?.status;

            setAnswers((state) => ({ ...state, [questionId]: previous }));

            if (status === 422) {
                flash.error(errorMessage(err, t('runner.finished')));
                router.visit(resultHref, { replace: true });
            } else {
                flash.error(t('runner.save_failed'));
            }
        } finally {
            setSavingId((value) => (value === questionId ? null : value));
        }
    };

    if (loading || (attempt && attempt.status !== 'in_progress')) {
        return (
            <>
                <PublicPageHeader title={t('nav.model_tests')} />
                <LoadingBlock />
            </>
        );
    }

    if (notFound) {
        return (
            <>
                <PublicPageHeader title={t('nav.model_tests')} />
                <PublicEmptyState />
            </>
        );
    }

    if (error || !attempt || !current) {
        return (
            <>
                <PublicPageHeader title={t('nav.model_tests')} />
                <ErrorCard message={error ?? t('browse.load_error')} onRetry={refetch} />
            </>
        );
    }

    const urgent = remaining !== null && remaining <= 60;
    const selected = answers[current.id] ?? null;

    return (
        <>
            <PublicPageHeader
                title={attempt.model_test ? tx(attempt.model_test.title) : t('nav.model_tests')}
                aside={
                    <div
                        className={
                            'flex items-center gap-2 rounded-control border px-3 py-1.5 font-mono text-lg font-semibold ' +
                            (urgent ? 'animate-pulse border-red-300 bg-red-50 text-red-700' : 'border-hairline bg-white text-ink')
                        }
                        aria-live="polite"
                    >
                        <ClockIcon className="h-5 w-5" />
                        <span className="sr-only">{t('runner.remaining')}</span>
                        {formatClock(remaining ?? 0)}
                    </div>
                }
            >
                <p className="mt-1 text-sm text-ink-muted">
                    {t('runner.question_n', { n: n(index + 1), total: n(questions.length) })} ·{' '}
                    {t('runner.answered', { count: n(answeredCount) })}
                </p>
            </PublicPageHeader>

            <div className="grid gap-5 lg:grid-cols-[1fr_minmax(0,18rem)]">
                <section className="rounded-card border border-hairline bg-white p-5 shadow-sm md:p-6">
                    <p className="flex gap-2 text-[15px] font-medium leading-relaxed text-ink md:text-base">
                        <span className="shrink-0 text-ink-muted">{n(index + 1)}.</span>
                        <span className="whitespace-pre-line">{tx(current.question)}</span>
                        <span className="ml-auto shrink-0 text-xs text-ink-muted">{n(current.marks)}</span>
                    </p>

                    <ul className="mt-4 space-y-2">
                        {current.options.map((option, optionIndex) => {
                            const active = selected === option.id;

                            return (
                                <li key={option.id}>
                                    <label
                                        className={
                                            'flex cursor-pointer items-start gap-3 rounded-control border px-4 py-3 text-sm transition ' +
                                            (active
                                                ? 'border-brand bg-brand/5 text-ink'
                                                : 'border-hairline text-ink hover:border-brand-muted hover:bg-gray-50')
                                        }
                                    >
                                        <input
                                            type="radio"
                                            name={`question-${current.id}`}
                                            checked={active}
                                            onChange={() => choose(current.id, option.id)}
                                            disabled={submitting}
                                            className="mt-0.5 h-4 w-4 accent-brand"
                                        />
                                        <span className="shrink-0 font-semibold text-ink-muted">
                                            {String.fromCharCode(65 + optionIndex)}.
                                        </span>
                                        <span>{tx(option.option)}</span>
                                    </label>
                                </li>
                            );
                        })}
                    </ul>

                    <div className="mt-5 flex flex-wrap items-center gap-2 border-t border-hairline pt-4">
                        <Button
                            variant="secondary"
                            size="sm"
                            onClick={() => setIndex((value) => Math.max(0, value - 1))}
                            disabled={index === 0 || submitting}
                        >
                            <ChevronLeftIcon className="h-4 w-4" />
                            {t('runner.prev')}
                        </Button>

                        {selected !== null && (
                            <button
                                type="button"
                                onClick={() => choose(current.id, null)}
                                disabled={submitting || savingId === current.id}
                                className="text-sm font-medium text-ink-muted hover:text-red-600 hover:underline disabled:opacity-50"
                            >
                                {t('runner.clear')}
                            </button>
                        )}

                        <span className="ml-auto" />

                        {index < questions.length - 1 ? (
                            <Button size="sm" onClick={() => setIndex((value) => value + 1)} disabled={submitting}>
                                {t('runner.next')}
                                <ChevronRightIcon className="h-4 w-4" />
                            </Button>
                        ) : (
                            <Button size="sm" onClick={() => setConfirmOpen(true)} loading={submitting}>
                                <PaperAirplaneIcon className="h-4 w-4" />
                                {t('runner.submit')}
                            </Button>
                        )}
                    </div>
                </section>

                <aside className="h-fit rounded-card border border-hairline bg-white p-4 shadow-sm lg:sticky lg:top-24">
                    <p className="text-xs font-semibold uppercase tracking-wide text-ink-muted">{t('runner.palette')}</p>

                    <div className="mt-3 grid grid-cols-6 gap-1.5 sm:grid-cols-8 lg:grid-cols-5">
                        {questions.map((question, questionIndex) => {
                            const answered = answers[question.id] != null;
                            const isCurrent = questionIndex === index;

                            return (
                                <button
                                    key={question.id}
                                    type="button"
                                    onClick={() => setIndex(questionIndex)}
                                    aria-current={isCurrent ? 'step' : undefined}
                                    className={
                                        'h-9 rounded-control border text-sm font-medium transition ' +
                                        (isCurrent
                                            ? 'border-brand bg-brand text-white'
                                            : answered
                                              ? 'border-emerald-300 bg-emerald-50 text-emerald-800'
                                              : 'border-hairline bg-white text-ink hover:border-brand-muted')
                                    }
                                >
                                    {n(questionIndex + 1)}
                                </button>
                            );
                        })}
                    </div>

                    <Button
                        className="mt-4"
                        fullWidth
                        variant="dark"
                        onClick={() => setConfirmOpen(true)}
                        loading={submitting}
                    >
                        <PaperAirplaneIcon className="h-4 w-4" />
                        {t('runner.submit')}
                    </Button>
                </aside>
            </div>

            <ConfirmationModal
                show={confirmOpen}
                onClose={() => setConfirmOpen(false)}
                onConfirm={submit}
                processing={submitting}
                variant="primary"
                title={t('runner.submit')}
                confirmText={t('runner.submit')}
                cancelText={t('common.cancel')}
            >
                {t('runner.submit_confirm', { unanswered: n(questions.length - answeredCount) })}
            </ConfirmationModal>
        </>
    );
}

ModelTestRunner.layout = (page: ReactNode) => <PublicLayout>{page}</PublicLayout>;
