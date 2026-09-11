import { useState, type ReactNode } from 'react';
import { PlusIcon } from '@heroicons/react/24/outline';
import PortalLayout from '@/components/public/PortalLayout';
import PublicPageHeader from '@/components/public/PublicPageHeader';
import AppLink from '@/components/public/AppLink';
import { ErrorCard, LoadingBlock } from '@/components/public/helpers';
import { Button, ConfirmationModal, StatusBadge } from '@/components/ui';
import api from '@/lib/api-client';
import { errorMessage, flash } from '@/lib/flash';
import usePublicList from '@/hooks/usePublicList';
import useTeacher from '@/hooks/useTeacher';
import useTranslation from '@/hooks/useTranslation';
import { teacherPortalNav } from '@/config/portalNav';
import type { TeacherRoutine } from '../../types';

export default function TeacherRoutinesIndex() {
    const { t, tx, d } = useTranslation();
    const { teacher } = useTeacher();

    const list = usePublicList<TeacherRoutine>({
        url: '/teacher/college/routines',
        errorMessage: t('browse.load_error'),
    });

    const [pending, setPending] = useState<TeacherRoutine | null>(null);
    const [deleting, setDeleting] = useState(false);
    const [busyId, setBusyId] = useState<number | null>(null);

    const togglePublish = async (routine: TeacherRoutine) => {
        setBusyId(routine.id);
        const action = routine.status === 'published' ? 'unpublish' : 'publish';

        try {
            await api.patch(`/teacher/college/routines/${routine.id}/${action}`);
            flash.success(t(action === 'publish' ? 'teacher.published' : 'teacher.unpublished'));
            await list.refetch();
        } catch (error) {
            flash.error(errorMessage(error, t('common.error')));
        } finally {
            setBusyId(null);
        }
    };

    const confirmDelete = async () => {
        if (!pending) return;
        setDeleting(true);

        try {
            await api.delete(`/teacher/college/routines/${pending.id}`);
            flash.success(t('teacher.deleted'));
            setPending(null);
            await list.refetch();
        } catch (error) {
            flash.error(errorMessage(error, t('common.error')));
        } finally {
            setDeleting(false);
        }
    };

    return (
        <>
            <PublicPageHeader
                title={t('teacher.routines_title')}
                aside={
                    <AppLink href="/teacher/routines/create">
                        <Button size="sm">
                            <PlusIcon className="h-4 w-4" />
                            {t('teacher.create_routine')}
                        </Button>
                    </AppLink>
                }
            />

            {list.error ? (
                <ErrorCard message={list.error} onRetry={list.refetch} />
            ) : list.loading ? (
                <LoadingBlock />
            ) : list.rows.length === 0 ? (
                <p className="rounded-card border border-hairline bg-white p-6 text-sm text-ink-muted">
                    {t('teacher.empty_routines')}
                </p>
            ) : (
                <ul className="space-y-3">
                    {list.rows.map((routine) => (
                        <li
                            key={routine.id}
                            className="rounded-card border border-hairline bg-white p-4 shadow-sm"
                        >
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div className="min-w-0">
                                    <p className="font-medium text-ink">{tx(routine.title)}</p>
                                    <p className="mt-0.5 text-xs text-ink-muted">
                                        {routine.effective_from
                                            ? t('college.effective_from', {
                                                  date: d(routine.effective_from),
                                              })
                                            : '—'}
                                    </p>
                                </div>

                                <StatusBadge
                                    status={routine.status}
                                    tone={routine.status === 'published' ? 'green' : 'gray'}
                                />
                            </div>

                            <div className="mt-3 flex flex-wrap items-center gap-3 text-sm">
                                <AppLink
                                    href={`/teacher/routines/${routine.id}/edit`}
                                    className="font-medium text-brand-accent hover:underline"
                                >
                                    {t('teacher.edit_routine')}
                                </AppLink>

                                <button
                                    type="button"
                                    onClick={() => togglePublish(routine)}
                                    disabled={busyId === routine.id}
                                    className="font-medium text-ink-muted hover:text-ink disabled:opacity-50"
                                >
                                    {routine.status === 'published'
                                        ? t('teacher.unpublish')
                                        : t('teacher.publish')}
                                </button>

                                <button
                                    type="button"
                                    onClick={() => setPending(routine)}
                                    className="font-medium text-red-600 hover:underline"
                                >
                                    {t('common.delete')}
                                </button>
                            </div>
                        </li>
                    ))}
                </ul>
            )}

            <ConfirmationModal
                show={pending !== null}
                onClose={() => setPending(null)}
                onConfirm={confirmDelete}
                processing={deleting}
                title={t('teacher.delete_title')}
                confirmText={t('common.delete')}
            >
                {t('teacher.delete_confirm')}
            </ConfirmationModal>
        </>
    );
}

TeacherRoutinesIndex.layout = (page: ReactNode) => (
    <PortalLayout items={teacherPortalNav}>{page}</PortalLayout>
);
