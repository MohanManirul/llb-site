import { useState, type ReactNode } from 'react';
import { Link, router } from '@inertiajs/react';
import { ClockIcon, MinusCircleIcon, PlayIcon, QueueListIcon } from '@heroicons/react/24/outline';
import PublicLayout from '@/components/public/PublicLayout';
import PublicPageHeader from '@/components/public/PublicPageHeader';
import AppLink from '@/components/public/AppLink';
import { ErrorCard, LoadingBlock, PublicEmptyState } from '@/components/public/helpers';
import { Button, StatusBadge } from '@/components/ui';
import api from '@/lib/api-client';
import type { ApiEnvelope } from '@/lib/api-types';
import { errorMessage, flash } from '@/lib/flash';
import usePublicResource from '@/hooks/usePublicResource';
import useStudent from '@/hooks/useStudent';
import useTranslation from '@/hooks/useTranslation';
import type { PageMeta } from '../../types';
import { EXAM_STAGE_LABELS, type AttemptStatus, type StudentAttempt, type StudentModelTest } from '../../types';

interface ModelTestShowProps {
    modelTestSlug: string;
    meta: PageMeta;
}

const STATUS_TONE: Record<AttemptStatus, 'green' | 'yellow' | 'gray'> = {
    submitted: 'green',
    in_progress: 'yellow',
    expired: 'gray',
};

