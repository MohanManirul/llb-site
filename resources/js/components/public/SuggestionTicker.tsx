import { useMemo } from 'react';
import { SparklesIcon } from '@heroicons/react/24/outline';
import usePublicList from '@/hooks/usePublicList';
import useTranslation from '@/hooks/useTranslation';
import type { PublicMaterial } from '@/pages/public/types';
import AppLink from './AppLink';

const TICKER_TARGET = '/materials/exclusive-sugession';

const TICKER_PARAMS = {
    type: 'suggestion',
    sort: 'sort_order',
    direction: 'desc',
    per_page: 8,
};

const SECONDS_PER_ITEM = 6;

export default function SuggestionTicker() {
    const { t, tx } = useTranslation();

    const list = usePublicList<PublicMaterial>({
        url: '/public/materials',
        params: TICKER_PARAMS,
    });

    const items = useMemo(
        () => list.rows.map((row) => ({ id: row.id, title: tx(row.title) })).filter((row) => row.title !== ''),
        [list.rows, tx],
    );

    if (items.length === 0) return null;

    const duration = `${items.length * SECONDS_PER_ITEM}s`;

    return (
        <div className="group hidden min-w-0 flex-1 items-center gap-2 sm:flex">
            <span className="inline-flex shrink-0 items-center gap-1 rounded-chip bg-brass-soft px-2 py-0.5 text-[11px] font-semibold text-brass-deep">
                <SparklesIcon className="h-3.5 w-3.5" />
                {t('ticker.badge')}
            </span>

            <div
                className="relative min-w-0 flex-1 overflow-hidden"
                aria-label={t('ticker.badge')}
            >
                <div
                    className="flex w-max animate-ticker items-center group-hover:[animation-play-state:paused] group-focus-within:[animation-play-state:paused] motion-reduce:animate-none"
                    style={{ animationDuration: duration }}
                >
                    {[0, 1].map((copy) => (
                        <div key={copy} className="flex items-center" aria-hidden={copy === 1}>
                            {items.map((item) => (
                                <AppLink
                                    key={`${copy}-${item.id}`}
                                    href={TICKER_TARGET}
                                    tabIndex={copy === 1 ? -1 : undefined}
                                    className="inline-flex items-center gap-2 whitespace-nowrap px-3 py-1 text-xs font-medium text-ink-muted hover:text-brand-accent hover:underline"
                                >
                                    <span aria-hidden="true" className="h-1 w-1 rounded-full bg-brass" />
                                    {item.title}
                                </AppLink>
                            ))}
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}
