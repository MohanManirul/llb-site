import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import axios from 'axios';
import api from '@/lib/api-client';
import type { ApiEnvelope } from '@/lib/api-types';
import { errorMessage as messageFromError } from '@/lib/flash';
import type { PublicListParams } from './usePublicList';

export interface UsePublicResourceOptions {
    params?: PublicListParams;
    enabled?: boolean;
    errorMessage?: string;
}

export interface UsePublicResourceResult<T> {
    data: T | null;
    extra: Record<string, unknown>;
    loading: boolean;
    error: string | null;
    notFound: boolean;
    refetch: () => Promise<void>;
}

export default function usePublicResource<T>(
    url: string,
    { params, enabled = true, errorMessage = 'Something went wrong.' }: UsePublicResourceOptions = {},
): UsePublicResourceResult<T> {
    const paramSignature = useMemo(() => JSON.stringify(params ?? {}), [params]);
    const paramsRef = useRef<PublicListParams | undefined>(params);
    paramsRef.current = params;

    const [data, setData] = useState<T | null>(null);
    const [extra, setExtra] = useState<Record<string, unknown>>({});
    const [loading, setLoading] = useState(enabled);
    const [error, setError] = useState<string | null>(null);
    const [notFound, setNotFound] = useState(false);

    const abortRef = useRef<AbortController | null>(null);

    const refetch = useCallback(async () => {
        if (!enabled) return;

        abortRef.current?.abort();
        const controller = new AbortController();
        abortRef.current = controller;

        setLoading(true);
        setError(null);
        setNotFound(false);

        try {
            const { data: response } = await api.get<ApiEnvelope<T & Record<string, unknown>>>(url, {
                params: paramsRef.current,
                signal: controller.signal,
            });

            setData(response.result);
            setExtra(response.result as Record<string, unknown>);
        } catch (err) {
            if (axios.isCancel(err)) return;

            const status = (err as { response?: { status?: number } })?.response?.status;

            if (status === 404) {
                setNotFound(true);
            } else {
                setError(messageFromError(err, errorMessage));
            }
        } finally {
            if (abortRef.current === controller) {
                setLoading(false);
            }
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [url, paramSignature, enabled, errorMessage]);

    useEffect(() => {
        refetch();

        return () => abortRef.current?.abort();
    }, [refetch]);

    return { data, extra, loading, error, notFound, refetch };
}
