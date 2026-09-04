import { useEffect } from 'react';
import { router } from '@inertiajs/react';

export type UrlQueryValues = Record<string, string | number | null | undefined>;

export function readUrlQuery(): URLSearchParams {
    return new URLSearchParams(
        typeof window === 'undefined' ? '' : window.location.search,
    );
}

function buildQuery(values: UrlQueryValues): string {
    const params = new URLSearchParams();

    Object.entries(values).forEach(([key, value]) => {
        if (value === null || value === undefined || value === '') return;

        params.set(key, String(value));
    });

    params.sort();

    return params.toString();
}

export default function useUrlQuery(values: UrlQueryValues): void {
    const query = buildQuery(values);

    useEffect(() => {
        if (typeof window === 'undefined') return;

        const current = new URLSearchParams(window.location.search);
        current.sort();

        if (current.toString() === query) return;

        router.replace({
            url: query === ''
                ? window.location.pathname
                : `${window.location.pathname}?${query}`,
            preserveState: true,
            preserveScroll: true,
        });
    }, [query]);
}
