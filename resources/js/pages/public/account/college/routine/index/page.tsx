import { type ReactNode } from 'react';
import PortalLayout from '@/components/public/PortalLayout';
import PublicPageHeader from '@/components/public/PublicPageHeader';
import AppLink from '@/components/public/AppLink';
import { ErrorCard, LoadingBlock } from '@/components/public/helpers';
import { Pagination } from '@/components/ui';
import usePublicList from '@/hooks/usePublicList';
import useQueryParams from '@/hooks/useQueryParams';
import useStudent from '@/hooks/useStudent';
import useTranslation from '@/hooks/useTranslation';
import { studentPortalNav } from '@/config/portalNav';
import type { CollegeRoutineItem } from '../../../../types';

export default function CollegeRoutineIndex() {
    const { t, tx, d } = useTranslation();
    const { student } = useStudent();

    const [params, setParams] = useQueryParams({ page: '1' });

    const list = usePublicList<CollegeRoutineItem>({
        url: '/student/college/routines',
        params: { page: params.page !== '1' ? params.page : undefined },
        errorMessage: t('browse.load_error'),
    });

    const hasCollege = Boolean(student?.college);

    return (
        <>
            <PublicPageHeader title={t('college.routine_title')} />

            {!hasCollege ? (
                <div className="rounded-card border border-hairline bg-white p-6">
                    <p className="font-medium text-ink">{t('college.no_college_title')}</p>
                    <p className="mt-1 text-sm text-ink-muted">
                        {t('college.no_college_message')}
                    </p>
                    <AppLink
                        href="/account/profile"
                        className="mt-3 inline-block font-medium text-brand-accent hover:underline"
                    >
                        {t('college.go_to_profile')}
                    </AppLink>
                </div>
            ) : list.error ? (
                <ErrorCard message={list.error} onRetry={list.refetch} />
            ) : list.loading ? (
                <LoadingBlock />
            ) : list.rows.length === 0 ? (
                <p className="rounded-card border border-hairline bg-white p-6 text-sm text-ink-muted">
                    {t('college.routine_empty')}
                </p>
            ) : (
                <>
                    <ul className="space-y-3">
                        {list.rows.map((routine) => (
                            <li
                                key={routine.id}
                                className="rounded-card border border-hairline bg-white p-4 shadow-sm"
                            >
                                <p className="font-medium text-ink">{tx(routine.title)}</p>

                                {routine.effective_from && (
                                    <p className="mt-0.5 text-xs text-ink-muted">
                                        {t('college.effective_from', {
                                            date: d(routine.effective_from),
                                        })}
                                    </p>
                                )}

                                {tx(routine.description) && (
                                    <p className="mt-2 text-sm whitespace-pre-line text-ink-muted">
                                        {tx(routine.description)}
                                    </p>
                                )}

                                {routine.has_attachment && (
                                    <a
                                        href={`/v1/student/college/routines/${routine.id}/attachment`}
                                        className="mt-3 inline-block text-sm font-medium text-brand-accent hover:underline"
                                    >
                                        {t('college.download')}
                                    </a>
                                )}
                            </li>
                        ))}
                    </ul>

                    {list.pagination && (
                        <div className="mt-4">
                            <Pagination
                                {...list.pagination}
                                onPageChange={(page) => setParams({ page: String(page) })}
                            />
                        </div>
                    )}
                </>
            )}
        </>
    );
}

CollegeRoutineIndex.layout = (page: ReactNode) => (
    <PortalLayout items={studentPortalNav}>{page}</PortalLayout>
);
