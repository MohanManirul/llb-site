import { useEffect } from 'react';
import {
    ArrowDownTrayIcon,
    ArrowTopRightOnSquareIcon,
    XMarkIcon,
} from '@heroicons/react/24/outline';
import useTranslation from '@/hooks/useTranslation';
import { formatBytes } from '@/lib/format';
import type { PublicMaterialFile } from '@/pages/public/types';

interface PdfViewerModalProps {
    file: PublicMaterialFile | null;
    title: string;
    onClose: () => void;
}

export default function PdfViewerModal({ file, title, onClose }: PdfViewerModalProps) {
    const { t, locale } = useTranslation();

    useEffect(() => {
        if (!file) return;

        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') onClose();
        };

        document.addEventListener('keydown', onKeyDown);
        document.body.style.overflow = 'hidden';

        return () => {
            document.removeEventListener('keydown', onKeyDown);
            document.body.style.overflow = '';
        };
    }, [file, onClose]);

    if (!file) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-4">
            <button
                type="button"
                aria-label={t('nav.close')}
                onClick={onClose}
                className="absolute inset-0 bg-black/60"
            />

            <div className="relative flex h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-card bg-white shadow-xl">
                <div className="flex items-center gap-2 border-b border-hairline px-3 py-2.5 sm:px-4">
                    <p className="min-w-0 flex-1 truncate text-sm font-semibold text-ink">
                        {title}
                    </p>

                    <a
                        href={file.download_url}
                        className="inline-flex shrink-0 items-center gap-1.5 rounded-control bg-brand px-3 py-2 text-sm font-semibold text-white hover:bg-brand-accent"
                    >
                        <ArrowDownTrayIcon className="h-4 w-4" />
                        <span className="hidden sm:inline">
                            {t('material.download', { size: formatBytes(file.size, locale) })}
                        </span>
                        <span className="sm:hidden">{t('material.download_plain')}</span>
                    </a>

                    <a
                        href={file.preview_url}
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label={t('material.open_new_tab')}
                        title={t('material.open_new_tab')}
                        className="shrink-0 rounded-control p-2 text-ink-muted hover:bg-gray-100 hover:text-ink"
                    >
                        <ArrowTopRightOnSquareIcon className="h-5 w-5" />
                    </a>

                    <button
                        type="button"
                        onClick={onClose}
                        aria-label={t('nav.close')}
                        className="shrink-0 rounded-control p-2 text-ink-muted hover:bg-gray-100 hover:text-ink"
                    >
                        <XMarkIcon className="h-5 w-5" />
                    </button>
                </div>

                <iframe
                    src={`${file.preview_url}#view=FitH`}
                    title={title}
                    className="h-full w-full flex-1 bg-gray-100"
                />
            </div>
        </div>
    );
}
