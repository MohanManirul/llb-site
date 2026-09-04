import { FormEvent, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import {
    Bars3Icon,
    BuildingLibraryIcon,
    ChevronDownIcon,
    MagnifyingGlassIcon,
    UserCircleIcon,
} from '@heroicons/react/24/outline';
import { Popover } from '@/components/ui';
import useStudent from '@/hooks/useStudent';
import useTranslation from '@/hooks/useTranslation';
import { SITE_NAME_BN, SITE_NAME } from '@/config/site';
import AppLink from './AppLink';
import LanguageToggle from './LanguageToggle';
import MobileNavDrawer from './MobileNavDrawer';

export default function PublicHeader() {
    const { t, tx, isBn, localeHref } = useTranslation();
    const programs = usePage().props.programs ?? [];
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [search, setSearch] = useState('');

    const submitSearch = (event: FormEvent) => {
        event.preventDefault();

        const query = search.trim();

        router.visit(localeHref(`/browse${query ? `?search=${encodeURIComponent(query)}` : ''}`));
    };

    return (
        <header className="sticky top-0 z-30 border-b border-hairline bg-white/95 backdrop-blur">
            <div className="h-1 bg-linear-to-r from-brand via-brass to-banyan" />
            <div className="mx-auto flex w-full max-w-300 items-center gap-3 px-4 py-3 md:px-6">
                <button
                    type="button"
                    onClick={() => setDrawerOpen(true)}
                    aria-label={t('nav.menu')}
                    className="rounded-control p-1.5 text-ink hover:bg-gray-100 lg:hidden"
                >
                    <Bars3Icon className="h-6 w-6" />
                </button>

                <AppLink href="/" className="flex shrink-0 items-center gap-2">
                    <img src="/llb.jpg" alt="" className="h-8 w-8 rounded-chip object-cover" />
                    <span className="text-lg font-semibold text-brand">
                        {isBn ? SITE_NAME_BN : SITE_NAME}
                    </span>
                </AppLink>

                <nav className="ml-4 hidden items-center gap-1 lg:flex">
                    <AppLink
                        href="/suggestions"
                        className="rounded-control px-3 py-1.5 text-sm font-medium text-ink hover:bg-gray-100"
                    >
                        {t('nav.suggestions')}
                    </AppLink>
                    <AppLink
                        href="/books"
                        className="rounded-control px-3 py-1.5 text-sm font-medium text-ink hover:bg-gray-100"
                    >
                        {t('nav.books')}
                    </AppLink>
                    <AppLink
                        href="/notes"
                        className="rounded-control px-3 py-1.5 text-sm font-medium text-ink hover:bg-gray-100"
                    >
                        {t('nav.notes')}
                    </AppLink>

                    <AppLink
                        href="/notices"
                        className="rounded-control px-3 py-1.5 text-sm font-medium text-ink hover:bg-gray-100"
                    >
                        {t('nav.notices')}
                    </AppLink>

                    <AppLink
                        href="/exam-prep"
                        className="rounded-control px-3 py-1.5 text-sm font-medium text-brand-accent hover:bg-gray-100"
                    >
                        {t('nav.exam_prep')}
                    </AppLink>

                    <Popover
                        label={t('nav.programs')}
                        icon={<BuildingLibraryIcon className="h-4 w-4" />}
                        panelClassName="w-72 p-2"
                    >
                        {(close) => (
                            <div className="flex flex-col">
                                {programs.map((program) => (
                                    <AppLink
                                        key={program.slug}
                                        href={`/programs/${program.slug}`}
                                        onClick={() => close()}
                                        className="rounded-control px-3 py-2 text-sm text-ink hover:bg-gray-100"
                                    >
                                        {tx(program.name)}
                                    </AppLink>
                                ))}
                            </div>
                        )}
                    </Popover>
                </nav>

                <form onSubmit={submitSearch} className="ml-auto hidden min-w-0 flex-1 max-w-xs sm:block">
                    <label className="relative block">
                        <span className="sr-only">{t('search.label')}</span>
                        <MagnifyingGlassIcon className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <input
                            type="search"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder={t('search.placeholder')}
                            className="w-full rounded-control border border-hairline bg-field py-2 pl-9 pr-3 text-sm text-ink placeholder:text-gray-400 focus:border-brand-accent focus:outline-none"
                        />
                    </label>
                </form>

                <AppLink
                    href="/browse"
                    aria-label={t('search.label')}
                    className="ml-auto rounded-control p-2 text-ink hover:bg-gray-100 sm:hidden"
                >
                    <MagnifyingGlassIcon className="h-5 w-5" />
                </AppLink>

                <LanguageToggle />

                <AccountMenu />
            </div>

            <MobileNavDrawer open={drawerOpen} onClose={() => setDrawerOpen(false)} />
        </header>
    );
}

function AccountMenu() {
    const { t } = useTranslation();
    const { student, loginHref, currentHref, logout } = useStudent();
    const [loggingOut, setLoggingOut] = useState(false);

    if (!student) {
        return (
            <Link
                href={loginHref(currentHref())}
                className="hidden items-center gap-1.5 rounded-control border border-hairline px-3 py-1.5 text-sm font-medium text-ink hover:bg-gray-100 sm:inline-flex"
            >
                <UserCircleIcon className="h-4 w-4" />
                {t('nav.login')}
            </Link>
        );
    }

    const signOut = async () => {
        setLoggingOut(true);

        try {
            await logout();
        } finally {
            setLoggingOut(false);
        }
    };

    return (
        <div className="hidden sm:block">
            <Popover
                label={student.name.split(' ')[0]}
                icon={<UserCircleIcon className="h-4 w-4" />}
                panelClassName="w-56 p-2"
            >
                {(close) => (
                    <div className="flex flex-col">
                        <AppLink
                            href="/account/profile"
                            onClick={() => close()}
                            className="rounded-control px-3 py-2 text-sm text-ink hover:bg-gray-100"
                        >
                            {t('nav.profile')}
                        </AppLink>
                        <AppLink
                            href="/account/attempts"
                            onClick={() => close()}
                            className="rounded-control px-3 py-2 text-sm text-ink hover:bg-gray-100"
                        >
                            {t('nav.my_attempts')}
                        </AppLink>
                        <AppLink
                            href="/practice"
                            onClick={() => close()}
                            className="rounded-control px-3 py-2 text-sm text-ink hover:bg-gray-100"
                        >
                            {t('nav.practice')}
                        </AppLink>
                        <button
                            type="button"
                            onClick={signOut}
                            disabled={loggingOut}
                            className="rounded-control px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50 disabled:opacity-50"
                        >
                            {t('nav.logout')}
                        </button>
                    </div>
                )}
            </Popover>
        </div>
    );
}

export function ProgramSwitcherIcon() {
    return <ChevronDownIcon className="h-4 w-4" />;
}
