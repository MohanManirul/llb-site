import { useEffect } from 'react';
import { createPortal } from 'react-dom';
import { Link, usePage } from '@inertiajs/react';
import { XMarkIcon } from '@heroicons/react/24/outline';
import useStudent from '@/hooks/useStudent';
import useTeacher from '@/hooks/useTeacher';
import useTranslation from '@/hooks/useTranslation';
import useSite from '@/hooks/useSite';
import AppLink from './AppLink';

interface MobileNavDrawerProps {
    open: boolean;
    onClose: () => void;
}

const itemClass =
    'flex min-h-14 items-center rounded-control px-4 py-4 text-base text-ink hover:bg-gray-100 active:bg-gray-200';

const primaryItemClass =
    'flex min-h-14 items-center rounded-control px-4 py-4 text-base font-medium text-ink hover:bg-gray-100 active:bg-gray-200';

const dangerItemClass =
    'flex min-h-14 items-center rounded-control px-4 py-4 text-left text-base font-medium text-red-600 hover:bg-red-50 active:bg-red-100';

const sectionClass = 'mt-5 px-4 text-xs font-semibold uppercase tracking-wide text-ink-muted';

export default function MobileNavDrawer({ open, onClose }: MobileNavDrawerProps) {
    const { t, tx } = useTranslation();
    const { student, logout, currentHref, loginHref: studentLoginHref } = useStudent();
    const { teacher, logout: teacherLogout, loginHref: teacherLoginHref } = useTeacher();
    const site = useSite();
    const programs = usePage().props.programs ?? [];

    useEffect(() => {
        if (!open) return;

        const previous = document.body.style.overflow;
        document.body.style.overflow = 'hidden';

        return () => {
            document.body.style.overflow = previous;
        };
    }, [open]);

    if (!open || typeof document === 'undefined') return null;

    return createPortal(
        <div className="fixed inset-0 z-50 lg:hidden">
            <button
                type="button"
                aria-label={t('nav.close')}
                onClick={onClose}
                className="absolute inset-0 bg-black/40"
            />

            <div className="absolute inset-y-0 left-0 flex w-max min-w-56 max-w-[92vw] flex-col bg-white shadow-xl">
                <div className="flex items-center justify-between border-b border-hairline px-4 py-3">
                    <span className="flex min-w-0 items-center gap-2 text-base font-semibold text-brand">
                        {site.logo_url && (
                            <img
                                src={site.logo_url}
                                alt=""
                                className="h-8 w-8 shrink-0 rounded-chip object-cover"
                            />
                        )}
                        <span className="truncate">{site.siteName}</span>
                    </span>
                    <button
                        type="button"
                        onClick={onClose}
                        aria-label={t('nav.close')}
                        className="-mr-1 rounded-control p-2.5 text-ink hover:bg-gray-100 active:bg-gray-200"
                    >
                        <XMarkIcon className="h-6 w-6" />
                    </button>
                </div>

                <nav className="flex-1 overflow-y-auto overscroll-contain p-3 pb-6">
                    <div className="flex flex-col gap-1">
                        <AppLink href="/" onClick={onClose} className={primaryItemClass}>
                            {t('nav.home')}
                        </AppLink>
                        <AppLink href="/suggestions" onClick={onClose} className={primaryItemClass}>
                            {t('nav.suggestions')}
                        </AppLink>
                        <AppLink href="/books" onClick={onClose} className={primaryItemClass}>
                            {t('nav.books')}
                        </AppLink>
                        <AppLink href="/notes" onClick={onClose} className={primaryItemClass}>
                            {t('nav.notes')}
                        </AppLink>
                        <AppLink href="/notices" onClick={onClose} className={primaryItemClass}>
                            {t('nav.notices')}
                        </AppLink>
                        <AppLink href="/browse" onClick={onClose} className={primaryItemClass}>
                            {t('search.label')}
                        </AppLink>
                    </div>

                    <p className={sectionClass}>{t('nav.exam_prep')}</p>

                    <div className="mt-1 flex flex-col gap-1">
                        <AppLink href="/exam-prep" onClick={onClose} className={primaryItemClass}>
                            {t('nav.exam_prep')}
                        </AppLink>
                        <AppLink href="/questions" onClick={onClose} className={itemClass}>
                            {t('nav.question_archive')}
                        </AppLink>
                        <AppLink href="/model-tests" onClick={onClose} className={itemClass}>
                            {t('nav.model_tests')}
                        </AppLink>
                        <AppLink href="/practice" onClick={onClose} className={itemClass}>
                            {t('nav.practice')}
                        </AppLink>
                    </div>

                    <p className={sectionClass}>{t('nav.account')}</p>

                    <div className="mt-1 flex flex-col gap-1">
                        {student && (
                            <>
                                <AppLink href="/account/profile" onClick={onClose} className={itemClass}>
                                    {t('nav.profile')}
                                </AppLink>
                                {student.college && (
                                    <>
                                        <AppLink href="/account/college/routine" onClick={onClose} className={itemClass}>
                                            {t('nav.college_routine')}
                                        </AppLink>
                                        <AppLink href="/account/college/notices" onClick={onClose} className={itemClass}>
                                            {t('nav.college_notices')}
                                        </AppLink>
                                        <AppLink href="/account/college/notes" onClick={onClose} className={itemClass}>
                                            {t('nav.college_notes')}
                                        </AppLink>
                                    </>
                                )}
                                <AppLink href="/account/attempts" onClick={onClose} className={itemClass}>
                                    {t('nav.my_attempts')}
                                </AppLink>
                                <button
                                    type="button"
                                    onClick={() => {
                                        onClose();
                                        logout();
                                    }}
                                    className={dangerItemClass}
                                >
                                    {t('nav.logout')}
                                </button>
                            </>
                        )}

                        {teacher && (
                            <>
                                <AppLink href="/teacher/routines" onClick={onClose} className={itemClass}>
                                    {t('teacher.nav_routines')}
                                </AppLink>
                                <AppLink href="/teacher/notices" onClick={onClose} className={itemClass}>
                                    {t('teacher.nav_notices')}
                                </AppLink>
                                <AppLink href="/teacher/notes" onClick={onClose} className={itemClass}>
                                    {t('teacher.nav_notes')}
                                </AppLink>
                                <AppLink href="/teacher/profile" onClick={onClose} className={itemClass}>
                                    {t('teacher.nav_profile')}
                                </AppLink>
                                <button
                                    type="button"
                                    onClick={() => {
                                        onClose();
                                        teacherLogout();
                                    }}
                                    className={dangerItemClass}
                                >
                                    {t('nav.logout')}
                                </button>
                            </>
                        )}

                        {!student && !teacher && (
                            <>
                                <Link
                                    href={studentLoginHref(currentHref())}
                                    onClick={onClose}
                                    className={primaryItemClass}
                                >
                                    {t('nav.student_login')}
                                </Link>
                                <Link
                                    href={teacherLoginHref(currentHref())}
                                    onClick={onClose}
                                    className={primaryItemClass}
                                >
                                    {t('nav.teacher_login')}
                                </Link>
                                <AppLink href="/account/register" onClick={onClose} className={itemClass}>
                                    {t('nav.register')}
                                </AppLink>
                            </>
                        )}
                    </div>

                    {programs.length > 0 && (
                        <>
                            <p className={sectionClass}>{t('nav.programs')}</p>

                            <div className="mt-1 flex flex-col gap-1">
                                {programs.map((program) => (
                                    <AppLink
                                        key={program.slug}
                                        href={`/programs/${program.slug}`}
                                        onClick={onClose}
                                        className={itemClass}
                                    >
                                        {tx(program.name)}
                                    </AppLink>
                                ))}
                            </div>
                        </>
                    )}
                </nav>

                {(site.email || site.phone) && (
                    <div className="border-t border-hairline p-3">
                        <div className="flex flex-col gap-1">
                            {site.email && (
                                <a
                                    href={`mailto:${site.email}`}
                                    className="flex min-h-12 items-center rounded-control px-4 py-3 text-sm text-ink-muted hover:bg-gray-100 active:bg-gray-200"
                                >
                                    {site.email}
                                </a>
                            )}
                            {site.phone && (
                                <a
                                    href={`tel:${site.phone}`}
                                    className="flex min-h-12 items-center rounded-control px-4 py-3 text-sm text-ink-muted hover:bg-gray-100 active:bg-gray-200"
                                >
                                    {site.phone}
                                </a>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </div>,
        document.body,
    );
}
