import { ReactNode, useId } from "react";
import {
    ArrowDownIcon,
    ArrowUpIcon,
    ChevronDownIcon,
} from "@heroicons/react/20/solid";
import Popover from "../Popover";
import type { TableSort as TableSortState } from "./TableHead";

export interface TableSortOption {
    key: string;
    label: ReactNode;
    ascLabel?: string;
    descLabel?: string;
}

export interface TableSortProps {
    options?: TableSortOption[];
    sort?: TableSortState;
    onSort?: (key: string, direction: "asc" | "desc") => void;
}

interface DirectionRow {
    value: "asc" | "desc";
    label: string;
    icon: ReactNode;
}

export default function TableSort({
    options = [],
    sort,
    onSort,
}: TableSortProps) {
    const groupName = useId();

    if (options.length === 0) return null;

    const active = options.find((option) => option.key === sort?.column);
    const direction = sort?.direction ?? "desc";

    const directions: DirectionRow[] = [
        {
            value: "asc",
            label: active?.ascLabel ?? "Ascending",
            icon: (
                <ArrowUpIcon
                    className="h-4 w-4 text-gray-500"
                    aria-hidden="true"
                />
            ),
        },
        {
            value: "desc",
            label: active?.descLabel ?? "Descending",
            icon: (
                <ArrowDownIcon
                    className="h-4 w-4 text-gray-500"
                    aria-hidden="true"
                />
            ),
        },
    ];

    return (
        <Popover
            label="Sort by"
            icon={
                <ChevronDownIcon className="-mr-1 h-5 w-5" aria-hidden="true" />
            }
            align="right"
            panelClassName="w-56 py-2"
        >
            {(close) => (
                <>
                    <div role="radiogroup" aria-label="Sort by">
                        {options.map((option) => (
                            <label
                                key={option.key}
                                className="flex cursor-pointer items-center gap-2 px-4 py-1.5 text-sm text-gray-700 transition hover:bg-gray-50"
                            >
                                <input
                                    type="radio"
                                    name={groupName}
                                    checked={sort?.column === option.key}
                                    onChange={() => {
                                        onSort?.(option.key, direction);
                                        close();
                                    }}
                                    className="h-4 w-4 border-gray-300 text-brand-accent focus:ring-brand-accent/30"
                                />
                                {option.label}
                            </label>
                        ))}
                    </div>

                    <hr className="my-2 border-gray-200" />

                    {directions.map((item) => (
                        <button
                            key={item.value}
                            type="button"
                            aria-pressed={direction === item.value}
                            onClick={() => {
                                onSort?.(
                                    sort?.column || options[0].key,
                                    item.value,
                                );
                                close();
                            }}
                            className={
                                "flex w-full items-center gap-2 px-4 py-1.5 text-left text-sm text-gray-700 transition hover:bg-gray-50 " +
                                (direction === item.value ? "bg-gray-100" : "")
                            }
                        >
                            {item.icon}
                            {item.label}
                        </button>
                    ))}
                </>
            )}
        </Popover>
    );
}
