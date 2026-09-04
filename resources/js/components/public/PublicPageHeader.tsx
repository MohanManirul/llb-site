import type { ReactNode } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { ChevronRightIcon } from '@heroicons/react/24/outline';
import useTranslation from '@/hooks/useTranslation';
import AppLink from './AppLink';

export interface Crumb {
    label: string;
    href?: string;
}

interface PublicPageHeaderProps {
    title: string;
    metaTitle?: string;
    description?: string | null;
    crumbs?: Crumb[];
    aside?: ReactNode;
    children?: ReactNode;
}

export default function PublicPageHeader({
    title,
    metaTitle,
    description,
    crumbs = [],
    aside,
    children,
}: PublicPageHeaderProps) {
    const { t } = useTranslation();
    const pageUrl = usePage().url;

    const [path] = pageUrl.split('?');
    const bare = path.replace(/^\/(bn|en)(?=\/|$)/, '') || '/';

    return (
        <div className="mb-6">
            <Head title={metaTitle ?? title}>
                {description ? <meta name="description" content={description} /> : null}
                <link rel="alternate" hrefLang="bn" href={`/bn${bare === '/' ? '' : bare}`} />
                <link rel="alternate" hrefLang="en" href={`/en${bare === '/' ? '' : bare}`} />
                <link rel="alternate" hrefLang="x-default" href={`/bn${bare === '/' ? '' : bare}`} />
            </Head>

            {crumbs.length > 0 && (
                <nav className="mb-2 flex flex-wrap items-center gap-1 text-xs text-ink-muted">
                    <AppLink href="/" className="hover:text-brand">
                        {t('nav.home')}
                    </AppLink>

                    {crumbs.map((crumb, index) => (
                        <span key={index} className="flex items-center gap-1">
                            <ChevronRightIcon className="h-3 w-3" />
                            {crumb.href ? (
                                <AppLink href={crumb.href} className="hover:text-brand">
                                    {crumb.label}
                                </AppLink>
                            ) : (
                                <span className="text-ink">{crumb.label}</span>
                            )}
                        </span>
                    ))}
                </nav>
            )}

            <div className="flex flex-wrap items-start justify-between gap-3">
                <h1 className="text-2xl font-bold text-ink md:text-3xl">{title}</h1>
                {aside}
            </div>

            {children}
        </div>
    );
}
