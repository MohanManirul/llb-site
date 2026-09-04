import type { ReactNode } from 'react';
import { Head, usePage } from '@inertiajs/react';
import {
    ArrowRightIcon,
    BuildingLibraryIcon,
    ClockIcon,
    PencilSquareIcon,
    ScaleIcon,
    SparklesIcon,
} from '@heroicons/react/24/outline';
import PublicLayout from '@/components/public/PublicLayout';
import AppLink from '@/components/public/AppLink';
import MaterialCard from '@/components/public/MaterialCard';
import { CourtSceneArt } from '@/components/public/motifs';
import { CardGrid, SkeletonGrid } from '@/components/public/helpers';
import usePublicList from '@/hooks/usePublicList';
import useTranslation from '@/hooks/useTranslation';
import { SITE_DESCRIPTION } from '@/config/site';
import type { PublicMaterial } from '../types';

const LATEST_PARAMS = { per_page: 6 };
const FEATURED_PARAMS = { featured: 1, per_page: 6 };

function SectionTitle({ icon, children }: { icon: ReactNode; children: ReactNode }) {
    return (
        <h2 className="flex items-center gap-2.5 text-lg font-semibold text-ink">
            <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-chip bg-brass-soft text-brass-deep">
                {icon}
            </span>
            {children}
        </h2>
    );
}

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

            <section className="relative overflow-hidden rounded-card bg-brand px-5 py-10 text-white md:px-10 md:py-14">
                <div className="pointer-events-none absolute inset-0 bg-linear-to-r from-brand via-brand/95 to-banyan-deep/60" />
                <CourtSceneArt className="pointer-events-none absolute bottom-0 right-2 hidden h-full w-auto lg:block" />

                <div className="relative max-w-2xl">
                    <div className="flex flex-wrap items-center gap-2">
                        <span className="rounded-chip bg-white/10 px-2.5 py-1 text-xs font-semibold">
                            {t('common.free_badge')}
                        </span>
                        <span className="flex items-center gap-1.5 rounded-chip border border-brass/50 bg-brass/15 px-2.5 py-1 text-xs font-semibold text-brass">
                            <ScaleIcon className="h-3.5 w-3.5" />
                            {t('home.eyebrow')}
                        </span>
                    </div>

                    <h1 className="mt-4 text-2xl font-bold leading-snug md:text-4xl">
                        {t('home.tagline')}
                    </h1>
                    <p className="mt-3 text-sm text-white/80 md:text-base">
                        {t('home.subtitle')}
                    </p>

                    <div className="mt-6 h-px w-24 bg-brass/70" />
                </div>
            </section>

            <AppLink
                href="/exam-prep"
                className="group mt-6 flex items-center gap-4 rounded-card border border-hairline border-l-4 border-l-brass bg-white p-4 shadow-sm transition hover:shadow md:p-5"
            >
                <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-chip bg-brass-soft">
                    <PencilSquareIcon className="h-5 w-5 text-brass-deep" />
                </span>
                <span className="min-w-0 flex-1">
                    <span className="block font-semibold text-ink group-hover:text-brand">
                        {t('prep.home_cta')}
                    </span>
                    <span className="block text-sm text-ink-muted">{t('prep.home_cta_desc')}</span>
                </span>
                <ArrowRightIcon className="h-5 w-5 shrink-0 text-gray-300 group-hover:text-brass-deep" />
            </AppLink>

            <section className="mt-8">
                <SectionTitle icon={<BuildingLibraryIcon className="h-4.5 w-4.5" />}>
                    {t('home.pick_program')}
                </SectionTitle>

                <div className="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    {programs.map((program) => (
                        <AppLink
                            key={program.slug}
                            href={`/programs/${program.slug}`}
                            className="group flex items-center gap-3 rounded-card border border-hairline border-b-2 border-b-brass/40 bg-white p-4 shadow-sm transition hover:border-b-brass hover:shadow"
                        >
                            <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-chip bg-brass-soft">
                                <ScaleIcon className="h-5 w-5 text-brass-deep" />
                            </span>
                            <span className="min-w-0 flex-1 font-medium text-ink group-hover:text-brand">
                                {tx(program.name)}
                            </span>
                            <ArrowRightIcon className="h-4 w-4 shrink-0 text-gray-300 group-hover:text-brass-deep" />
                        </AppLink>
                    ))}
                </div>
            </section>

            {(featured.loading || featured.rows.length > 0) && (
                <section className="mt-10">
                    <div className="flex items-center justify-between">
                        <SectionTitle icon={<SparklesIcon className="h-4.5 w-4.5" />}>
                            {t('home.featured')}
                        </SectionTitle>
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
                    <SectionTitle icon={<ClockIcon className="h-4.5 w-4.5" />}>
                        {t('home.latest')}
                    </SectionTitle>
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
