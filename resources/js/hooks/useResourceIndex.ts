import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import axios from 'axios';
import useDataTable, {
    type DataTableStateProps,
    type UseDataTableOptions,
    type UseDataTableResult,
} from './useDataTable';
import api from '@/lib/api-client';
import type { DataTablePagination } from '@/components/ui';
import {
    dataTablePagination,
    type ApiEnvelope,
    type SimpleResourcePaginator,
} from '@/lib/api-types';
import { errorMessage as messageFromError, flash } from '@/lib/flash';

export type ResourceFilters = Record<
    string,
    string | number | boolean | null | undefined
>;

export interface UseResourceIndexOptions
    extends Omit<UseDataTableOptions, 'resetKey'> {
    url: string;
    filters?: ResourceFilters;
    errorMessage?: string;
}

export interface ResourceTableProps<Row> extends DataTableStateProps {
    rows: Row[];
    pagination: DataTablePagination | undefined;
    loading: boolean;
}

export interface UseResourceIndexResult<Row> extends UseDataTableResult {
    paginator: SimpleResourcePaginator<Row> | null;
    setPaginator: React.Dispatch<
        React.SetStateAction<SimpleResourcePaginator<Row> | null>
    >;
    loading: boolean;
    refetch: () => Promise<void>;
    tableProps: ResourceTableProps<Row>;
}

export default function useResourceIndex<Row>({
    url,
    filters,
    errorMessage = 'Something went wrong.',
    ...tableOptions
}: UseResourceIndexOptions): UseResourceIndexResult<Row> {
    const filterSignature = useMemo(
        () => JSON.stringify(filters ?? {}),
        [filters],
    );
    const filtersRef = useRef<ResourceFilters | undefined>(filters);
    filtersRef.current = filters;

    const table = useDataTable({ ...tableOptions, resetKey: filterSignature });
    const { debouncedSearch, page, perPage, sort } = table;

    const [paginator, setPaginator] =
        useState<SimpleResourcePaginator<Row> | null>(null);
    const [loading, setLoading] = useState(true);

    const abortRef = useRef<AbortController | null>(null);

    const refetch = useCallback(async () => {
        abortRef.current?.abort();
        const controller = new AbortController();
        abortRef.current = controller;

        setLoading(true);
        try {
            const { data } = await api.get<
                ApiEnvelope<SimpleResourcePaginator<Row>>
            >(url, {
                params: {
                    search: debouncedSearch,
                    ...filtersRef.current,
                    per_page: perPage,
                    sort: sort.column,
                    direction: sort.direction,
                    page,
                },
                signal: controller.signal,
            });
            setPaginator(data.result);
        } catch (error) {
            if (axios.isCancel(error)) return;
            flash.error(messageFromError(error, errorMessage));
        } finally {
            if (abortRef.current === controller) {
                setLoading(false);
            }
        }
    }, [
        url,
        debouncedSearch,
        filterSignature,
        perPage,
        sort.column,
        sort.direction,
        page,
        errorMessage,
    ]);

    useEffect(() => {
        refetch();
        return () => abortRef.current?.abort();
    }, [refetch]);

    const tableProps = useMemo<ResourceTableProps<Row>>(
        () => ({
            ...table.tableProps,
            rows: paginator?.data ?? [],
            pagination: dataTablePagination(paginator),
            loading,
        }),
        [table.tableProps, paginator, loading],
    );

    return { ...table, paginator, setPaginator, loading, refetch, tableProps };
}
