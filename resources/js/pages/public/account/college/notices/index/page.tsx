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
import type { CollegeNoticeItem } from '../../../../types';

export default function CollegeNoticesIndex() {
    const { t, tx, d } = useTranslation();
    const { student } = useStudent();

    const [params, setParams] = useQueryParams({ page: '1' });

    const list = usePublicList<CollegeNoticeItem>({
        url: '/student/college/notices',
        params: { page: params.page !== '1' ? params.page : undefined },
        errorMessage: t('browse.load_error'),
    });

    const hasCollege = Boolean(student?.college);

    return (
        <>
            <PublicPageHeader title={t('college.notices_title')} />

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
                    {t('college.notices_empty')}
                </p>
            ) : (
                <>
                    <ul className="space-y-3">
                        {list.rows.map((notice) => (
                            <li
                                key={notice.id}
                                className="rounded-card border border-hairline bg-white p-4 shadow-sm"
                            >
                                <AppLink
                                    href={`/account/college/notices/${notice.id}`}
                                    className="font-medium text-ink hover:text-brand-accent"
                                >
                                    {tx(notice.title)}
                                </AppLink>

                                <p className="mt-0.5 text-xs text-ink-muted">
                                    {notice.is_pinned ? `${t('notice.pinned')} · ` : ''}
                                    {notice.published_at ? d(notice.published_at) : ''}
                                </p>

                                {tx(notice.excerpt) && (
                                    <p className="mt-2 text-sm text-ink-muted">
                                        {tx(notice.excerpt)}
                                    </p>
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

CollegeNoticesIndex.layout = (page: ReactNode) => (
    <PortalLayout items={studentPortalNav}>{page}</PortalLayout>
);
