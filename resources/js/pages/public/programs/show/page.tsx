import { useState, type ReactNode } from 'react';
import PublicLayout from '@/components/public/PublicLayout';
import PublicPageHeader from '@/components/public/PublicPageHeader';
import AppLink from '@/components/public/AppLink';
import SubjectCard from '@/components/public/SubjectCard';
import {
    ErrorCard,
    LoadingBlock,
    PublicEmptyState,
} from '@/components/public/helpers';
import usePublicResource from '@/hooks/usePublicResource';
import useTranslation from '@/hooks/useTranslation';
import type { PageMeta, PublicProgram, PublicSubject } from '../../types';

interface ProgramShowProps {
    programSlug: string;
    meta: PageMeta;
}

export default function ProgramShow({ programSlug, meta }: ProgramShowProps) {
    const { t, tx, isBn } = useTranslation();
    const [levelSlug, setLevelSlug] = useState('');

    const program = usePublicResource<PublicProgram>(`/public/programs/${programSlug}`);

    const subjects = usePublicResource<PublicSubject[]>('/public/subjects', {
        params: { program: programSlug, level: levelSlug || undefined },
    });

    const metaTitle = (isBn ? meta.title_bn : (meta.title_en ?? meta.title_bn)) || meta.title_bn;

    if (program.loading) {
        return (
            <>
                <PublicPageHeader title={metaTitle} />
                <LoadingBlock />
            </>
        );
    }

    if (program.error || !program.data) {
        return (
            <>
                <PublicPageHeader title={metaTitle} />
                <ErrorCard message={program.error ?? t('browse.load_error')} onRetry={program.refetch} />
            </>
        );
    }

    const detail = program.data;
    const levels = detail.levels ?? [];

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
                crumbs={[{ label: tx(detail.name) }]}
                aside={
                    <AppLink
                        href={`/browse?program=${detail.slug}`}
                        className="rounded-control bg-brand px-3 py-2 text-sm font-semibold text-white hover:bg-brand-accent"
                    >
                        {t('program.browse_all')}
                    </AppLink>
                }
            />

            {detail.has_levels && levels.length > 0 && (
                <div className="mb-4 flex items-center gap-2 overflow-x-auto pb-1">
                    <span className="shrink-0 text-xs font-medium text-ink-muted">
                        {tx(detail.level_label)}:
                    </span>

                    <button type="button" onClick={() => setLevelSlug('')} className={chipClass(levelSlug === '')}>
                        {t('browse.all')}
                    </button>

                    {levels.map((level) => (
                        <button
                            key={level.slug}
                            type="button"
                            onClick={() => setLevelSlug(level.slug)}
                            className={chipClass(levelSlug === level.slug)}
                        >
                            {tx(level.name)}
                        </button>
                    ))}
                </div>
            )}

            <h2 className="mb-3 text-lg font-semibold text-ink">{t('program.subjects')}</h2>

            {subjects.loading ? (
                <LoadingBlock />
            ) : subjects.error ? (
                <ErrorCard message={subjects.error} onRetry={subjects.refetch} />
            ) : (subjects.data ?? []).length === 0 ? (
                <PublicEmptyState />
            ) : (
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {(subjects.data ?? []).map((subject) => (
                        <SubjectCard key={subject.slug} subject={subject} />
                    ))}
                </div>
            )}
        </>
    );
}

ProgramShow.layout = (page: ReactNode) => <PublicLayout>{page}</PublicLayout>;
