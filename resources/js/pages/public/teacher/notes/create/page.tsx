import { type ReactNode } from 'react';
import PortalLayout from '@/components/public/PortalLayout';
import PublicPageHeader from '@/components/public/PublicPageHeader';
import NoteForm from '../components/NoteForm';
import useTranslation from '@/hooks/useTranslation';
import { teacherPortalNav } from '@/config/portalNav';

export default function TeacherNoteCreate() {
    const { t } = useTranslation();

    return (
        <>
            <PublicPageHeader title={t('teacher.create_note')} />
            <div className="rounded-card border border-hairline bg-white p-5 shadow-sm">
                <NoteForm />
            </div>
        </>
    );
}

TeacherNoteCreate.layout = (page: ReactNode) => (
    <PortalLayout items={teacherPortalNav}>{page}</PortalLayout>
);
