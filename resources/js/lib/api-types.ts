import type { DataTablePagination } from '@/components/ui';
import type { PaginationLinks } from '@/components/ui';

export interface ApiEnvelope<T> {
    success: boolean;
    message: string | null;
    result: T;
}

export interface SimplePaginationMeta {
    current_page: number;
    from?: number | null;
    path: string;
    per_page: number;
    to?: number | null;
}

export interface SimpleResourcePaginator<T> {
    data: T[];
    links: PaginationLinks;
    meta: SimplePaginationMeta;
    status_count?: Record<string, number>;
}

export function dataTablePagination<T>(
    paginator: SimpleResourcePaginator<T> | null,
): DataTablePagination | undefined {
    if (!paginator) return undefined;

    return {
        from: paginator.meta.from ?? undefined,
        to: paginator.meta.to ?? undefined,
        links: paginator.links,
    };
}
