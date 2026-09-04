import { useMemo, type ReactNode } from 'react';
import { ClockIcon } from '@heroicons/react/24/outline';
import PublicLayout from '@/components/public/PublicLayout';
import PublicPageHeader from '@/components/public/PublicPageHeader';
import AppLink from '@/components/public/AppLink';
import { ErrorCard, LoadingBlock } from '@/components/public/helpers';
import { Pagination, StatusBadge } from '@/components/ui';
import usePublicList from '@/hooks/usePublicList';
import useQueryParams from '@/hooks/useQueryParams';
import useTranslation from '@/hooks/useTranslation';
import type { AttemptStatus, StudentAttempt } from '../../../types';

const STATUS_TONE: Record<AttemptStatus, 'green' | 'yellow' | 'gray'> = {
    submitted: 'green',
    in_progress: 'yellow',
    expired: 'gray',
};

export default function StudentAttemptsIndex() {
    const { t, tx, d, n } = useTranslation();
    const [params, setParams] = useQueryParams({ page: '1' });

    const listParams = useMemo(
        () => ({ page: params.page !== '1' ? params.page : undefined }),
        [params.page],
    );

    const list = usePublicList<StudentAttempt>({
        url: '/student/attempts',
        params: listParams,
        errorMessage: t('browse.load_error'),
    });

    const statusLabel = (status: AttemptStatus) => t(`result.status_${status}` as const);

    const hrefFor = (attempt: StudentAttempt) =>
        attempt.status === 'in_progress' && attempt.model_test
            ? `/model-tests/${attempt.model_test.slug}/attempts/${attempt.id}`
            : `/account/attempts/${attempt.id}`;

    return (
        <>
            <PublicPageHeader
                title={t('account.attempts_title')}
                crumbs={[{ label: t('nav.exam_prep'), href: '/exam-prep' }, { label: t('nav.my_attempts') }]}
            />

            {list.error ? (
                <ErrorCard message={list.error} onRetry={list.refetch} />
            ) : list.loading ? (
                <LoadingBlock />
            ) : list.rows.length === 0 ? (
                <div className="rounded-card border border-dashed border-hairline bg-white px-4 py-10 text-center">
                    <p className="text-sm text-ink-muted">{t('account.no_attempts')}</p>
                    <AppLink href="/model-tests" className="mt-3 inline-block text-sm font-medium text-brand-accent hover:underline">
                        {t('result.back_to_tests')}
                    </AppLink>
                </div>
            ) : (
                <>
                    <ul className="space-y-3">
                        {list.rows.map((attempt) => (
                            <li key={attempt.id}>
                                <AppLink
                                    href={hrefFor(attempt)}
                                    className="flex flex-wrap items-center gap-3 rounded-card border border-hairline bg-white p-4 shadow-sm transition hover:border-brand-muted hover:shadow"
                                >
                                    <span className="min-w-0 flex-1">
                                        <span className="block font-semibold text-ink">
                                            {attempt.model_test ? tx(attempt.model_test.title) : '—'}
                                        </span>
                                        <span className="mt-0.5 flex items-center gap-1 text-xs text-ink-muted">
                                            <ClockIcon className="h-3.5 w-3.5" />
                                            {d(attempt.started_at)}
                                        </span>
                                    </span>

                                    {attempt.status !== 'in_progress' && (
                                        <span className="text-right">
                                            <span className="block text-lg font-bold text-brand">
                                                {n(attempt.score)}
                                            </span>
                                            <span className="block text-xs text-ink-muted">
                                                {n(attempt.correct_count)} ✓ · {n(attempt.wrong_count)} ✗ ·{' '}
                                                {n(attempt.skipped_count)} –
                                            </span>
                                        </span>
                                    )}

                                    <StatusBadge status={statusLabel(attempt.status)} tone={STATUS_TONE[attempt.status]} />
                                </AppLink>
                            </li>
                        ))}
                    </ul>

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

StudentAttemptsIndex.layout = (page: ReactNode) => <PublicLayout>{page}</PublicLayout>;
