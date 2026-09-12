import { useLayoutEffect, useMemo, useRef, useState } from 'react';
import { SparklesIcon } from '@heroicons/react/24/outline';
import usePublicList from '@/hooks/usePublicList';
import useTranslation from '@/hooks/useTranslation';
import type { PublicMaterial } from '@/pages/public/types';
import AppLink from './AppLink';

const TICKER_PARAMS = {
    type: 'suggestion',
    sort: 'sort_order',
    direction: 'desc',
    per_page: 8,
};

const SECONDS_PER_ITEM = 6;

export interface SuggestionTickerItem {
    id: number;
    slug: string;
    title: string;
}

export function useSuggestionTickerItems(): SuggestionTickerItem[] {
    const { tx } = useTranslation();

    const list = usePublicList<PublicMaterial>({
        url: '/public/materials',
        params: TICKER_PARAMS,
    });

    return useMemo(
        () =>
            list.rows
                .map((row) => ({ id: row.id, slug: row.slug, title: tx(row.title) }))
                .filter((row) => row.title !== '' && row.slug !== ''),
        [list.rows, tx],
    );
}

interface SuggestionTickerProps {
    items: SuggestionTickerItem[];
}

export default function SuggestionTicker({ items }: SuggestionTickerProps) {
    const { t } = useTranslation();
    const windowRef = useRef<HTMLDivElement>(null);
    const copyRef = useRef<HTMLDivElement>(null);
    const [scrolling, setScrolling] = useState(false);

    useLayoutEffect(() => {
        const track = windowRef.current;
        const copy = copyRef.current;

        if (!track || !copy) return;

        const measure = () => {
            const copyWidth = copy.getBoundingClientRect().width;

            if (copyWidth === 0) return;

            setScrolling(copyWidth > track.clientWidth);
        };

        measure();

        const observer = new ResizeObserver(measure);

        observer.observe(track);
        observer.observe(copy);

        return () => observer.disconnect();
    }, [items]);

    if (items.length === 0) return null;

    const duration = `${items.length * SECONDS_PER_ITEM}s`;

    return (
        <div className="group flex min-w-0 flex-1 items-center gap-2">
            <span className="hidden shrink-0 items-center gap-1 rounded-chip bg-brass-soft px-2 py-0.5 text-[11px] font-semibold text-brass-deep sm:inline-flex">
                <SparklesIcon className="h-3.5 w-3.5" />
                {t('ticker.badge')}
            </span>

            <div
                ref={windowRef}
                className="relative min-w-0 flex-1 overflow-hidden"
                aria-label={t('ticker.badge')}
            >
                <div
                    className={
                        'flex w-max items-center ' +
                        (scrolling
                            ? 'animate-ticker group-hover:[animation-play-state:paused] group-focus-within:[animation-play-state:paused] motion-reduce:animate-none'
                            : '')
                    }
                    style={scrolling ? { animationDuration: duration } : undefined}
                >
                    {Array.from({ length: scrolling ? 2 : 1 }, (_, copy) => (
                        <div
                            key={copy}
                            ref={copy === 0 ? copyRef : undefined}
                            className="flex items-center"
                            aria-hidden={copy > 0}
                        >
                            {items.map((item) => (
                                <AppLink
                                    key={`${copy}-${item.id}`}
                                    href={`/materials/${item.slug}`}
                                    tabIndex={copy > 0 ? -1 : undefined}
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
