import { useMemo, type ReactNode } from 'react';
import { usePage } from '@inertiajs/react';
import { ClockIcon, QueueListIcon } from '@heroicons/react/24/outline';
import PublicLayout from '@/components/public/PublicLayout';
import PublicPageHeader from '@/components/public/PublicPageHeader';
import AppLink from '@/components/public/AppLink';
import { CardGrid, ErrorCard, PublicEmptyState, SkeletonGrid } from '@/components/public/helpers';
import { Pagination } from '@/components/ui';
import usePublicList from '@/hooks/usePublicList';
import useQueryParams from '@/hooks/useQueryParams';
import useTranslation from '@/hooks/useTranslation';
import { EXAM_STAGE_LABELS, type PublicModelTest } from '../../types';

export default function PublicModelTestsIndex() {
    const { t, tx, n } = useTranslation();
    const programs = usePage().props.programs ?? [];
    const [params, setParams] = useQueryParams({ program: '', page: '1' });

    const listParams = useMemo(
        () => ({
            program: params.program || undefined,
            page: params.page !== '1' ? params.page : undefined,
        }),
        [params.program, params.page],
    );

    const list = usePublicList<PublicModelTest>({
        url: '/public/model-tests',
        params: listParams,
        errorMessage: t('browse.load_error'),
    });

    const chipClass = (active: boolean) =>
        'shrink-0 rounded-chip border px-3 py-1.5 text-sm font-medium transition ' +
        (active ? 'border-brand bg-brand text-white' : 'border-hairline bg-white text-ink hover:border-brand-muted');

    return (
        <>
            <PublicPageHeader
                title={t('prep.model_tests_title')}
                description={t('prep.model_tests_desc')}
                crumbs={[{ label: t('nav.exam_prep'), href: '/exam-prep' }, { label: t('nav.model_tests') }]}
            >
                <p className="mt-2 max-w-2xl text-sm text-ink-muted">{t('prep.model_tests_desc')}</p>
            </PublicPageHeader>

            {programs.length > 1 && (
                <div className="mb-4 flex items-center gap-2 overflow-x-auto pb-1">
                    <button type="button" onClick={() => setParams({ program: '', page: '1' })} className={chipClass(params.program === '')}>
                        {t('filter.all_programs')}
                    </button>
                    {programs.map((program) => (
                        <button
                            key={program.slug}
                            type="button"
                            onClick={() => setParams({ program: program.slug, page: '1' })}
                            className={chipClass(params.program === program.slug)}
                        >
                            {tx(program.short_name) || tx(program.name)}
                        </button>
                    ))}
                </div>
            )}

            {list.error ? (
                <ErrorCard message={list.error} onRetry={list.refetch} />
            ) : list.loading ? (
                <SkeletonGrid count={6} />
            ) : list.rows.length === 0 ? (
                <PublicEmptyState onReset={params.program ? () => setParams({ program: '', page: '1' }) : undefined} />
            ) : (
                <>
                    <CardGrid>
                        {list.rows.map((test) => (
                            <AppLink
                                key={test.id}
                                href={`/model-tests/${test.slug}`}
                                className="group flex flex-col rounded-card border border-hairline border-b-2 border-b-brass/40 bg-white p-4 shadow-sm transition hover:border-b-brass hover:shadow"
                            >
                                <p className="text-xs text-ink-muted">
                                    {test.program ? tx(test.program.name) : ''}
                                    {test.exam_stage
                                        ? ` · ${tx(EXAM_STAGE_LABELS[test.exam_stage] ?? null) || test.exam_stage}`
                                        : ''}
                                </p>
                                <h2 className="mt-1 font-semibold text-ink group-hover:text-brand">{tx(test.title)}</h2>
                                {tx(test.description) && (
                                    <p className="mt-1 line-clamp-2 text-sm text-ink-muted">{tx(test.description)}</p>
                                )}
                                <p className="mt-3 flex flex-wrap items-center gap-3 text-xs text-ink-muted">
                                    <span className="inline-flex items-center gap-1">
                                        <QueueListIcon className="h-3.5 w-3.5" />
                                        {t('mt.questions', { count: n(test.question_count ?? 0) })}
                                    </span>
                                    <span className="inline-flex items-center gap-1">
                                        <ClockIcon className="h-3.5 w-3.5" />
                                        {t('mt.duration', { minutes: n(test.duration_minutes) })}
                                    </span>
                                </p>
                            </AppLink>
                        ))}
                    </CardGrid>

                    <div className="mt-6 flex justify-center">
                        <Pagination
                            links={list.pagination?.links}
                            onPageChange={(page) => {
                                if (page == null) return;
                                setParams({ page: String(page) });
                                window.scrollTo({ top: 0 });
                            }}
                        />
                    </div>
                </>
            )}
        </>
    );
}

PublicModelTestsIndex.layout = (page: ReactNode) => <PublicLayout>{page}</PublicLayout>;
