import { ArrowDownTrayIcon, DocumentTextIcon } from '@heroicons/react/24/outline';
import { StatusBadge, type BadgeTone } from '@/components/ui';
import useTranslation from '@/hooks/useTranslation';
import type { PublicMaterial } from '@/pages/public/types';
import AppLink from './AppLink';

const TYPE_TONES: Record<string, BadgeTone> = {
    suggestion: 'indigo',
    book: 'blue',
    note: 'green',
};

interface MaterialCardProps {
    material: PublicMaterial;
}

export default function MaterialCard({ material }: MaterialCardProps) {
    const { t, tx, n } = useTranslation();

    const typeLabel =
        material.type === 'suggestion'
            ? t('type.suggestion')
            : material.type === 'book'
              ? t('type.book')
              : t('type.note');

    return (
        <AppLink
            href={`/materials/${material.slug}`}
            className="group flex flex-col rounded-card border border-hairline bg-white p-4 shadow-sm transition hover:border-brand-muted hover:shadow"
        >
            <div className="flex items-start gap-3">
                {material.cover_thumbnail_url ? (
                    <img
                        src={material.cover_thumbnail_url}
                        alt=""
                        className="h-14 w-11 shrink-0 rounded-chip border border-hairline object-cover"
                    />
                ) : (
                    <span className="flex h-14 w-11 shrink-0 items-center justify-center rounded-chip bg-brand/5">
                        <DocumentTextIcon className="h-6 w-6 text-brand" />
                    </span>
                )}

                <div className="min-w-0">
                    <StatusBadge status={typeLabel} tone={TYPE_TONES[material.type] ?? 'gray'} />
                    <h3 className="mt-1.5 line-clamp-2 font-semibold text-ink group-hover:text-brand">
                        {tx(material.title)}
                    </h3>
                </div>
            </div>

            <p className="mt-2 line-clamp-1 text-xs text-ink-muted">
                {material.subject ? tx(material.subject.name) : ''}
                {material.subject?.level ? ` · ${tx(material.subject.level.name)}` : ''}
                {material.session ? ` · ${material.session.label}` : ''}
                {material.exam_year ? ` · ${n(material.exam_year)}` : ''}
            </p>

            <p className="mt-auto flex items-center gap-1 pt-3 text-xs text-ink-muted">
                <ArrowDownTrayIcon className="h-3.5 w-3.5" />
                {t('material.downloads', { count: n(material.download_count) })}
            </p>
        </AppLink>
    );
}
