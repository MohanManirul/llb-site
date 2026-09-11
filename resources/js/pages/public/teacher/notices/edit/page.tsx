import { type ReactNode } from 'react';
import PortalLayout from '@/components/public/PortalLayout';
import PublicPageHeader from '@/components/public/PublicPageHeader';
import { ErrorCard, LoadingBlock } from '@/components/public/helpers';
import CollegeNoticeForm from '../components/CollegeNoticeForm';
import usePublicResource from '@/hooks/usePublicResource';
import useTranslation from '@/hooks/useTranslation';
import { teacherPortalNav } from '@/config/portalNav';
import type { TeacherNotice } from '../../types';

interface TeacherNoticeEditProps {
    noticeId: number | string;
}

export default function TeacherNoticeEdit({ noticeId }: TeacherNoticeEditProps) {
    const { t } = useTranslation();

    const notice = usePublicResource<TeacherNotice>(`/teacher/college/notices/${noticeId}`, {
        errorMessage: t('common.error'),
    });

    return (
        <>
            <PublicPageHeader title={t('teacher.edit_notice')} />

            <div className="rounded-card border border-hairline bg-white p-5 shadow-sm">
                {notice.error ? (
                    <ErrorCard message={notice.error} onRetry={notice.refetch} />
                ) : !notice.data ? (
                    <LoadingBlock />
                ) : (
                    <CollegeNoticeForm notice={notice.data} />
                )}
            </div>
        </>
    );
}

TeacherNoticeEdit.layout = (page: ReactNode) => (
    <PortalLayout items={teacherPortalNav}>{page}</PortalLayout>
);
