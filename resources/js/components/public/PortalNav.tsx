import { usePage } from '@inertiajs/react';
import AppLink from './AppLink';
import useTranslation from '@/hooks/useTranslation';
import type { PortalNavItem } from '@/config/portalNav';

interface PortalNavProps {
    items: PortalNavItem[];
}

export default function PortalNav({ items }: PortalNavProps) {
    const { t } = useTranslation();
    const url = usePage().url;
    const path = url.replace(/^\/(bn|en)/, '') || '/';

    const isActive = (href: string) => path === href || path.startsWith(`${href}/`);

    return (
        <nav className="lg:sticky lg:top-6">
            <ul className="-mx-1 flex gap-2 overflow-x-auto px-1 pb-2 lg:mx-0 lg:flex-col lg:gap-1 lg:overflow-visible lg:px-0 lg:pb-0">
                {items.map((item) => {
                    const Icon = item.icon;
                    const active = isActive(item.href);

                    return (
                        <li key={item.href} className="shrink-0 lg:shrink">
                            <AppLink
                                href={item.href}
                                className={
                                    'flex items-center gap-2 rounded-full px-3 py-2 text-sm whitespace-nowrap transition lg:rounded-card lg:w-full ' +
                                    (active
                                        ? 'bg-brand-accent text-white'
                                        : 'border border-hairline bg-white text-ink-muted hover:text-ink lg:border-transparent lg:bg-transparent')
                                }
                            >
                                <Icon className="h-4 w-4 shrink-0" />
                                {t(item.labelKey)}
                            </AppLink>
                        </li>
                    );
                })}
            </ul>
        </nav>
    );
}
