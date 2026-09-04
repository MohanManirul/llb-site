import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import useDebouncedValue from './useDebouncedValue';
import useStoredState from './useStoredState';

export type SortDirection = 'asc' | 'desc';

export interface DataTableSort {
    column: string;
    direction: SortDirection;
}

export interface UseDataTableOptions {
    storageKey: string;
    hiddenColumnsKey?: string;
    defaultHidden?: string[];
    defaultPerPage?: number;
    defaultSort?: Partial<DataTableSort>;
    initialSearch?: string;
    initialPage?: number;
    resetKey?: unknown;
}

export interface DataTableStateProps {
    search: string;
    onSearchChange: (value: string) => void;
    perPage: number;
    onPerPageChange: (value: number) => void;
    hiddenColumns: string[];
    onHiddenColumnsChange: (keys: string[]) => void;
    sort: DataTableSort;
    onSort: (column: string, direction?: SortDirection) => void;
    onPageChange: (value: number | null) => void;
}

export interface UseDataTableResult {
    search: string;
    setSearch: React.Dispatch<React.SetStateAction<string>>;
    debouncedSearch: string;
    page: number;
    setPage: React.Dispatch<React.SetStateAction<number>>;
    perPage: number;
    setPerPage: React.Dispatch<React.SetStateAction<number>>;
    hiddenColumns: string[];
    setHiddenColumns: React.Dispatch<React.SetStateAction<string[]>>;
    sort: DataTableSort;
    changeSort: (column: string, direction?: SortDirection) => void;
    tableProps: DataTableStateProps;
}

export default function useDataTable({
    storageKey,
    hiddenColumnsKey,
    defaultHidden = [],
    defaultPerPage = 10,
    defaultSort,
    initialSearch = '',
    initialPage = 1,
    resetKey,
}: UseDataTableOptions): UseDataTableResult {
    const [search, setSearch] = useState(initialSearch);
    const debouncedSearch = useDebouncedValue(search);

    const [page, setPage] = useState(initialPage);
    const [perPage, setPerPage] = useStoredState(
        `datatable:${storageKey}:per_page`,
        defaultPerPage,
    );
    const [hiddenColumns, setHiddenColumns] = useStoredState<string[]>(
        hiddenColumnsKey ?? `datatable:${storageKey}:hidden_columns`,
        defaultHidden,
    );

    const [sort, setSort] = useState<DataTableSort>({
        column: defaultSort?.column ?? '',
        direction: defaultSort?.direction ?? 'desc',
    });

    const changeSort = useCallback(
        (column: string, direction?: SortDirection) => {
            setSort((current) => {
                const toggled =
                    current.direction === 'asc' ? 'desc' : 'asc';

                const next: DataTableSort = {
                    column,
                    direction:
                        direction ??
                        (current.column === column
                            ? toggled
                            : current.direction),
                };

                return next.column === current.column &&
                    next.direction === current.direction
                    ? current
                    : next;
            });
        },
        [],
    );

    const filterSignature = useMemo(
        () => JSON.stringify(resetKey ?? null),
        [resetKey],
    );

    const isFirstRender = useRef(true);
    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }
        setPage(1);
    }, [debouncedSearch, perPage, sort, filterSignature]);

    const tableProps = useMemo<DataTableStateProps>(
        () => ({
            search,
            onSearchChange: setSearch,
            perPage,
            onPerPageChange: setPerPage,
            hiddenColumns,
            onHiddenColumnsChange: setHiddenColumns,
            sort,
            onSort: changeSort,
            onPageChange: (value: number | null) => setPage(value ?? 1),
        }),
        [
            search,
            perPage,
            setPerPage,
            hiddenColumns,
            setHiddenColumns,
            sort,
            changeSort,
        ],
    );

    return {
        search,
        setSearch,
        debouncedSearch,
        page,
        setPage,
        perPage,
        setPerPage,
        hiddenColumns,
        setHiddenColumns,
        sort,
        changeSort,
        tableProps,
    };
}
