import { type ReactNode } from 'react';
import PortalLayout from '@/components/public/PortalLayout';
import PublicPageHeader from '@/components/public/PublicPageHeader';
import RoutineForm from '../components/RoutineForm';
import useTranslation from '@/hooks/useTranslation';
import { teacherPortalNav } from '@/config/portalNav';

export default function TeacherRoutineCreate() {
    const { t } = useTranslation();

    return (
        <>
            <PublicPageHeader title={t('teacher.create_routine')} />
            <div className="rounded-card border border-hairline bg-white p-5 shadow-sm">
                <RoutineForm />
            </div>
        </>
    );
}

TeacherRoutineCreate.layout = (page: ReactNode) => (
    <PortalLayout items={teacherPortalNav}>{page}</PortalLayout>
);
