import type { KeyboardEvent, MouseEvent, ReactNode } from 'react';
import type { TableColumn } from './TableHead';

export interface TableRowProps<T extends { id: string | number } = { id: string | number }> {
    row: T;
    columns: TableColumn<T>[];
    serial: number;
    showSerial?: boolean;
    onRowClick?: (row: T) => void;
    striped?: boolean;
}

export default function TableRow<T extends { id: string | number } = { id: string | number }>({
    row,
    columns,
    serial,
    showSerial = true,
    onRowClick,
    striped = false,
}: TableRowProps<T>) {
    const background = striped ? 'bg-gray-50' : '';

    function handleRowClick(event: MouseEvent<HTMLTableRowElement>) {
        if ((event.target as HTMLElement).closest('a, button, input, label')) return;
        onRowClick?.(row);
    }

    function handleRowKeyDown(event: KeyboardEvent<HTMLTableRowElement>) {
        if (event.target !== event.currentTarget) return;

        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            onRowClick?.(row);
        }
    }

    return (
        <tr
            onClick={onRowClick ? handleRowClick : undefined}
            onKeyDown={onRowClick ? handleRowKeyDown : undefined}
            tabIndex={onRowClick ? 0 : undefined}
            className={
                'transition hover:bg-[#f1f1f1] focus:outline-none focus:ring-2 focus:ring-inset focus:ring-brand-accent/30 ' +
                (onRowClick ? 'cursor-pointer ' : '') +
                background
            }
        >
            {showSerial && (
                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                    {serial}
                </td>
            )}

            {columns.map((col) => (
                <td
                    key={col.key}
                    className={
                        'whitespace-nowrap px-6 py-4 text-sm text-gray-800 ' +
                        (col.className ?? '')
                    }
                >
                    {col.render ? col.render(row) : ((row as Record<string, unknown>)[col.key] as ReactNode)}
                </td>
            ))}
        </tr>
    );
}
