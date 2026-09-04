import type { TableColumn } from './TableHead';
import TableRow from './TableRow';
import EmptyState from './EmptyState';

export interface TableBodyProps<T extends { id: string | number } = { id: string | number }> {
    rows?: T[];
    columns: TableColumn<T>[];
    serialStart?: number;
    showSerial?: boolean;
    onRowClick?: (row: T) => void;
    striped?: boolean;
    loading?: boolean;
    search?: string;
    onClearSearch?: () => void;
}

export default function TableBody<T extends { id: string | number } = { id: string | number }>({
    rows = [],
    columns,
    serialStart = 1,
    showSerial = true,
    onRowClick,
    striped = false,
    loading = false,
    search,
    onClearSearch,
}: TableBodyProps<T>) {
    const colSpan = columns.length + (showSerial ? 1 : 0);

    if (rows.length === 0) {
        return (
            <tbody>
                <tr>
                    <td colSpan={colSpan} className="px-4 py-12 text-center">
                        {!loading && (
                            <EmptyState
                                search={search}
                                onClearSearch={onClearSearch}
                            />
                        )}
                    </td>
                </tr>
            </tbody>
        );
    }

    return (
        <tbody className="divide-y divide-[#ECECEC] bg-white">
            {rows.map((row, index) => (
                <TableRow
                    key={row.id}
                    row={row}
                    columns={columns}
                    serial={serialStart + index}
                    showSerial={showSerial}
                    striped={striped && index % 2 === 1}
                    onRowClick={onRowClick}
                />
            ))}
        </tbody>
    );
}
