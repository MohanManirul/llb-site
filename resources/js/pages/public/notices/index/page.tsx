import { useMemo, type ReactNode } from 'react';
import { MapPinIcon, PaperClipIcon } from '@heroicons/react/24/outline';
import PublicLayout from '@/components/public/PublicLayout';
import PublicPageHeader from '@/components/public/PublicPageHeader';
import AppLink from '@/components/public/AppLink';
import {
    ErrorCard,
    LoadingBlock,
    PublicEmptyState,
} from '@/components/public/helpers';
import { Pagination, StatusBadge } from '@/components/ui';
import usePublicList from '@/hooks/usePublicList';
import useQueryParams from '@/hooks/useQueryParams';
import useTranslation from '@/hooks/useTranslation';
import type { TranslatedField } from '@/lib/i18n';

interface PublicNotice {
    id: number;
    slug: string;
    title: TranslatedField;
    excerpt: TranslatedField;
    category: string;
    is_pinned: boolean;
    published_at: string | null;
    has_attachment: boolean;
    program?: { slug: string; name: TranslatedField } | null;
    session?: { slug: string; label: string } | null;
}

const CATEGORY_KEYS = ['general', 'exam', 'routine', 'result', 'admission'] as const;

const CATEGORY_LABELS: Record<string, TranslatedField> = {
    general: { bn: 'সাধারণ', en: 'General' },
    exam: { bn: 'পরীক্ষা', en: 'Exam' },
    routine: { bn: 'রুটিন', en: 'Routine' },
    result: { bn: 'ফলাফল', en: 'Result' },
    admission: { bn: 'ভর্তি', en: 'Admission' },
};

export default function NoticesIndex() {
    const { t, tx, d } = useTranslation();
    const [params, setParams] = useQueryParams({ category: '', page: '1' });

    const listParams = useMemo(
        () => ({
            category: params.category || undefined,
            page: params.page !== '1' ? params.page : undefined,
        }),
        [params.category, params.page],
    );

    const list = usePublicList<PublicNotice>({
        url: '/public/notices',
        params: listParams,
        errorMessage: t('browse.load_error'),
    });

    const chipClass = (active: boolean) =>
        'shrink-0 rounded-chip border px-3 py-1.5 text-sm font-medium transition ' +
        (active
            ? 'border-brand bg-brand text-white'
            : 'border-hairline bg-white text-ink hover:border-brand-muted');

    return (
        <>
            <PublicPageHeader title={t('nav.notices')} crumbs={[{ label: t('nav.notices') }]} />

            <div className="mb-4 flex items-center gap-2 overflow-x-auto pb-1">
                <button
                    type="button"
                    onClick={() => setParams({ category: '', page: '1' })}
                    className={chipClass(params.category === '')}
                >
                    {t('notice.all')}
                </button>

                {CATEGORY_KEYS.map((key) => (
                    <button
                        key={key}
                        type="button"
                        onClick={() => setParams({ category: key, page: '1' })}
                        className={chipClass(params.category === key)}
                    >
                        {tx(CATEGORY_LABELS[key])}
                    </button>
                ))}
            </div>

            {list.error ? (
                <ErrorCard message={list.error} onRetry={list.refetch} />
            ) : list.loading ? (
                <LoadingBlock />
            ) : list.rows.length === 0 ? (
                <PublicEmptyState
                    onReset={params.category ? () => setParams({ category: '', page: '1' }) : undefined}
                />
            ) : (
                <>
                    <ul className="space-y-3">
                        {list.rows.map((notice) => (
                            <li key={notice.id}>
                                <AppLink
                                    href={`/notices/${notice.slug}`}
                                    className="flex flex-col gap-1 rounded-card border border-hairline bg-white p-4 shadow-sm transition hover:border-brand-muted hover:shadow"
                                >
                                    <div className="flex flex-wrap items-center gap-2">
                                        {notice.is_pinned && (
                                            <span className="inline-flex items-center gap-1 rounded-chip bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">
                                                <MapPinIcon className="h-3 w-3" />
                                                {t('notice.pinned')}
                                            </span>
                                        )}
                                        <StatusBadge
                                            status={tx(CATEGORY_LABELS[notice.category] ?? null) || notice.category}
                                            tone="blue"
                                        />
                                        {notice.has_attachment && (
                                            <PaperClipIcon className="h-4 w-4 text-gray-400" />
                                        )}
                                        <span className="ml-auto text-xs text-ink-muted">
                                            {d(notice.published_at)}
                                        </span>
                                    </div>

                                    <h2 className="font-semibold text-ink">{tx(notice.title)}</h2>

                                    {tx(notice.excerpt) && (
                                        <p className="line-clamp-2 text-sm text-ink-muted">
                                            {tx(notice.excerpt)}
                                        </p>
                                    )}

                                    {(notice.program || notice.session) && (
                                        <p className="text-xs text-ink-muted">
                                            {notice.program ? tx(notice.program.name) : ''}
                                            {notice.program && notice.session ? ' · ' : ''}
                                            {notice.session?.label ?? ''}
                                        </p>
                                    )}
                                </AppLink>
                            </li>
                        ))}
                    </ul>

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
        </>
    );
}

NoticesIndex.layout = (page: ReactNode) => <PublicLayout>{page}</PublicLayout>;
