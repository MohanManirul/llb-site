import { useEffect, useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { ChevronDownIcon, ArrowLeftIcon, Cog6ToothIcon } from '@heroicons/react/24/outline';
import { navItems, settingsItem, settingsSections, isSettingsPath, type NavItem } from '../../config/sidebarNav';

interface SidebarProps {
    open: boolean;
    onClose: () => void;
}

export default function Sidebar({ open, onClose }: SidebarProps) {
    const page = usePage();
    const { auth, portal } = page.props;
    const base = portal?.base ?? '/admin';
    const currentPath = page.url.split(/[?#]/)[0];

    function withBase(item: NavItem): NavItem {
        return {
            ...item,
            href: item.href && !item.external ? base + item.href : item.href,
            children: item.children?.map(withBase),
        };
    }

    const isSuperAdmin = auth?.user?.roles?.includes('super-admin') ?? false;
    const permissions = auth?.user?.permissions ?? [];
    const roles = auth?.user?.roles ?? [];

    function allowed(item: NavItem): boolean {
        if (item.hidden) {
            return false;
        }

        if (item.rolesExcept?.some((role) => roles.includes(role))) {
            return false;
        }

        if (item.rolesOnly) {
            return item.rolesOnly.some((role) => roles.includes(role));
        }

        return (
            !item.permission ||
            isSuperAdmin ||
            permissions.includes(item.permission)
        );
    }

    const visibleItems = navItems
        .map(withBase)
        .filter(allowed)
        .map((item) =>
            item.children
                ? { ...item, children: item.children.filter(allowed) }
                : item,
        )
        .filter((item) => !item.children || item.children.length > 0);

    const visibleSections = settingsSections
        .map(withBase)
        .filter(allowed)
        .map((section) =>
            section.children
                ? { ...section, children: section.children.filter(allowed) }
                : section,
        )
        .filter((section) => !section.children || section.children.length > 0);

    const showSettings = allowed(settingsItem) && visibleSections.length > 0;

    const firstSection = visibleSections
        .flatMap((section) => section.children ?? [section])
        .find((section) => section.href && !section.external);

    function isActive(href?: string): boolean {
        if (!href) return false;
        if (href === base + '/dashboard') {
            return currentPath === href;
        }
        return currentPath === href || currentPath.startsWith(href + '/');
    }

    function isGroupActive(item: NavItem): boolean {
        return (
            (item.children?.some((child) => isActive(child.href)) ?? false) ||
            isActive(item.href)
        );
    }

    const activeGroupLabels = [...visibleItems, ...visibleSections]
        .filter((item) => item.children && isGroupActive(item))
        .map((item) => item.label);

    const [openGroups, setOpenGroups] = useState<string[]>(activeGroupLabels);

    useEffect(() => {
        setOpenGroups((groups) => {
            const unchanged =
                groups.length === activeGroupLabels.length &&
                groups.every((label) => activeGroupLabels.includes(label));

            return unchanged ? groups : activeGroupLabels;
        });
    }, [currentPath]);

    function expandGroup(label: string) {
        setOpenGroups((groups) =>
            groups.includes(label) ? groups : [...groups, label],
        );
    }

    function toggleGroup(label: string) {
        setOpenGroups((groups) =>
            groups.includes(label)
                ? groups.filter((open) => open !== label)
                : [...groups, label],
        );
    }

    const [settingsOpen, setSettingsOpen] = useState(() =>
        isSettingsPath(currentPath, base),
    );

    useEffect(() => {
        setSettingsOpen(isSettingsPath(currentPath, base));
    }, [currentPath, base]);

    function renderItem(item: NavItem, active: boolean) {
        const ItemIcon = item.icon;
        const icon = ItemIcon ? <ItemIcon className="mr-3 h-5 w-5" /> : null;

        const className =
            'flex items-center gap-2 rounded-md px-3 py-2 text-[14px] font-semibold text-ink transition ' +
            (active ? 'bg-white shadow-sm' : 'hover:bg-white');

        if (item.external) {
            return (
                <a
                    key={item.label}
                    href={item.href}
                    onClick={onClose}
                    className={className}
                >
                    {icon}
                    <span>{item.label}</span>
                </a>
            );
        }

        return (
            <Link
                key={item.label}
                href={item.href ?? '#'}
                onClick={onClose}
                className={className}
            >
                {icon}
                <span>{item.label}</span>
            </Link>
        );
    }

    function renderSubItem(item: NavItem, active: boolean) {
        const ItemIcon = item.icon;
        const icon = ItemIcon ? <ItemIcon className="mr-3 h-5 w-5" /> : null;

        const className =
            'relative flex items-center gap-2 rounded-lg px-3 py-1.5 text-[13px] transition ' +
            (active
                ? 'bg-white font-semibold text-ink shadow-sm ' +
                  'before:absolute before:-left-[9px] before:top-0 before:h-full ' +
                  'before:w-[2px] before:rounded-full before:bg-brand-accent'
                : 'font-medium text-[#616161] hover:bg-white/70');

        if (item.external) {
            return (
                <a
                    key={item.label}
                    href={item.href}
                    onClick={onClose}
                    className={className}
                >
                    {icon}
                    <span>{item.label}</span>
                </a>
            );
        }

        return (
            <Link
                key={item.label}
                href={item.href ?? '#'}
                onClick={onClose}
                className={className}
            >
                {icon}
                <span>{item.label}</span>
            </Link>
        );
    }

    function renderGroup(item: NavItem) {
        const ItemIcon = item.icon;
        const icon = ItemIcon ? <ItemIcon className="mr-3 h-5 w-5" /> : null;
        const expanded = openGroups.includes(item.label);
        const hasActiveChild = isGroupActive(item);

        const rowClasses =
            'flex w-full items-center gap-2 rounded-md px-3 py-2 text-[14px] font-semibold text-ink transition ' +
            (hasActiveChild &&
            !item.children?.some((child) => isActive(child.href))
                ? 'bg-white shadow-sm'
                : 'hover:bg-white');

        const chevron = (
            <ChevronDownIcon
                className={
                    'h-4 w-4 transition-transform duration-200 ease-in-out ' +
                    (expanded ? 'rotate-180' : '')
                }
            />
        );

        return (
            <div
                key={item.label}
                className="rounded-md transition-colors duration-200"
            >
                {item.href ? (
                    <div className={rowClasses}>
                        <Link
                            href={item.href}
                            onClick={() => {
                                expandGroup(item.label);
                                onClose();
                            }}
                            className="flex flex-1 items-center gap-2"
                        >
                            {icon}
                            <span>{item.label}</span>
                        </Link>

                        <button
                            type="button"
                            onClick={() => toggleGroup(item.label)}
                            aria-expanded={expanded}
                            aria-label={`Toggle ${item.label} submenu`}
                            className="ml-auto rounded p-0.5 transition hover:bg-black/5 focus:outline-none focus:ring-2 focus:ring-brand-accent/30"
                        >
                            {chevron}
                        </button>
                    </div>
                ) : (
                    <button
                        type="button"
                        onClick={() => toggleGroup(item.label)}
                        aria-expanded={expanded}
                        className={rowClasses}
                    >
                        {icon}
                        <span>{item.label}</span>
                        <span className="ml-auto">{chevron}</span>
                    </button>
                )}

                <div
                    inert={!expanded}
                    className={
                        'grid transition-[grid-template-rows] duration-200 ease-in-out ' +
                        (expanded ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]')
                    }
                >
                    <div className="overflow-hidden">
                        <div className="ml-[22px] flex flex-col gap-1 border-l border-[#d4d4d4] pb-2 pl-2">
                            {item.children?.map((child) =>
                                renderSubItem(child, isActive(child.href)),
                            )}
                        </div>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <>
            {open && (
                <div
                    onClick={onClose}
                    className="fixed inset-0 z-30 bg-black/50 lg:hidden"
                />
            )}

            <aside
                className={
                    'fixed inset-y-0 left-0 z-40 flex w-[17rem] flex-col bg-sidebar text-sm text-ink ' +
                    'transition-transform duration-200 ease-in-out lg:static lg:translate-x-0 ' +
                    (open ? 'translate-x-0' : '-translate-x-full')
                }
            >
                {showSettings && settingsOpen ? (
                    <>
                        <div className="flex shrink-0 items-center gap-3 px-4 pb-2 pt-4">
                            <Link
                                href={`${base}/dashboard`}
                                onClick={() => {
                                    setSettingsOpen(false);
                                    onClose?.();
                                }}
                                title="Back to Dashboard"
                                aria-label="Back to Dashboard"
                                className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-gray-300 bg-white text-gray-500 shadow-sm transition hover:text-gray-900 active:scale-95"
                            >
                                <ArrowLeftIcon className="h-4 w-4" />
                            </Link>

                            <h2 className="truncate text-base font-semibold tracking-tight text-ink">
                                {settingsItem.label}
                            </h2>
                        </div>

                        <nav className="flex flex-1 flex-col gap-1 overflow-y-auto px-3 pt-4">
                            {visibleSections.map((section) =>
                                section.children
                                    ? renderGroup(section)
                                    : renderItem(section, isActive(section.href)),
                            )}
                        </nav>
                    </>
                ) : (
                    <>
                        <nav className="flex flex-1 flex-col gap-1 overflow-y-auto px-3 pt-4">
                            {visibleItems.map((item) =>
                                item.children
                                    ? renderGroup(item)
                                    : renderItem(item, isActive(item.href)),
                            )}
                        </nav>

                        {showSettings && (
                            <div className="shrink-0 px-3 pb-4 pt-2">
                                <Link
                                    href={firstSection?.href ?? `${base}/settings`}
                                    onClick={() => {
                                        setSettingsOpen(true);
                                        onClose?.();
                                    }}
                                    className="flex w-full items-center gap-2 rounded-md px-3 py-2 text-[14px] font-semibold text-ink transition hover:bg-white"
                                >
                                    <Cog6ToothIcon className="mr-3 h-5 w-5" />
                                    <span>{settingsItem.label}</span>
                                </Link>
                            </div>
                        )}
                    </>
                )}
            </aside>
        </>
    );
}
