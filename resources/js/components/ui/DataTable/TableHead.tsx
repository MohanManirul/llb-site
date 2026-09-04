import { ReactNode } from 'react';
import {
    ChevronUpIcon,
    ChevronDownIcon,
    ChevronUpDownIcon,
} from '@heroicons/react/20/solid';

export interface TableColumn<T = unknown> {
    key: string;
    header: ReactNode;
    headerAside?: ReactNode;
    className?: string;
    sortable?: boolean;
    hideable?: boolean;
    render?: (row: T) => ReactNode;
}

export interface TableSort {
    column?: string;
    direction?: 'asc' | 'desc';
}

export interface TableHeadProps<T = unknown> {
    columns: TableColumn<T>[];
    sort?: TableSort;
    onSort?: (key: string) => void;
    showSerial?: boolean;
    leadingRow?: ReactNode;
}

function SortIcon({ active, direction }: { active: boolean; direction?: 'asc' | 'desc' }) {
    if (active) {
        return direction === 'asc' ? (
            <ChevronUpIcon className="h-4 w-4 text-gray-700" aria-hidden="true" />
        ) : (
            <ChevronDownIcon className="h-4 w-4 text-gray-700" aria-hidden="true" />
        );
    }
    return (
        <ChevronUpDownIcon
            className="h-4 w-4 text-gray-300 group-hover:text-gray-400"
            aria-hidden="true"
        />
    );
}

export default function TableHead<T = unknown>({
    columns,
    sort,
    onSort,
    showSerial = true,
    leadingRow,
}: TableHeadProps<T>) {
    const baseTh =
        'whitespace-nowrap px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500';

    return (
        <thead className="sticky top-0 z-10 bg-sidebar text-[13px] font-semibold text-black">
            {leadingRow && (
                <tr className="bg-brand-accent/5">
                    <td
                        colSpan={columns.length + (showSerial ? 1 : 0)}
                        className="px-6 py-2"
                    >
                        {leadingRow}
                    </td>
                </tr>
            )}

            <tr>
                {showSerial && (
                    <th scope="col" className={baseTh + ' w-16'}>
                        SL
                    </th>
                )}

                {columns.map((col) => {
                    const isActive = sort?.column === col.key;

                    return (
                        <th
                            key={col.key}
                            scope="col"
                            aria-sort={
                                col.sortable
                                    ? isActive
                                        ? sort?.direction === 'asc'
                                            ? 'ascending'
                                            : 'descending'
                                        : 'none'
                                    : undefined
                            }
                            className={baseTh + ' ' + (col.className ?? '')}
                        >
                            {col.sortable ? (
                                <span className="inline-flex items-center gap-1">
                                    <button
                                        type="button"
                                        onClick={() => onSort?.(col.key)}
                                        className="group inline-flex items-center gap-1 rounded uppercase tracking-wider transition hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-brand-accent/30"
                                    >
                                        {col.header}
                                        <SortIcon
                                            active={isActive}
                                            direction={sort?.direction}
                                        />
                                    </button>

                                    {col.headerAside}
                                </span>
                            ) : col.headerAside ? (
                                <span className="inline-flex items-center gap-1">
                                    {col.header}
                                    {col.headerAside}
                                </span>
                            ) : (
                                col.header
                            )}
                        </th>
                    );
                })}
            </tr>
        </thead>
    );
}
