import { type ReactNode } from 'react';
import PortalLayout from '@/components/public/PortalLayout';
import PublicPageHeader from '@/components/public/PublicPageHeader';
import CollegeNoticeForm from '../components/CollegeNoticeForm';
import useTranslation from '@/hooks/useTranslation';
import { teacherPortalNav } from '@/config/portalNav';

export default function TeacherNoticeCreate() {
    const { t } = useTranslation();

    return (
        <>
            <PublicPageHeader title={t('teacher.create_notice')} />
            <div className="rounded-card border border-hairline bg-white p-5 shadow-sm">
                <CollegeNoticeForm />
            </div>
        </>
    );
}

TeacherNoticeCreate.layout = (page: ReactNode) => (
    <PortalLayout items={teacherPortalNav}>{page}</PortalLayout>
);
