import type { ReactNode } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { AcademicCapIcon, ArrowRightIcon } from '@heroicons/react/24/outline';
import PublicLayout from '@/components/public/PublicLayout';
import AppLink from '@/components/public/AppLink';
import MaterialCard from '@/components/public/MaterialCard';
import { CardGrid, SkeletonGrid } from '@/components/public/helpers';
import usePublicList from '@/hooks/usePublicList';
import useTranslation from '@/hooks/useTranslation';
import { SITE_DESCRIPTION } from '@/config/site';
import type { PublicMaterial } from '../types';

const LATEST_PARAMS = { per_page: 6 };
const FEATURED_PARAMS = { featured: 1, per_page: 6 };

export default function PublicHome() {
    const { t, tx } = useTranslation();
    const programs = usePage().props.programs ?? [];

    const latest = usePublicList<PublicMaterial>({
        url: '/public/materials',
        params: LATEST_PARAMS,
    });

    const featured = usePublicList<PublicMaterial>({
        url: '/public/materials',
        params: FEATURED_PARAMS,
    });

    return (
        <>
            <Head title={t('nav.home')}>
                <meta name="description" content={SITE_DESCRIPTION} />
            </Head>

            <section className="rounded-card bg-brand px-5 py-10 text-white md:px-10 md:py-14">
                <span className="rounded-chip bg-white/10 px-2.5 py-1 text-xs font-semibold">
                    {t('common.free_badge')}
                </span>
                <h1 className="mt-4 max-w-2xl text-2xl font-bold leading-snug md:text-4xl">
                    {t('home.tagline')}
                </h1>
                <p className="mt-3 max-w-2xl text-sm text-white/80 md:text-base">
                    {t('home.subtitle')}
                </p>
            </section>

            <section className="mt-8">
                <h2 className="text-lg font-semibold text-ink">{t('home.pick_program')}</h2>

                <div className="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    {programs.map((program) => (
                        <AppLink
                            key={program.slug}
                            href={`/programs/${program.slug}`}
                            className="group flex items-center gap-3 rounded-card border border-hairline bg-white p-4 shadow-sm transition hover:border-brand-muted hover:shadow"
                        >
                            <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-chip bg-brand/5">
                                <AcademicCapIcon className="h-5 w-5 text-brand" />
                            </span>
                            <span className="min-w-0 flex-1 font-medium text-ink group-hover:text-brand">
                                {tx(program.name)}
                            </span>
                            <ArrowRightIcon className="h-4 w-4 shrink-0 text-gray-300 group-hover:text-brand" />
                        </AppLink>
                    ))}
                </div>
            </section>

            {(featured.loading || featured.rows.length > 0) && (
                <section className="mt-10">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold text-ink">{t('home.featured')}</h2>
                        <AppLink
                            href="/browse"
                            className="text-sm font-medium text-brand-accent hover:underline"
                        >
                            {t('home.view_all')}
                        </AppLink>
                    </div>

                    <div className="mt-3">
                        {featured.loading ? (
                            <SkeletonGrid count={3} />
                        ) : (
                            <CardGrid>
                                {featured.rows.map((material) => (
                                    <MaterialCard key={material.id} material={material} />
                                ))}
                            </CardGrid>
                        )}
                    </div>
                </section>
            )}

            <section className="mt-10">
                <div className="flex items-center justify-between">
                    <h2 className="text-lg font-semibold text-ink">{t('home.latest')}</h2>
                    <AppLink
                        href="/browse"
                        className="text-sm font-medium text-brand-accent hover:underline"
                    >
                        {t('home.view_all')}
                    </AppLink>
                </div>

                <div className="mt-3">
                    {latest.loading ? (
                        <SkeletonGrid count={6} />
                    ) : (
                        <CardGrid>
                            {latest.rows.map((material) => (
                                <MaterialCard key={material.id} material={material} />
                            ))}
                        </CardGrid>
                    )}
                </div>
            </section>
        </>
    );
}

PublicHome.layout = (page: ReactNode) => <PublicLayout>{page}</PublicLayout>;
