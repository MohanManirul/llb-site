import type { ReactNode } from 'react';
import { ArrowPathIcon, ExclamationTriangleIcon, FolderOpenIcon } from '@heroicons/react/24/outline';
import useTranslation from '@/hooks/useTranslation';

export function CardGrid({ children }: { children: ReactNode }) {
    return <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">{children}</div>;
}

export function SkeletonGrid({ count = 6 }: { count?: number }) {
    return (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {Array.from({ length: count }).map((_, index) => (
                <div
                    key={index}
                    className="h-36 animate-pulse rounded-card border border-hairline bg-white p-4"
                >
                    <div className="h-4 w-16 rounded bg-gray-200" />
                    <div className="mt-3 h-4 w-3/4 rounded bg-gray-200" />
                    <div className="mt-2 h-3 w-1/2 rounded bg-gray-100" />
                </div>
            ))}
        </div>
    );
}

export function PublicEmptyState({ onReset }: { onReset?: () => void }) {
    const { t } = useTranslation();

    return (
        <div className="flex flex-col items-center rounded-card border border-dashed border-hairline bg-white px-4 py-12 text-center">
            <FolderOpenIcon className="h-10 w-10 text-gray-300" />
            <p className="mt-3 font-medium text-ink">{t('browse.no_results')}</p>

            {onReset && (
                <button
                    type="button"
                    onClick={onReset}
                    className="mt-3 text-sm font-medium text-brand-accent hover:underline"
                >
                    {t('browse.reset')}
                </button>
            )}
        </div>
    );
}

export function ErrorCard({ message, onRetry }: { message: string; onRetry: () => void }) {
    const { t } = useTranslation();

    return (
        <div className="flex flex-col items-center rounded-card border border-hairline bg-white px-4 py-12 text-center">
            <ExclamationTriangleIcon className="h-10 w-10 text-amber-400" />
            <p className="mt-3 font-medium text-ink">{message}</p>
            <button
                type="button"
                onClick={onRetry}
                className="mt-3 inline-flex items-center gap-1 text-sm font-medium text-brand-accent hover:underline"
            >
                <ArrowPathIcon className="h-4 w-4" />
                {t('browse.retry')}
            </button>
        </div>
    );
}

export function LoadingBlock() {
    const { t } = useTranslation();

    return (
        <div className="flex flex-col items-center py-16 text-ink-muted">
            <ArrowPathIcon className="h-6 w-6 animate-spin" />
            <p className="mt-2 text-sm">{t('common.loading')}</p>
        </div>
    );
}
