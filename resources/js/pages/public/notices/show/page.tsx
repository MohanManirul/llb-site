import type { ReactNode } from 'react';
import { ArrowDownTrayIcon, MapPinIcon } from '@heroicons/react/24/outline';
import PublicLayout from '@/components/public/PublicLayout';
import PublicPageHeader from '@/components/public/PublicPageHeader';
import {
    ErrorCard,
    LoadingBlock,
    PublicEmptyState,
} from '@/components/public/helpers';
import usePublicResource from '@/hooks/usePublicResource';
import useTranslation from '@/hooks/useTranslation';
import { formatBytes } from '@/lib/format';
import type { TranslatedField } from '@/lib/i18n';
import type { PageMeta } from '../../types';

interface PublicNoticeDetail {
    id: number;
    slug: string;
    title: TranslatedField;
    body: TranslatedField;
    category: string;
    is_pinned: boolean;
    published_at: string | null;
    has_attachment: boolean;
    attachment_name: string | null;
    attachment_size: number | null;
    attachment_url: string | null;
    program?: { slug: string; name: TranslatedField } | null;
    session?: { slug: string; label: string } | null;
    subject?: { slug: string; name: TranslatedField } | null;
}

interface NoticeShowProps {
    noticeSlug: string;
    meta: PageMeta;
}

export default function NoticeShow({ noticeSlug, meta }: NoticeShowProps) {
    const { t, tx, d, isBn, locale } = useTranslation();

    const { data: notice, loading, error, notFound, refetch } =
        usePublicResource<PublicNoticeDetail>(`/public/notices/${noticeSlug}`);

    const metaTitle = (isBn ? meta.title_bn : (meta.title_en ?? meta.title_bn)) || meta.title_bn;

    if (loading) {
        return (
            <>
                <PublicPageHeader title={metaTitle} />
                <LoadingBlock />
            </>
        );
    }

    if (notFound || (!notice && !error)) {
        return (
            <>
                <PublicPageHeader title={metaTitle} />
                <PublicEmptyState />
            </>
        );
    }

    if (error || !notice) {
        return (
            <>
                <PublicPageHeader title={metaTitle} />
                <ErrorCard message={error ?? t('browse.load_error')} onRetry={refetch} />
            </>
        );
    }

    return (
        <>
            <PublicPageHeader
                title={tx(notice.title)}
                metaTitle={metaTitle}
                crumbs={[{ label: t('nav.notices'), href: '/notices' }, { label: tx(notice.title) }]}
            >
                <p className="mt-1 flex flex-wrap items-center gap-2 text-sm text-ink-muted">
                    {notice.is_pinned && (
                        <span className="inline-flex items-center gap-1 rounded-chip bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">
                            <MapPinIcon className="h-3 w-3" />
                            {t('notice.pinned')}
                        </span>
                    )}
                    <span>{d(notice.published_at)}</span>
                    {notice.program && <span>· {tx(notice.program.name)}</span>}
                    {notice.session && <span>· {notice.session.label}</span>}
                    {notice.subject && <span>· {tx(notice.subject.name)}</span>}
                </p>
            </PublicPageHeader>

            <article className="max-w-3xl rounded-card border border-hairline bg-white p-5 shadow-sm md:p-7">
                <div className="whitespace-pre-line text-[15px] leading-relaxed text-ink">
                    {tx(notice.body)}
                </div>

                {notice.has_attachment && notice.attachment_url && (
                    <a
                        href={notice.attachment_url}
                        className="mt-6 inline-flex items-center gap-2 rounded-control bg-brand px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-accent"
                    >
                        <ArrowDownTrayIcon className="h-4 w-4" />
                        {t('notice.attachment', {
                            size: formatBytes(notice.attachment_size, locale),
                        })}
                    </a>
                )}
            </article>
        </>
    );
}

NoticeShow.layout = (page: ReactNode) => <PublicLayout>{page}</PublicLayout>;
