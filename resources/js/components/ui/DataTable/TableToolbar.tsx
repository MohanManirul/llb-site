import { ReactNode, useId } from 'react';
import { MagnifyingGlassIcon, XMarkIcon } from '@heroicons/react/24/outline';
import { controlSizeClasses, fieldStateClasses } from '../tokens';
import TableTabs, { type TableTab } from './TableTabs';

export type { TableTab };

export interface TableToolbarProps {
    title?: ReactNode;
    search?: string;
    onSearchChange?: (value: string) => void;
    searchLabel?: string;
    tabs?: TableTab[];
    activeTab?: string;
    onTabChange?: (value: string) => void;
    filters?: ReactNode;
    trailing?: ReactNode;
    headerAction?: ReactNode;
}

export default function TableToolbar({
    title,
    search = '',
    onSearchChange,
    searchLabel = 'Search',
    tabs,
    activeTab,
    onTabChange,
    filters,
    trailing,
    headerAction,
}: TableToolbarProps) {
    const searchId = useId();

    return (
        <div className="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                {title && (
                    <h2 className="text-md font-semibold text-gray-800">{title}</h2>
                )}

                <div className="relative">
                    <MagnifyingGlassIcon className="pointer-events-none absolute inset-y-0 left-3 my-auto h-4 w-4 text-gray-400" />
                    <input
                        id={searchId}
                        type="search"
                        value={search}
                        onChange={(e) => onSearchChange?.(e.target.value)}
                        placeholder="Search…"
                        aria-label={searchLabel}
                        className={
                            'w-full rounded-lg border bg-white pl-9 pr-9 text-gray-700 transition sm:w-64 ' +
                            `${controlSizeClasses.md} ${fieldStateClasses()}`
                        }
                    />
                    {search && (
                        <button
                            type="button"
                            onClick={() => onSearchChange?.('')}
                            aria-label="Clear search"
                            className="absolute inset-y-0 right-2 my-auto flex h-5 w-5 items-center justify-center rounded text-gray-400 transition hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-brand-accent/30"
                        >
                            <XMarkIcon className="h-4 w-4" />
                        </button>
                    )}
                </div>

                {tabs && (
                    <TableTabs
                        tabs={tabs}
                        activeTab={activeTab}
                        onTabChange={onTabChange}
                    />
                )}

                {filters}
            </div>

            <div className="flex items-center gap-3">
                {trailing}

                {headerAction}
            </div>
        </div>
    );
}
