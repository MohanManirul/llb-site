import { type ReactNode } from 'react';
import PortalLayout from '@/components/public/PortalLayout';
import PublicPageHeader from '@/components/public/PublicPageHeader';
import { ErrorCard, LoadingBlock } from '@/components/public/helpers';
import RoutineForm from '../components/RoutineForm';
import usePublicResource from '@/hooks/usePublicResource';
import useTranslation from '@/hooks/useTranslation';
import { teacherPortalNav } from '@/config/portalNav';
import type { TeacherRoutine } from '../../types';

interface TeacherRoutineEditProps {
    routineId: number | string;
}

export default function TeacherRoutineEdit({ routineId }: TeacherRoutineEditProps) {
    const { t } = useTranslation();

    const routine = usePublicResource<TeacherRoutine>(`/teacher/college/routines/${routineId}`, {
        errorMessage: t('common.error'),
    });

    return (
        <>
            <PublicPageHeader title={t('teacher.edit_routine')} />

            <div className="rounded-card border border-hairline bg-white p-5 shadow-sm">
                {routine.error ? (
                    <ErrorCard message={routine.error} onRetry={routine.refetch} />
                ) : !routine.data ? (
                    <LoadingBlock />
                ) : (
                    <RoutineForm routine={routine.data} />
                )}
            </div>
        </>
    );
}

TeacherRoutineEdit.layout = (page: ReactNode) => (
    <PortalLayout items={teacherPortalNav}>{page}</PortalLayout>
);