export default function PublicModelTestShow({ modelTestSlug, meta }: ModelTestShowProps) {
    const { t, tx, d, n, isBn, localeHref } = useTranslation();
    const { student, loginHref } = useStudent();
    const [starting, setStarting] = useState(false);

    const url = student ? `/student/model-tests/${modelTestSlug}` : `/public/model-tests/${modelTestSlug}`;

    const { data: test, loading, error, notFound, refetch } = usePublicResource<StudentModelTest>(url, {
        errorMessage: t('browse.load_error'),
    });

    const metaTitle = (isBn ? meta.title_bn : (meta.title_en ?? meta.title_bn)) || meta.title_bn;

    if (loading) {
        return (
            <>
                <PublicPageHeader title={metaTitle} />
                <LoadingBlock />
            </>
        );
    }

    if (notFound || (!test && !error)) {
        return (
            <>
                <PublicPageHeader title={metaTitle} />
                <PublicEmptyState />
            </>
        );
    }

    if (error || !test) {
        return (
            <>
                <PublicPageHeader title={metaTitle} />
                <ErrorCard message={error ?? t('browse.load_error')} onRetry={refetch} />
            </>
        );
    }

    const attempts = test.my_attempts ?? [];
    const inProgress = attempts.find((attempt) => attempt.status === 'in_progress') ?? null;
    const negative = Number(test.negative_mark);
    const runnerHref = (attemptId: number) => localeHref(`/model-tests/${test.slug}/attempts/${attemptId}`);

    const start = async () => {
        setStarting(true);

        try {
            const { data } = await api.post<ApiEnvelope<StudentAttempt>>(`/student/model-tests/${test.slug}/attempts`);
            router.visit(runnerHref(data.result.id));
        } catch (err) {
            flash.error(errorMessage(err, t('common.error')));
            setStarting(false);
        }
    };

    return (
        <>
            <PublicPageHeader
                title={tx(test.title)}
                metaTitle={metaTitle}
                description={tx(test.description) || null}
                crumbs={[
                    { label: t('nav.exam_prep'), href: '/exam-prep' },
                    { label: t('nav.model_tests'), href: '/model-tests' },
                    { label: tx(test.title) },
                ]}
            >
                <p className="mt-1 text-sm text-ink-muted">
                    {test.program ? tx(test.program.name) : ''}
                    {test.exam_stage ? ` · ${tx(EXAM_STAGE_LABELS[test.exam_stage] ?? null) || test.exam_stage}` : ''}
                    {test.published_at ? ` · ${t('material.published')} ${d(test.published_at)}` : ''}
                </p>
            </PublicPageHeader>

            <div className="grid gap-6 lg:grid-cols-[1fr_minmax(0,20rem)]">
                <div>
                    {tx(test.description) && (
                        <p className="whitespace-pre-line rounded-card border border-hairline bg-white p-5 text-[15px] leading-relaxed text-ink shadow-sm">
                            {tx(test.description)}
                        </p>
                    )}

                    <section className="mt-5 rounded-card border border-hairline bg-white p-5 shadow-sm">
                        <h2 className="font-semibold text-ink">{t('mt.rules_title')}</h2>
                        <ul className="mt-2 list-disc space-y-1 pl-5 text-sm text-ink">
                            <li>{t('mt.rule_time', { minutes: n(test.duration_minutes) })}</li>
                            {negative > 0 ? (
                                <li>{t('mt.rule_negative', { mark: n(negative) })}</li>
                            ) : (
                                <li>{t('mt.no_negative')}</li>
                            )}
                            <li>{t('mt.rule_submit')}</li>
                        </ul>
                    </section>

                    {student && (
                        <section className="mt-5">
                            <h2 className="font-semibold text-ink">{t('mt.my_attempts')}</h2>

                            {attempts.length === 0 ? (
                                <p className="mt-2 text-sm text-ink-muted">{t('mt.no_attempts')}</p>
                            ) : (
                                <ul className="mt-2 divide-y divide-hairline rounded-card border border-hairline bg-white">
                                    {attempts.map((attempt) => (
                                        <li key={attempt.id} className="flex flex-wrap items-center gap-3 px-4 py-3 text-sm">
                                            <span className="min-w-0 flex-1 text-ink-muted">
                                                {t('mt.attempted_on', { date: d(attempt.started_at) })}
                                            </span>
                                            {attempt.status !== 'in_progress' && (
                                                <span className="font-semibold text-brand">{n(attempt.score)}</span>
                                            )}
                                            <StatusBadge
                                                status={t(`result.status_${attempt.status}` as const)}
                                                tone={STATUS_TONE[attempt.status]}
                                            />
                                            {attempt.status === 'in_progress' ? (
                                                <Link href={runnerHref(attempt.id)} className="font-medium text-brand-accent hover:underline">
                                                    {t('mt.resume')}
                                                </Link>
                                            ) : (
                                                <AppLink href={`/account/attempts/${attempt.id}`} className="font-medium text-brand-accent hover:underline">
                                                    {t('mt.view_result')}
                                                </AppLink>
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </section>
                    )}
                </div>

                <aside className="h-fit rounded-card border border-hairline bg-white p-5 shadow-sm lg:sticky lg:top-24">
                    <dl className="space-y-3 text-sm">
                        <div className="flex items-center gap-2">
                            <QueueListIcon className="h-5 w-5 text-brass-deep" />
                            <dd className="text-ink">{t('mt.questions', { count: n(test.question_count ?? 0) })}</dd>
                        </div>
                        <div className="flex items-center gap-2">
                            <ClockIcon className="h-5 w-5 text-brass-deep" />
                            <dd className="text-ink">{t('mt.duration', { minutes: n(test.duration_minutes) })}</dd>
                        </div>
                        <div className="flex items-center gap-2">
                            <MinusCircleIcon className="h-5 w-5 text-brass-deep" />
                            <dd className="text-ink">
                                {negative > 0 ? t('mt.negative', { mark: n(negative) }) : t('mt.no_negative')}
                            </dd>
                        </div>
                    </dl>

                    <div className="mt-5">
                        {!student ? (
                            <Link
                                href={loginHref(localeHref(`/model-tests/${test.slug}`))}
                                className="flex w-full items-center justify-center gap-2 rounded-control bg-brand px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-accent"
                            >
                                <PlayIcon className="h-4 w-4" />
                                {t('mt.login_to_start')}
                            </Link>
                        ) : inProgress ? (
                            <Link
                                href={runnerHref(inProgress.id)}
                                className="flex w-full items-center justify-center gap-2 rounded-control bg-brand px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-accent"
                            >
                                <PlayIcon className="h-4 w-4" />
                                {t('mt.resume')}
                            </Link>
                        ) : (
                            <Button onClick={start} loading={starting} fullWidth disabled={(test.question_count ?? 0) === 0}>
                                <PlayIcon className="h-4 w-4" />
                                {starting ? t('mt.starting') : t('mt.start')}
                            </Button>
                        )}
                    </div>
                </aside>
            </div>
        </>
    );
}

PublicModelTestShow.layout = (page: ReactNode) => <PublicLayout>{page}</PublicLayout>;
