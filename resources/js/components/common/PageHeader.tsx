import { useEffect, type ReactNode } from 'react';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';
import { usePageTitle } from './PageTitle';

interface PageHeaderProps {
    title: string;
    subtitle?: ReactNode;
    action?: ReactNode;
    backHref?: string;
    backLabel?: string;
}

export default function PageHeader({
    title,
    subtitle,
    action,
    backHref,
    backLabel = 'Go back',
}: PageHeaderProps) {
    const { setTitle } = usePageTitle();

    useEffect(() => {
        setTitle(title);

        return () => setTitle('');
    }, [title, setTitle]);

    return (
        <>
            <Head title={title} />

            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div className="flex min-w-0 items-center gap-3">
                    {backHref && (
                        <Link
                            href={backHref}
                            aria-label={backLabel}
                            title={backLabel}
                            className="inline-flex h-8.5 w-8.5 shrink-0 items-center justify-center rounded-md border border-gray-200 bg-white text-ink shadow-sm transition hover:bg-gray-50"
                        >
                            <ArrowLeftIcon className="h-4 w-4" />
                        </Link>
                    )}

                    <div className="min-w-0">
                        <h1 className="truncate text-[20px] font-bold text-ink">
                            {title}
                        </h1>
                        {subtitle && (
                            <p className="mt-0.5 flex items-center gap-1.5 text-sm text-ink-muted">
                                {subtitle}
                            </p>
                        )}
                    </div>
                </div>

                {action && (
                    <div className="flex flex-wrap items-center gap-3">
                        {action}
                    </div>
                )}
            </div>
        </>
    );
}
