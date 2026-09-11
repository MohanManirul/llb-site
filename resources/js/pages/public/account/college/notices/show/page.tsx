import { type ReactNode } from 'react';
import PortalLayout from '@/components/public/PortalLayout';
import PublicPageHeader from '@/components/public/PublicPageHeader';
import { ErrorCard, LoadingBlock } from '@/components/public/helpers';
import usePublicResource from '@/hooks/usePublicResource';
import useTranslation from '@/hooks/useTranslation';
import { studentPortalNav } from '@/config/portalNav';
import type { CollegeNoticeItem } from '../../../../types';

interface CollegeNoticeShowProps {
    noticeId: number | string;
}

export default function CollegeNoticeShow({ noticeId }: CollegeNoticeShowProps) {
    const { t, tx, d } = useTranslation();

    const notice = usePublicResource<CollegeNoticeItem>(
        `/student/college/notices/${noticeId}`,
        { errorMessage: t('common.error') },
    );

    return (
        <>
            <PublicPageHeader
                title={notice.data ? tx(notice.data.title) : t('college.notices_title')}
            />

            {notice.error ? (
                <ErrorCard message={notice.error} onRetry={notice.refetch} />
            ) : !notice.data ? (
                <LoadingBlock />
            ) : (
                <article className="rounded-card border border-hairline bg-white p-5 shadow-sm">
                    <p className="text-xs text-ink-muted">
                        {notice.data.published_at ? d(notice.data.published_at) : ''}
                        {notice.data.teacher
                            ? ` · ${t('college.posted_by', { name: notice.data.teacher })}`
                            : ''}
                    </p>

                    <div className="mt-3 text-sm whitespace-pre-line text-ink">
                        {tx(notice.data.body)}
                    </div>

                    {notice.data.has_attachment && (
                        <a
                            href={`/v1/student/college/notices/${notice.data.id}/attachment`}
                            className="mt-4 inline-block text-sm font-medium text-brand-accent hover:underline"
                        >
                            {t('college.download')}
                        </a>
                    )}
                </article>
            )}
        </>
    );
}

CollegeNoticeShow.layout = (page: ReactNode) => (
    <PortalLayout items={studentPortalNav}>{page}</PortalLayout>
);
