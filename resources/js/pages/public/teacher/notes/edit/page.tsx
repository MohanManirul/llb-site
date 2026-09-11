import { type ReactNode } from 'react';
import PortalLayout from '@/components/public/PortalLayout';
import PublicPageHeader from '@/components/public/PublicPageHeader';
import { ErrorCard, LoadingBlock } from '@/components/public/helpers';
import NoteForm from '../components/NoteForm';
import usePublicResource from '@/hooks/usePublicResource';
import useTranslation from '@/hooks/useTranslation';
import { teacherPortalNav } from '@/config/portalNav';
import type { TeacherNote } from '../../types';

interface TeacherNoteEditProps {
    noteId: number | string;
}

export default function TeacherNoteEdit({ noteId }: TeacherNoteEditProps) {
    const { t } = useTranslation();

    const note = usePublicResource<TeacherNote>(`/teacher/college/notes/${noteId}`, {
        errorMessage: t('common.error'),
    });

    return (
        <>
            <PublicPageHeader title={t('teacher.edit_note')} />

            <div className="rounded-card border border-hairline bg-white p-5 shadow-sm">
                {note.error ? (
                    <ErrorCard message={note.error} onRetry={note.refetch} />
                ) : !note.data ? (
                    <LoadingBlock />
                ) : (
                    <NoteForm note={note.data} />
                )}
            </div>
        </>
    );
}

TeacherNoteEdit.layout = (page: ReactNode) => (
    <PortalLayout items={teacherPortalNav}>{page}</PortalLayout>
);
