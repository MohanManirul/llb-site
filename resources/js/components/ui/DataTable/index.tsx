import { ReactNode } from 'react';
import { ArrowPathIcon } from '@heroicons/react/24/outline';
import TableSelect from './TableSelect';
import TableColumns from './TableColumns';
import TableSortControl, { type TableSortOption } from './TableSort';
import TableToolbar from './TableToolbar';
import TableTabs, { type TableTab } from './TableTabs';
import TableHead, { TableColumn, TableSort } from './TableHead';
import TableBody from './TableBody';
import Pagination, { PaginationLinks } from './Pagination';

const PER_PAGE_OPTIONS = [10, 25, 50, 100];

export interface DataTablePagination {
    from?: number;
    to?: number;
    links?: PaginationLinks;
}

export interface DataTableProps<T extends { id: string | number } = { id: string | number }> {
    title?: ReactNode;
    columns: TableColumn<T>[];
    rows?: T[];
    pagination?: DataTablePagination;
    search?: string;
    onSearchChange?: (value: string) => void;
    searchLabel?: string;
    perPage?: number;
    onPerPageChange?: (value: number) => void;
    tabs?: TableTab[];
    activeTab?: string;
    onTabChange?: (value: string) => void;
    tabsInCard?: boolean;
    filters?: ReactNode;
    headerAction?: ReactNode;
    bulkActions?: ReactNode;
    showSerial?: boolean;
    loading?: boolean;
    striped?: boolean;
    sort?: TableSort;
    sortOptions?: TableSortOption[];
    onSort?: (key: string, direction?: 'asc' | 'desc') => void;
    onRowClick?: (row: T) => void;
    onPageChange?: (page: number | null) => void;
    hiddenColumns?: string[];
    onHiddenColumnsChange?: (keys: string[]) => void;
}

export default function DataTable<T extends { id: string | number } = { id: string | number }>({
    title,
    columns,
    rows = [],
    pagination,
    search = '',
    onSearchChange,
    searchLabel,
    perPage = 10,
    onPerPageChange,
    tabs,
    activeTab,
    onTabChange,
    tabsInCard = false,
    filters,
    headerAction,
    bulkActions,
    showSerial = true,
    loading = false,
    striped = true,
    sort,
    sortOptions,
    onSort,
    onRowClick,
    onPageChange,
    hiddenColumns = [],
    onHiddenColumnsChange,
}: DataTableProps<T>) {
    const visibleColumns = columns.filter(
        (col) => !hiddenColumns.includes(col.key),
    );

    const serialStart = pagination?.from ?? 1;

    const perPageControl = (
        <TableSelect
            value={perPage}
            onChange={(e) => onPerPageChange?.(Number(e.target.value))}
            aria-label="Rows per page"
            className="w-20"
        >
            {PER_PAGE_OPTIONS.map((n) => (
                <option key={n} value={n}>
                    {n}
                </option>
            ))}
        </TableSelect>
    );

    const trailingControls = (
        <>
            {sortOptions && sortOptions.length > 0 && (
                <TableSortControl
                    options={sortOptions}
                    sort={sort}
                    onSort={(key, direction) => onSort?.(key, direction)}
                />
            )}
            {onHiddenColumnsChange && (
                <TableColumns
                    columns={columns}
                    hidden={hiddenColumns}
                    onChange={onHiddenColumnsChange}
                />
            )}
            {perPageControl}
        </>
    );

    return (
        <div>
            <TableToolbar
                title={title}
                search={search}
                onSearchChange={onSearchChange}
                searchLabel={searchLabel}
                tabs={tabsInCard ? undefined : tabs}
                activeTab={activeTab}
                onTabChange={onTabChange}
                filters={filters}
                trailing={trailingControls}
                headerAction={headerAction}
            />

            <div
                className="relative rounded-table border border-gray-200 bg-white shadow-sm"
                aria-busy={loading || undefined}
            >
                {tabsInCard && tabs && (
                    <TableTabs
                        tabs={tabs}
                        activeTab={activeTab}
                        onTabChange={onTabChange}
                        className="border-b border-gray-200 px-4 py-3"
                    />
                )}

                <div className="overflow-x-auto rounded-b-table">
                    <table className="min-w-full text-[13px] font-medium text-[#434343]">
                        <TableHead
                            columns={visibleColumns}
                            sort={sort}
                            onSort={onSort}
                            showSerial={showSerial}
                            leadingRow={bulkActions}
                        />
                        <TableBody
                            rows={rows}
                            columns={visibleColumns}
                            serialStart={serialStart}
                            showSerial={showSerial}
                            striped={striped}
                            loading={loading}
                            onRowClick={onRowClick}
                            search={search}
                            onClearSearch={() => onSearchChange?.('')}
                        />
                    </table>
                </div>

                {loading && (
                    <div
                        className="absolute inset-0 flex items-center justify-center bg-white/60"
                        role="status"
                    >
                        <ArrowPathIcon
                            className="h-6 w-6 animate-spin text-gray-400"
                            aria-hidden="true"
                        />
                        <span className="sr-only">Loading</span>
                    </div>
                )}
            </div>

            <div className="mt-4 flex flex-col gap-3 text-sm text-gray-600 sm:flex-row sm:items-center sm:justify-between">
                {pagination && (
                    <span>
                        Showing {pagination.from ?? 0} to {pagination.to ?? 0}
                    </span>
                )}

                <Pagination
                    links={pagination?.links}
                    onPageChange={onPageChange}
                />
            </div>
        </div>
    );
}
