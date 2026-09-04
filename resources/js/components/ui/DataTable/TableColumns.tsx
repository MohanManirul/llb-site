import { ChevronDownIcon } from '@heroicons/react/20/solid';
import Checkbox from '../Checkbox';
import Popover from '../Popover';
import type { TableColumn } from './TableHead';

export interface TableColumnsProps<T = unknown> {
    columns?: TableColumn<T>[];
    hidden?: string[];
    onChange?: (hiddenKeys: string[]) => void;
}

export default function TableColumns<T = unknown>({
    columns = [],
    hidden = [],
    onChange,
}: TableColumnsProps<T>) {
    const toggleable = columns.filter((col) => col.hideable !== false);

    if (toggleable.length === 0) return null;

    const visibleCount = toggleable.filter((col) => !hidden.includes(col.key)).length;
    const allVisible = visibleCount === toggleable.length;
    const someVisible = visibleCount > 0 && !allVisible;

    function toggleAll() {
        onChange?.(allVisible ? toggleable.map((col) => col.key) : []);
    }

    function toggleColumn(key: string) {
        onChange?.(
            hidden.includes(key)
                ? hidden.filter((columnKey) => columnKey !== key)
                : [...hidden, key],
        );
    }

    return (
        <Popover
            label="Columns"
            icon={<ChevronDownIcon className="-mr-1 h-5 w-5" aria-hidden="true" />}
            align="right"
            panelClassName="max-h-80 w-56 overflow-y-auto py-2"
        >
            <div className="px-4 py-1.5">
                <Checkbox
                    label="All Columns"
                    checked={allVisible}
                    indeterminate={someVisible}
                    onChange={toggleAll}
                />
            </div>

            {toggleable.map((col) => (
                <div key={col.key} className="px-4 py-1.5">
                    <Checkbox
                        label={col.header}
                        checked={!hidden.includes(col.key)}
                        onChange={() => toggleColumn(col.key)}
                    />
                </div>
            ))}
        </Popover>
    );
}
