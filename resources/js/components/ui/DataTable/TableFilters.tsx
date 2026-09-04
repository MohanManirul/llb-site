import { ReactNode } from 'react';
import { FunnelIcon } from '@heroicons/react/24/outline';
import Button from '../Button';
import Popover from '../Popover';
import { badgeToneClasses } from '../tokens';

export interface TableFiltersProps {
    activeCount?: number;
    onClear?: () => void;
    children?: ReactNode;
}

export default function TableFilters({ activeCount = 0, onClear, children }: TableFiltersProps) {
    return (
        <Popover
            label="Filters"
            badge={
                activeCount > 0 ? (
                    <span
                        className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${badgeToneClasses.indigo}`}
                        aria-label={`${activeCount} active filters`}
                    >
                        {activeCount}
                    </span>
                ) : null
            }
            icon={<FunnelIcon className="-mr-1 h-5 w-5" aria-hidden="true" />}
            align="left"
            panelClassName="w-72 p-4"
        >
            <div className="space-y-4">{children}</div>

            {onClear && activeCount > 0 && (
                <Button variant="link" className="mt-4" onClick={onClear}>
                    Clear filters
                </Button>
            )}
        </Popover>
    );
}
