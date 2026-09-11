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
import useTranslation from '@/hooks/useTranslation';
import { teacherPortalNav } from '@/config/portalNav';
import type { TeacherNotice } from '../../types';

export default function TeacherNoticesIndex() {
    const { t, tx } = useTranslation();

    const list = usePublicList<TeacherNotice>({
        url: '/teacher/college/notices',
        errorMessage: t('browse.load_error'),
    });

    const [pending, setPending] = useState<TeacherNotice | null>(null);
    const [deleting, setDeleting] = useState(false);
    const [busyId, setBusyId] = useState<number | null>(null);

    const togglePublish = async (notice: TeacherNotice) => {
        setBusyId(notice.id);
        const action = notice.status === 'published' ? 'unpublish' : 'publish';

        try {
            await api.patch(`/teacher/college/notices/${notice.id}/${action}`);
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
            await api.delete(`/teacher/college/notices/${pending.id}`);
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
                title={t('teacher.notices_title')}
                aside={
                    <AppLink href="/teacher/notices/create">
                        <Button size="sm">
                            <PlusIcon className="h-4 w-4" />
                            {t('teacher.create_notice')}
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
                    {t('teacher.empty_notices')}
                </p>
            ) : (
                <ul className="space-y-3">
                    {list.rows.map((notice) => (
                        <li
                            key={notice.id}
                            className="rounded-card border border-hairline bg-white p-4 shadow-sm"
                        >
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div className="min-w-0">
                                    <p className="font-medium text-ink">{tx(notice.title)}</p>
                                    <p className="mt-0.5 text-xs text-ink-muted">
                                        {notice.category}
                                    </p>
                                </div>

                                <StatusBadge
                                    status={notice.status}
                                    tone={notice.status === 'published' ? 'green' : 'gray'}
                                />
                            </div>

                            <div className="mt-3 flex flex-wrap items-center gap-3 text-sm">
                                <AppLink
                                    href={`/teacher/notices/${notice.id}/edit`}
                                    className="font-medium text-brand-accent hover:underline"
                                >
                                    {t('teacher.edit_notice')}
                                </AppLink>

                                <button
                                    type="button"
                                    onClick={() => togglePublish(notice)}
                                    disabled={busyId === notice.id}
                                    className="font-medium text-ink-muted hover:text-ink disabled:opacity-50"
                                >
                                    {notice.status === 'published'
                                        ? t('teacher.unpublish')
                                        : t('teacher.publish')}
                                </button>

                                <button
                                    type="button"
                                    onClick={() => setPending(notice)}
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

TeacherNoticesIndex.layout = (page: ReactNode) => (
    <PortalLayout items={teacherPortalNav}>{page}</PortalLayout>
);
