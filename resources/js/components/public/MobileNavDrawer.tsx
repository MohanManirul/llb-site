import { Link, usePage } from '@inertiajs/react';
import { XMarkIcon } from '@heroicons/react/24/outline';
import useStudent from '@/hooks/useStudent';
import useTranslation from '@/hooks/useTranslation';
import AppLink from './AppLink';

interface MobileNavDrawerProps {
    open: boolean;
    onClose: () => void;
}

export default function MobileNavDrawer({ open, onClose }: MobileNavDrawerProps) {
    const { t, tx } = useTranslation();
    const { student, loginHref, currentHref, logout } = useStudent();
    const programs = usePage().props.programs ?? [];

    if (!open) return null;

    return (
        <div className="fixed inset-0 z-40 lg:hidden">
            <button
                type="button"
                aria-label={t('nav.close')}
                onClick={onClose}
                className="absolute inset-0 bg-black/40"
            />

            <div className="absolute inset-y-0 left-0 flex w-72 max-w-[85vw] flex-col bg-white shadow-xl">
                <div className="flex items-center justify-between border-b border-hairline px-4 py-3">
                    <span className="font-semibold text-brand">{t('nav.menu')}</span>
                    <button
                        type="button"
                        onClick={onClose}
                        aria-label={t('nav.close')}
                        className="rounded-control p-1.5 text-ink hover:bg-gray-100"
                    >
                        <XMarkIcon className="h-5 w-5" />
                    </button>
                </div>

                <nav className="flex-1 overflow-y-auto p-3">
                    <div className="flex flex-col gap-0.5">
                        <AppLink href="/" onClick={onClose} className="rounded-control px-3 py-2 text-sm font-medium text-ink hover:bg-gray-100">
                            {t('nav.home')}
                        </AppLink>
                        <AppLink href="/suggestions" onClick={onClose} className="rounded-control px-3 py-2 text-sm font-medium text-ink hover:bg-gray-100">
                            {t('nav.suggestions')}
                        </AppLink>
                        <AppLink href="/books" onClick={onClose} className="rounded-control px-3 py-2 text-sm font-medium text-ink hover:bg-gray-100">
                            {t('nav.books')}
                        </AppLink>
                        <AppLink href="/notes" onClick={onClose} className="rounded-control px-3 py-2 text-sm font-medium text-ink hover:bg-gray-100">
                            {t('nav.notes')}
                        </AppLink>
                        <AppLink href="/notices" onClick={onClose} className="rounded-control px-3 py-2 text-sm font-medium text-ink hover:bg-gray-100">
                            {t('nav.notices')}
                        </AppLink>
                    </div>

                    <p className="mt-4 px-3 text-xs font-semibold uppercase tracking-wide text-ink-muted">
                        {t('nav.exam_prep')}
                    </p>

                    <div className="mt-1 flex flex-col gap-0.5">
                        <AppLink href="/exam-prep" onClick={onClose} className="rounded-control px-3 py-2 text-sm font-medium text-ink hover:bg-gray-100">
                            {t('nav.exam_prep')}
                        </AppLink>
                        <AppLink href="/questions" onClick={onClose} className="rounded-control px-3 py-2 text-sm text-ink hover:bg-gray-100">
                            {t('nav.question_archive')}
                        </AppLink>
                        <AppLink href="/model-tests" onClick={onClose} className="rounded-control px-3 py-2 text-sm text-ink hover:bg-gray-100">
                            {t('nav.model_tests')}
                        </AppLink>
                        <AppLink href="/practice" onClick={onClose} className="rounded-control px-3 py-2 text-sm text-ink hover:bg-gray-100">
                            {t('nav.practice')}
                        </AppLink>
                    </div>

                    <p className="mt-4 px-3 text-xs font-semibold uppercase tracking-wide text-ink-muted">
                        {t('nav.account')}
                    </p>

                    <div className="mt-1 flex flex-col gap-0.5">
                        {student ? (
                            <>
                                <AppLink href="/account/profile" onClick={onClose} className="rounded-control px-3 py-2 text-sm text-ink hover:bg-gray-100">
                                    {t('nav.profile')}
                                </AppLink>
                                <AppLink href="/account/attempts" onClick={onClose} className="rounded-control px-3 py-2 text-sm text-ink hover:bg-gray-100">
                                    {t('nav.my_attempts')}
                                </AppLink>
                                <button
                                    type="button"
                                    onClick={() => {
                                        onClose();
                                        logout();
                                    }}
                                    className="rounded-control px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50"
                                >
                                    {t('nav.logout')}
                                </button>
                            </>
                        ) : (
                            <>
                                <Link href={loginHref(currentHref())} onClick={onClose} className="rounded-control px-3 py-2 text-sm text-ink hover:bg-gray-100">
                                    {t('nav.login')}
                                </Link>
                                <AppLink href="/account/register" onClick={onClose} className="rounded-control px-3 py-2 text-sm text-ink hover:bg-gray-100">
                                    {t('nav.register')}
                                </AppLink>
                            </>
                        )}
                    </div>

                    <p className="mt-4 px-3 text-xs font-semibold uppercase tracking-wide text-ink-muted">
                        {t('nav.programs')}
                    </p>

                    <div className="mt-1 flex flex-col gap-0.5">
                        {programs.map((program) => (
                            <AppLink
                                key={program.slug}
                                href={`/programs/${program.slug}`}
                                onClick={onClose}
                                className="rounded-control px-3 py-2 text-sm text-ink hover:bg-gray-100"
                            >
                                {tx(program.name)}
                            </AppLink>
                        ))}
                    </div>
                </nav>
            </div>
        </div>
    );
}
