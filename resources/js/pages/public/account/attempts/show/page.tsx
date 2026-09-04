import { useEffect, type ReactNode } from 'react';
import { router } from '@inertiajs/react';
import { CheckCircleIcon, MinusCircleIcon, XCircleIcon } from '@heroicons/react/24/outline';
import PublicLayout from '@/components/public/PublicLayout';
import PublicPageHeader from '@/components/public/PublicPageHeader';
import AppLink from '@/components/public/AppLink';
import { ErrorCard, LoadingBlock, PublicEmptyState } from '@/components/public/helpers';
import api from '@/lib/api-client';
import type { ApiEnvelope } from '@/lib/api-types';
import usePublicResource from '@/hooks/usePublicResource';
import useTranslation from '@/hooks/useTranslation';
import type { AttemptResult, StudentAttempt } from '../../../types';

interface AttemptShowProps {
    attemptId: number | string;
}

export default function StudentAttemptShow({ attemptId }: AttemptShowProps) {
    const { t, tx, d, n, localeHref } = useTranslation();

    const result = usePublicResource<AttemptResult>(`/student/attempts/${attemptId}/result`, {
        errorMessage: t('browse.load_error'),
    });

    useEffect(() => {
        if (!result.error) return;

        api.get<ApiEnvelope<StudentAttempt>>(`/student/attempts/${attemptId}`)
            .then(({ data }) => {
                const attempt = data.result;

                if (attempt.status === 'in_progress' && attempt.model_test) {
                    router.visit(localeHref(`/model-tests/${attempt.model_test.slug}/attempts/${attempt.id}`));
                }
            })
            .catch(() => undefined);
    }, [result.error, attemptId, localeHref]);

    if (result.loading) {
        return (
            <>
                <PublicPageHeader title={t('result.title')} />
                <LoadingBlock />
            </>
        );
    }

    if (result.notFound) {
        return (
            <>
                <PublicPageHeader title={t('result.title')} />
                <PublicEmptyState />
            </>
        );
    }

    if (result.error || !result.data) {
        return (
            <>
                <PublicPageHeader title={t('result.title')} />
                <ErrorCard message={result.error ?? t('browse.load_error')} onRetry={result.refetch} />
            </>
        );
    }

    const attempt = result.data;
    const total = Number(attempt.model_test.total_marks);
    const score = Number(attempt.score ?? 0);
    const percent = total > 0 ? Math.max(0, Math.round((score / total) * 100)) : 0;

    return (
        <>
            <PublicPageHeader
                title={tx(attempt.model_test.title)}
                metaTitle={t('result.title')}
                crumbs={[
                    { label: t('nav.exam_prep'), href: '/exam-prep' },
                    { label: t('nav.my_attempts'), href: '/account/attempts' },
                    { label: t('result.title') },
                ]}
            >
                <p className="mt-1 text-sm text-ink-muted">
                    {t(`result.status_${attempt.status}` as const)} · {d(attempt.submitted_at ?? attempt.started_at)}
                    {attempt.model_test.program ? ` · ${tx(attempt.model_test.program.name)}` : ''}
                </p>
            </PublicPageHeader>

            <section className="grid gap-3 sm:grid-cols-4">
                <div className="rounded-card border border-hairline bg-brand p-4 text-white sm:col-span-1">
                    <p className="text-xs uppercase tracking-wide text-white/70">{t('result.score')}</p>
                    <p className="mt-1 text-3xl font-bold">
                        {n(score)}
                        <span className="text-base font-medium text-white/70"> / {n(total)}</span>
                    </p>
                    <p className="mt-1 text-xs text-brass">{n(percent)}%</p>
                </div>

                <Stat icon={<CheckCircleIcon className="h-5 w-5 text-emerald-600" />} label={t('result.correct')} value={n(attempt.correct_count)} />
                <Stat icon={<XCircleIcon className="h-5 w-5 text-red-500" />} label={t('result.wrong')} value={n(attempt.wrong_count)} />
                <Stat icon={<MinusCircleIcon className="h-5 w-5 text-gray-400" />} label={t('result.skipped')} value={n(attempt.skipped_count)} />
            </section>

            <div className="mt-4 flex flex-wrap gap-3 text-sm">
                <AppLink href={`/model-tests/${attempt.model_test.slug}`} className="font-medium text-brand-accent hover:underline">
                    {t('mt.start')}
                </AppLink>
                <AppLink href="/model-tests" className="font-medium text-brand-accent hover:underline">
                    {t('result.back_to_tests')}
                </AppLink>
            </div>

            <h2 className="mt-8 text-lg font-semibold text-ink">{t('result.breakdown')}</h2>

            <ol className="mt-3 space-y-3">
                {attempt.breakdown.map((item, index) => {
                    const chosen = item.options.find((option) => option.id === item.chosen_option_id) ?? null;
                    const tone =
                        item.chosen_option_id === null
                            ? 'border-l-gray-300'
                            : item.is_correct
                              ? 'border-l-emerald-500'
                              : 'border-l-red-500';

                    return (
                        <li
                            key={item.id}
                            className={`rounded-card border border-hairline border-l-4 bg-white p-4 shadow-sm ${tone}`}
                        >
                            <p className="flex gap-2 font-medium text-ink">
                                <span className="shrink-0 text-ink-muted">{n(index + 1)}.</span>
                                <span>{tx(item.question)}</span>
                                <span className="ml-auto shrink-0 text-xs text-ink-muted">{n(item.marks)}</span>
                            </p>

                            <ul className="mt-3 space-y-1.5">
                                {item.options.map((option) => {
                                    const isChosen = option.id === item.chosen_option_id;
                                    const cls = option.is_correct
                                        ? 'border-emerald-300 bg-emerald-50 text-emerald-900'
                                        : isChosen
                                          ? 'border-red-300 bg-red-50 text-red-900'
                                          : 'border-hairline text-ink';

                                    return (
                                        <li
                                            key={option.id}
                                            className={`flex items-center gap-2 rounded-control border px-3 py-2 text-sm ${cls}`}
                                        >
                                            {option.is_correct ? (
                                                <CheckCircleIcon className="h-4 w-4 shrink-0" />
                                            ) : isChosen ? (
                                                <XCircleIcon className="h-4 w-4 shrink-0" />
                                            ) : (
                                                <span className="h-4 w-4 shrink-0" />
                                            )}
                                            <span>{tx(option.option)}</span>
                                            {isChosen && (
                                                <span className="ml-auto text-xs opacity-70">{t('result.your_answer')}</span>
                                            )}
                                        </li>
                                    );
                                })}
                            </ul>

                            {!chosen && (
                                <p className="mt-2 text-xs text-ink-muted">{t('result.not_answered')}</p>
                            )}

                            {(tx(item.explanation) || item.reference) && (
                                <div className="mt-3 rounded-control bg-brass-soft/60 px-3 py-2 text-sm text-ink">
                                    {tx(item.explanation) && (
                                        <p>
                                            <span className="font-semibold">{t('archive.explanation')}: </span>
                                            {tx(item.explanation)}
                                        </p>
                                    )}
                                    {item.reference && (
                                        <p className="mt-1 text-xs text-ink-muted">
                                            {t('archive.reference')}: {item.reference}
                                        </p>
                                    )}
                                </div>
                            )}
                        </li>
                    );
                })}
            </ol>
        </>
    );
}

function Stat({ icon, label, value }: { icon: ReactNode; label: string; value: string }) {
    return (
        <div className="flex items-center gap-3 rounded-card border border-hairline bg-white p-4">
            {icon}
            <div>
                <p className="text-xs uppercase tracking-wide text-ink-muted">{label}</p>
                <p className="text-xl font-bold text-ink">{value}</p>
            </div>
        </div>
    );
}

StudentAttemptShow.layout = (page: ReactNode) => <PublicLayout>{page}</PublicLayout>;
