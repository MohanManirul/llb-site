import type { ReactNode } from 'react';
import { Link } from '@inertiajs/react';
import {
    ArchiveBoxIcon,
    ArrowRightIcon,
    ClipboardDocumentCheckIcon,
    LightBulbIcon,
    UserCircleIcon,
} from '@heroicons/react/24/outline';
import PublicLayout from '@/components/public/PublicLayout';
import PublicPageHeader from '@/components/public/PublicPageHeader';
import AppLink from '@/components/public/AppLink';
import useStudent from '@/hooks/useStudent';
import useTranslation from '@/hooks/useTranslation';

interface PrepCardProps {
    href: string;
    icon: ReactNode;
    title: string;
    description: string;
    badge?: string;
}

function PrepCard({ href, icon, title, description, badge }: PrepCardProps) {
    const { t } = useTranslation();

    return (
        <AppLink
            href={href}
            className="group flex flex-col rounded-card border border-hairline border-b-2 border-b-brass/40 bg-white p-5 shadow-sm transition hover:border-b-brass hover:shadow"
        >
            <span className="flex h-11 w-11 items-center justify-center rounded-chip bg-brass-soft text-brass-deep">
                {icon}
            </span>
            <span className="mt-4 flex items-center gap-2">
                <span className="text-lg font-semibold text-ink group-hover:text-brand">{title}</span>
                {badge && (
                    <span className="rounded-chip bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600">
                        {badge}
                    </span>
                )}
            </span>
            <span className="mt-1 flex-1 text-sm text-ink-muted">{description}</span>
            <span className="mt-4 inline-flex items-center gap-1 text-sm font-medium text-brand-accent">
                {t('prep.open')}
                <ArrowRightIcon className="h-4 w-4 transition group-hover:translate-x-0.5" />
            </span>
        </AppLink>
    );
}

export default function ExamPrepIndex() {
    const { t, localeHref } = useTranslation();
    const { student, loginHref } = useStudent();

    return (
        <>
            <PublicPageHeader
                title={t('prep.title')}
                description={t('prep.subtitle')}
                crumbs={[{ label: t('nav.exam_prep') }]}
            >
                <p className="mt-2 max-w-2xl text-sm text-ink-muted">{t('prep.subtitle')}</p>
            </PublicPageHeader>

            <div className="grid gap-4 md:grid-cols-3">
                <PrepCard
                    href="/questions"
                    icon={<ArchiveBoxIcon className="h-6 w-6" />}
                    title={t('prep.archive_title')}
                    description={t('prep.archive_desc')}
                    badge={t('common.free_badge')}
                />
                <PrepCard
                    href="/practice"
                    icon={<LightBulbIcon className="h-6 w-6" />}
                    title={t('prep.practice_title')}
                    description={t('prep.practice_desc')}
                />
                <PrepCard
                    href="/model-tests"
                    icon={<ClipboardDocumentCheckIcon className="h-6 w-6" />}
                    title={t('prep.model_tests_title')}
                    description={t('prep.model_tests_desc')}
                />
            </div>

            <div className="mt-6 flex flex-wrap items-center gap-3 rounded-card border border-hairline bg-white px-5 py-4 text-sm">
                <UserCircleIcon className="h-5 w-5 shrink-0 text-brand-accent" />
                {student ? (
                    <>
                        <span className="text-ink">{t('account.welcome', { name: student.name })}</span>
                        <AppLink href="/account/attempts" className="font-medium text-brand-accent hover:underline">
                            {t('nav.my_attempts')}
                        </AppLink>
                        <AppLink href="/account/profile" className="font-medium text-brand-accent hover:underline">
                            {t('nav.profile')}
                        </AppLink>
                    </>
                ) : (
                    <>
                        <span className="text-ink-muted">{t('prep.login_hint')}</span>
                        <Link
                            href={loginHref(localeHref('/exam-prep'))}
                            className="font-medium text-brand-accent hover:underline"
                        >
                            {t('nav.login')}
                        </Link>
                        <AppLink href="/account/register" className="font-medium text-brand-accent hover:underline">
                            {t('nav.register')}
                        </AppLink>
                    </>
                )}
            </div>
        </>
    );
}

ExamPrepIndex.layout = (page: ReactNode) => <PublicLayout>{page}</PublicLayout>;
