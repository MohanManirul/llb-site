import { useMemo, useState, type ReactNode } from 'react';
import PublicLayout from '@/components/public/PublicLayout';
import PublicPageHeader, { type Crumb } from '@/components/public/PublicPageHeader';
import MaterialCard from '@/components/public/MaterialCard';
import {
    CardGrid,
    ErrorCard,
    LoadingBlock,
    PublicEmptyState,
    SkeletonGrid,
} from '@/components/public/helpers';
import { Pagination } from '@/components/ui';
import usePublicList from '@/hooks/usePublicList';
import usePublicResource from '@/hooks/usePublicResource';
import useTranslation from '@/hooks/useTranslation';
import type { PageMeta, PublicMaterial, PublicSubject } from '../../types';

interface SubjectShowProps {
    subjectSlug: string;
    meta: PageMeta;
}

export default function SubjectShow({ subjectSlug, meta }: SubjectShowProps) {
    const { t, tx, n, isBn } = useTranslation();
    const [type, setType] = useState('');
    const [page, setPage] = useState(1);

    const subject = usePublicResource<PublicSubject>(`/public/subjects/${subjectSlug}`);

    const listParams = useMemo(
        () => ({
            subject: subjectSlug,
            type: type || undefined,
            page: page > 1 ? page : undefined,
            per_page: 12,
        }),
        [subjectSlug, type, page],
    );

    const materials = usePublicList<PublicMaterial>({
        url: '/public/materials',
        params: listParams,
        errorMessage: t('browse.load_error'),
    });

    const metaTitle = (isBn ? meta.title_bn : (meta.title_en ?? meta.title_bn)) || meta.title_bn;
    const metaDescription = isBn
        ? (meta.description_bn ?? meta.description_en)
        : (meta.description_en ?? meta.description_bn);

    if (subject.loading) {
        return (
            <>
                <PublicPageHeader title={metaTitle} description={metaDescription} />
                <LoadingBlock />
            </>
        );
    }

    if (subject.error || !subject.data) {
        return (
            <>
                <PublicPageHeader title={metaTitle} />
                <ErrorCard message={subject.error ?? t('browse.load_error')} onRetry={subject.refetch} />
            </>
        );
    }

    const detail = subject.data;

    const typeTabs = [
        { value: '', label: t('browse.all') },
        { value: 'suggestion', label: t('type.suggestion') },
        { value: 'book', label: t('type.book') },
        { value: 'note', label: t('type.note') },
    ];

    const chipClass = (active: boolean) =>
        'shrink-0 rounded-chip border px-3 py-1.5 text-sm font-medium transition ' +
        (active
            ? 'border-brand bg-brand text-white'
            : 'border-hairline bg-white text-ink hover:border-brand-muted');

    return (
        <>
            <PublicPageHeader
                title={tx(detail.name)}
                metaTitle={metaTitle}
                description={metaDescription}
                crumbs={([
                    detail.program
                        ? { label: tx(detail.program.name), href: `/programs/${detail.program.slug}` }
                        : null,
                    { label: tx(detail.name) },
                ] as Array<Crumb | null>).filter((crumb): crumb is Crumb => crumb !== null)}
            >
                <p className="mt-1 text-sm text-ink-muted">
                    {detail.code ? t('subject.code', { code: detail.code }) : ''}
                    {detail.code && detail.marks ? ' · ' : ''}
                    {detail.marks ? t('subject.marks', { marks: n(detail.marks) }) : ''}
                    {detail.level ? ` · ${tx(detail.level.name)}` : ''}
                </p>
            </PublicPageHeader>

            {tx(detail.description) && (
                <p className="mb-5 max-w-2xl text-sm leading-relaxed text-ink-soft">
                    {tx(detail.description)}
                </p>
            )}

            <div className="mb-4 flex items-center gap-2 overflow-x-auto pb-1">
                {typeTabs.map((tab) => (
                    <button
                        key={tab.value}
                        type="button"
                        onClick={() => {
                            setType(tab.value);
                            setPage(1);
                        }}
                        className={chipClass(type === tab.value)}
                    >
                        {tab.label}
                    </button>
                ))}
            </div>

            {materials.error ? (
                <ErrorCard message={materials.error} onRetry={materials.refetch} />
            ) : materials.loading ? (
                <SkeletonGrid count={6} />
            ) : materials.rows.length === 0 ? (
                <PublicEmptyState onReset={type ? () => setType('') : undefined} />
            ) : (
                <>
                    <CardGrid>
                        {materials.rows.map((material) => (
                            <MaterialCard key={material.id} material={material} />
                        ))}
                    </CardGrid>

                    <div className="mt-6 flex justify-center">
                        <Pagination
                            links={materials.pagination?.links}
                            onPageChange={(next) => {
                                if (next == null) return;
                                setPage(next);
                                window.scrollTo({ top: 0 });
                            }}
                        />
                    </div>
                </>
            )}
        </>
    );
}

SubjectShow.layout = (page: ReactNode) => <PublicLayout>{page}</PublicLayout>;
