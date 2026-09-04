import type { ReactNode } from 'react';
import { Head } from '@inertiajs/react';
import { ScaleIcon } from '@heroicons/react/24/outline';

interface AuthCardProps {
    title: string;
    subtitle?: ReactNode;
    children: ReactNode;
    footer?: ReactNode;
}

export default function AuthCard({ title, subtitle, children, footer }: AuthCardProps) {
    return (
        <div className="mx-auto w-full max-w-md py-6">
            <Head title={title} />

            <div className="rounded-card border border-hairline bg-white shadow-sm">
                <div className="h-1 rounded-t-card bg-linear-to-r from-brand via-brass to-banyan" />
                <div className="p-6 md:p-8">
                    <span className="flex h-10 w-10 items-center justify-center rounded-chip bg-brass-soft">
                        <ScaleIcon className="h-5 w-5 text-brass-deep" />
                    </span>
                    <h1 className="mt-4 text-xl font-bold text-ink">{title}</h1>
                    {subtitle && <p className="mt-1 text-sm text-ink-muted">{subtitle}</p>}

                    <div className="mt-6">{children}</div>
                </div>

                {footer && (
                    <div className="border-t border-hairline px-6 py-4 text-center text-sm text-ink-muted md:px-8">
                        {footer}
                    </div>
                )}
            </div>
        </div>
    );
}
