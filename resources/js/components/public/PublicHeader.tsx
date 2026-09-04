import { FormEvent, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import {
    AcademicCapIcon,
    Bars3Icon,
    ChevronDownIcon,
    MagnifyingGlassIcon,
} from '@heroicons/react/24/outline';
import { Popover } from '@/components/ui';
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

                    <Popover
                        label={t('nav.programs')}
                        icon={<AcademicCapIcon className="h-4 w-4" />}
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
            </div>

            <MobileNavDrawer open={drawerOpen} onClose={() => setDrawerOpen(false)} />
        </header>
    );
}

export function ProgramSwitcherIcon() {
    return <ChevronDownIcon className="h-4 w-4" />;
}
