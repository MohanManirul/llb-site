import { useState, type ReactNode } from 'react';
import {
    ArrowDownTrayIcon,
    DocumentTextIcon,
    EyeIcon,
} from '@heroicons/react/24/outline';
import PublicLayout from '@/components/public/PublicLayout';
import PublicPageHeader, { type Crumb } from '@/components/public/PublicPageHeader';
import MaterialCard from '@/components/public/MaterialCard';
import PdfViewerModal from '@/components/public/PdfViewerModal';
import {
    CardGrid,
    ErrorCard,
    LoadingBlock,
    PublicEmptyState,
} from '@/components/public/helpers';
import { StatusBadge, type BadgeTone } from '@/components/ui';
import usePublicResource from '@/hooks/usePublicResource';
import useMediaQuery from '@/hooks/useMediaQuery';
import useTranslation from '@/hooks/useTranslation';
import { formatBytes } from '@/lib/format';
import type { PageMeta, PublicMaterial, PublicMaterialFile } from '../../types';

interface MaterialShowProps {
    materialSlug: string;
    meta: PageMeta;
}

const TYPE_TONES: Record<string, BadgeTone> = {
    suggestion: 'indigo',
    book: 'blue',
    note: 'green',
};

function FileRow({
    file,
    index,
    total,
    onView,
}: {
    file: PublicMaterialFile;
    index: number;
    total: number;
    onView: ((file: PublicMaterialFile) => void) | null;
}) {
    const { t, tx, n, locale } = useTranslation();

    const label =
        tx(file.label) ||
        (total > 1 ? `${t('material.files')} ${n(index + 1)}` : t('material.file'));

    return (
        <div className="flex flex-wrap items-center gap-3 rounded-control border border-hairline bg-field px-3 py-3">
            <DocumentTextIcon className="h-8 w-8 shrink-0 text-brand-accent" />

            <div className="min-w-0 flex-1">
                <p className="truncate text-sm font-medium text-ink">{label}</p>
                <p className="text-xs text-ink-muted">
                    {formatBytes(file.size, locale)}
                    {file.page_count ? ` · ${t('material.pages', { count: n(file.page_count) })}` : ''}
                    {' · '}
                    {t('material.downloads', { count: n(file.download_count) })}
                </p>
            </div>

            <div className="flex shrink-0 items-center gap-2">
                {onView ? (
                    <button
                        type="button"
                        onClick={() => onView(file)}
                        className="inline-flex items-center gap-1.5 rounded-control bg-brand px-3.5 py-2 text-sm font-semibold text-white hover:bg-brand-accent"
                    >
                        <EyeIcon className="h-4 w-4" />
                        {t('material.view')}
                    </button>
                ) : (
                    <a
                        href={file.preview_url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="inline-flex items-center gap-1.5 rounded-control bg-brand px-3.5 py-2 text-sm font-semibold text-white hover:bg-brand-accent"
                    >
                        <EyeIcon className="h-4 w-4" />
                        {t('material.view')}
                    </a>
                )}

                <a
                    href={file.download_url}
                    className="inline-flex items-center gap-1.5 rounded-control border border-hairline bg-white px-3 py-2 text-sm font-medium text-ink hover:border-brand-muted"
                >
                    <ArrowDownTrayIcon className="h-4 w-4" />
                    {t('material.download', { size: formatBytes(file.size, locale) })}
                </a>
            </div>
        </div>
    );
}

export default function MaterialShow({ materialSlug, meta }: MaterialShowProps) {
    const { t, tx, n, d, isBn } = useTranslation();
    const isDesktop = useMediaQuery('(min-width: 1024px)');
    const [viewing, setViewing] = useState<PublicMaterialFile | null>(null);

    const { data: material, extra, loading, error, notFound, refetch } =
        usePublicResource<PublicMaterial>(`/public/materials/${materialSlug}`);

    const related = (extra.related as PublicMaterial[] | undefined) ?? [];

    const metaTitle = (isBn ? meta.title_bn : (meta.title_en ?? meta.title_bn)) || meta.title_bn;
    const metaDescription = isBn
        ? (meta.description_bn ?? meta.description_en)
        : (meta.description_en ?? meta.description_bn);

    if (loading) {
        return (
            <>
                <PublicPageHeader title={metaTitle} description={metaDescription} />
                <LoadingBlock />
            </>
        );
    }

    if (notFound || (!material && !error)) {
        return (
            <>
                <PublicPageHeader title={metaTitle} />
                <PublicEmptyState />
            </>
        );
    }

    if (error || !material) {
        return (
            <>
                <PublicPageHeader title={metaTitle} />
                <ErrorCard message={error ?? t('browse.load_error')} onRetry={refetch} />
            </>
        );
    }

    const typeLabel =
        material.type === 'suggestion'
            ? t('type.suggestion')
            : material.type === 'book'
              ? t('type.book')
              : t('type.note');

    const files = material.files ?? [];

    const details: Array<{ label: string; value: string }> = [
        material.subject ? { label: t('material.subject'), value: tx(material.subject.name) } : null,
        material.session ? { label: t('material.session'), value: material.session.label } : null,
        material.exam_year ? { label: t('material.exam_year'), value: n(material.exam_year) } : null,
        material.author ? { label: t('material.author'), value: material.author } : null,
        material.publisher ? { label: t('material.publisher'), value: material.publisher } : null,
        material.edition ? { label: t('material.edition'), value: material.edition } : null,
        material.published_at ? { label: t('material.published'), value: d(material.published_at) } : null,
    ].filter((entry): entry is { label: string; value: string } => entry !== null);

    return (
        <>
            <PublicPageHeader
                title={tx(material.title)}
                metaTitle={metaTitle}
                description={metaDescription}
                crumbs={([
                    material.subject?.program
                        ? {
                              label: tx(material.subject.program.name),
                              href: `/programs/${material.subject.program.slug}`,
                          }
                        : null,
                    material.subject
                        ? {
                              label: tx(material.subject.name),
                              href: `/subjects/${material.subject.slug}`,
                          }
                        : null,
                    { label: tx(material.title) },
                ] as Array<Crumb | null>).filter((crumb): crumb is Crumb => crumb !== null)}
                aside={<StatusBadge status={typeLabel} tone={TYPE_TONES[material.type] ?? 'gray'} />}
            />

            <div className="grid gap-6 lg:grid-cols-[1fr_300px]">
                <div className="min-w-0">
                    {tx(material.description) && (
                        <p className="mb-4 max-w-2xl text-sm leading-relaxed text-ink-soft">
                            {tx(material.description)}
                        </p>
                    )}

                    <div className="space-y-3">
                        {files.map((file, index) => (
                            <FileRow
                                key={file.id}
                                file={file}
                                index={index}
                                total={files.length}
                                onView={isDesktop ? setViewing : null}
                            />
                        ))}
                    </div>

                    <p className="mt-3 text-xs text-ink-muted">{t('material.view_hint')}</p>
                </div>

                <aside>
                    <div className="rounded-card border border-hairline bg-white p-4 shadow-sm">
                        <dl className="space-y-2.5 text-sm">
                            {details.map((entry) => (
                                <div key={entry.label} className="flex justify-between gap-3">
                                    <dt className="shrink-0 text-ink-muted">{entry.label}</dt>
                                    <dd className="text-right font-medium text-ink">{entry.value}</dd>
                                </div>
                            ))}
                        </dl>

                        <p className="mt-4 border-t border-hairline pt-3 text-xs text-ink-muted">
                            {t('material.views', { count: n(material.view_count) })} ·{' '}
                            {t('material.downloads', { count: n(material.download_count) })}
                        </p>
                    </div>
                </aside>
            </div>

            {related.length > 0 && (
                <section className="mt-10">
                    <h2 className="text-lg font-semibold text-ink">{t('material.related')}</h2>
                    <div className="mt-3">
                        <CardGrid>
                            {related.map((item) => (
                                <MaterialCard key={item.id} material={item} />
                            ))}
                        </CardGrid>
                    </div>
                </section>
            )}

            <PdfViewerModal
                file={viewing}
                title={tx(material.title)}
                onClose={() => setViewing(null)}
            />
        </>
    );
}

MaterialShow.layout = (page: ReactNode) => <PublicLayout>{page}</PublicLayout>;
