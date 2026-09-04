import { useMemo, type ReactNode } from 'react';
import { usePage } from '@inertiajs/react';
import { MagnifyingGlassIcon } from '@heroicons/react/24/outline';
import PublicLayout from '@/components/public/PublicLayout';
import PublicPageHeader from '@/components/public/PublicPageHeader';
import MaterialCard from '@/components/public/MaterialCard';
import {
    CardGrid,
    ErrorCard,
    PublicEmptyState,
    SkeletonGrid,
} from '@/components/public/helpers';
import { Pagination } from '@/components/ui';
import usePublicList from '@/hooks/usePublicList';
import usePublicResource from '@/hooks/usePublicResource';
import useQueryParams from '@/hooks/useQueryParams';
import useDebouncedValue from '@/hooks/useDebouncedValue';
import useTranslation from '@/hooks/useTranslation';
import type { FilterDef, PublicMaterial, PublicProgram } from '../types';

interface BrowsePageProps {
    pinnedType?: string | null;
}

const QUERY_DEFAULTS = {
    search: '',
    program: '',
    level: '',
    subject: '',
    session: '',
    exam_stage: '',
    type: '',
    page: '1',
};

export default function Browse({ pinnedType = null }: BrowsePageProps) {
    const { t, tx } = useTranslation();
    const programs = usePage().props.programs ?? [];

    const [params, setParams] = useQueryParams(QUERY_DEFAULTS);
    const debouncedSearch = useDebouncedValue(params.search);

    const effectiveType = pinnedType ?? params.type;

    const programDetail = usePublicResource<PublicProgram & { filters?: FilterDef[] }>(
        `/public/programs/${params.program}`,
        { enabled: params.program !== '' },
    );

    const listParams = useMemo(
        () => ({
            search: debouncedSearch || undefined,
            program: params.program || undefined,
            level: params.level || undefined,
            subject: params.subject || undefined,
            session: params.session || undefined,
            exam_stage: params.exam_stage || undefined,
            type: effectiveType || undefined,
            page: params.page !== '1' ? params.page : undefined,
            per_page: 12,
        }),
        [
            debouncedSearch,
            params.program,
            params.level,
            params.subject,
            params.session,
            params.exam_stage,
            effectiveType,
            params.page,
        ],
    );

    const list = usePublicList<PublicMaterial>({
        url: '/public/materials',
        params: listParams,
        errorMessage: t('browse.load_error'),
    });

    const filterDefs = params.program !== '' ? (programDetail.extra.filters as FilterDef[] | undefined) ?? [] : [];

    const title =
        pinnedType === 'suggestion'
            ? t('nav.suggestions')
            : pinnedType === 'book'
              ? t('nav.books')
              : pinnedType === 'note'
                ? t('nav.notes')
                : t('browse.title');

    const reset = () =>
        setParams({
            search: '',
            program: '',
            level: '',
            subject: '',
            session: '',
            exam_stage: '',
            type: '',
            page: '1',
        });

    const chipClass = (active: boolean) =>
        'shrink-0 rounded-chip border px-3 py-1.5 text-sm font-medium transition ' +
        (active
            ? 'border-brand bg-brand text-white'
            : 'border-hairline bg-white text-ink hover:border-brand-muted');

    return (
        <>
            <PublicPageHeader title={title} crumbs={[{ label: title }]} />

            <label className="relative block max-w-xl">
                <span className="sr-only">{t('search.label')}</span>
                <MagnifyingGlassIcon className="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
                <input
                    type="search"
                    value={params.search}
                    onChange={(e) => setParams({ search: e.target.value, page: '1' }, { replace: true })}
                    placeholder={t('search.placeholder')}
                    className="w-full rounded-control border border-hairline bg-white py-2.5 pl-10 pr-3 text-sm text-ink placeholder:text-gray-400 focus:border-brand-accent focus:outline-none"
                />
            </label>

            <div className="mt-4 space-y-3">
                <div className="flex gap-2 overflow-x-auto pb-1">
                    <button
                        type="button"
                        onClick={() => setParams({ program: '', level: '', subject: '', session: '', exam_stage: '', page: '1' })}
                        className={chipClass(params.program === '')}
                    >
                        {t('filter.all_programs')}
                    </button>

                    {programs.map((program) => (
                        <button
                            key={program.slug}
                            type="button"
                            onClick={() =>
                                setParams({
                                    program: program.slug,
                                    level: '',
                                    subject: '',
                                    session: '',
                                    exam_stage: '',
                                    page: '1',
                                })
                            }
                            className={chipClass(params.program === program.slug)}
                        >
                            {tx(program.short_name) || tx(program.name)}
                        </button>
                    ))}
                </div>

                {filterDefs.map((filter) => {
                    const isTypeFilter = filter.key === 'type';

                    if (isTypeFilter && pinnedType) return null;

                    const current = params[filter.key as keyof typeof params] ?? '';

                    return (
                        <div key={filter.key} className="flex items-center gap-2 overflow-x-auto pb-1">
                            <span className="shrink-0 text-xs font-medium text-ink-muted">
                                {tx(filter.label)}:
                            </span>

                            <button
                                type="button"
                                onClick={() => setParams({ [filter.key]: '', page: '1' } as never)}
                                className={chipClass(current === '')}
                            >
                                {t('browse.all')}
                            </button>

                            {filter.options.map((option) => (
                                <button
                                    key={option.value}
                                    type="button"
                                    onClick={() =>
                                        setParams({ [filter.key]: option.value, page: '1' } as never)
                                    }
                                    className={chipClass(current === option.value)}
                                >
                                    {tx(option.label)}
                                </button>
                            ))}
                        </div>
                    );
                })}
            </div>

            <div className="mt-5">
                {list.error ? (
                    <ErrorCard message={list.error} onRetry={list.refetch} />
                ) : list.loading ? (
                    <SkeletonGrid count={6} />
                ) : list.rows.length === 0 ? (
                    <PublicEmptyState onReset={reset} />
                ) : (
                    <>
                        <p className="mb-3 text-sm text-ink-muted">
                            {t('browse.results', { count: String(list.total ?? list.rows.length) })}
                        </p>

                        <CardGrid>
                            {list.rows.map((material) => (
                                <MaterialCard key={material.id} material={material} />
                            ))}
                        </CardGrid>

                        <div className="mt-6 flex justify-center">
                            <Pagination
                                links={list.pagination?.links}
                                onPageChange={(page) => {
                                    if (page == null) return;
                                    setParams({ page: String(page) });
                                    window.scrollTo({ top: 0 });
                                }}
                            />
                        </div>
                    </>
                )}
            </div>
        </>
    );
}

Browse.layout = (page: ReactNode) => <PublicLayout wide>{page}</PublicLayout>;
