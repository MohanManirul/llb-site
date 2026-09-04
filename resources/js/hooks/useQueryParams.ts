import { useCallback, useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';

function readParams<T extends Record<string, string>>(defaults: T): T {
    const query = new URLSearchParams(window.location.search);
    const out = { ...defaults };

    (Object.keys(defaults) as Array<keyof T>).forEach((key) => {
        const value = query.get(String(key));

        if (value !== null) {
            out[key] = value as T[keyof T];
        }
    });

    return out;
}

function buildUrl(params: Record<string, string>): string {
    const query = new URLSearchParams();

    Object.entries(params).forEach(([key, value]) => {
        if (value !== '' && value != null) {
            query.set(key, value);
        }
    });

    const search = query.toString();

    return window.location.pathname + (search ? `?${search}` : '');
}

export default function useQueryParams<T extends Record<string, string>>(
    defaults: T,
): [T, (patch: Partial<T>, options?: { replace?: boolean }) => void] {
    const pageUrl = usePage().url;
    const [params, setParams] = useState<T>(() => readParams(defaults));

    useEffect(() => {
        const onPopState = () => setParams(readParams(defaults));

        window.addEventListener('popstate', onPopState);

        return () => window.removeEventListener('popstate', onPopState);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    useEffect(() => {
        setParams(readParams(defaults));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [pageUrl.split('?')[0]]);

    const update = useCallback(
        (patch: Partial<T>, { replace = false }: { replace?: boolean } = {}) => {
            setParams((current) => {
                const next = { ...current, ...patch };
                const url = buildUrl(next);

                // Inertia keeps its page object in history.state — passing it
                // through untouched (never `{}`) is what keeps Back working.
                if (replace) {
                    window.history.replaceState(window.history.state, '', url);
                } else {
                    window.history.pushState(window.history.state, '', url);
                }

                return next;
            });
        },
        [],
    );

    return [params, update];
}
