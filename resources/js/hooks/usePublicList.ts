import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import axios from 'axios';
import api from '@/lib/api-client';
import type { DataTablePagination } from '@/components/ui';
import {
    dataTablePagination,
    type ApiEnvelope,
    type ResourcePaginator,
} from '@/lib/api-types';
import { errorMessage as messageFromError } from '@/lib/flash';

export type PublicListParams = Record<string, string | number | null | undefined>;

export interface UsePublicListOptions {
    url: string;
    params?: PublicListParams;
    enabled?: boolean;
    errorMessage?: string;
}

export interface UsePublicListResult<Row> {
    rows: Row[];
    total: number | null;
    lastPage: number | null;
    pagination: DataTablePagination | undefined;
    loading: boolean;
    error: string | null;
    refetch: () => Promise<void>;
}

export default function usePublicList<Row>({
    url,
    params,
    enabled = true,
    errorMessage = 'Something went wrong.',
}: UsePublicListOptions): UsePublicListResult<Row> {
    const paramSignature = useMemo(() => JSON.stringify(params ?? {}), [params]);
    const paramsRef = useRef<PublicListParams | undefined>(params);
    paramsRef.current = params;

    const [paginator, setPaginator] = useState<ResourcePaginator<Row> | null>(null);
    const [loading, setLoading] = useState(enabled);
    const [error, setError] = useState<string | null>(null);

    const abortRef = useRef<AbortController | null>(null);

    const refetch = useCallback(async () => {
        if (!enabled) return;

        abortRef.current?.abort();
        const controller = new AbortController();
        abortRef.current = controller;

        setLoading(true);
        setError(null);

        try {
            const { data } = await api.get<ApiEnvelope<ResourcePaginator<Row>>>(url, {
                params: paramsRef.current,
                signal: controller.signal,
            });

            setPaginator(data.result);
        } catch (err) {
            if (axios.isCancel(err)) return;
            setError(messageFromError(err, errorMessage));
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

    return {
        rows: paginator?.data ?? [],
        total: paginator?.meta.total ?? null,
        lastPage: paginator?.meta.last_page ?? null,
        pagination: dataTablePagination(paginator),
        loading,
        error,
        refetch,
    };
}
