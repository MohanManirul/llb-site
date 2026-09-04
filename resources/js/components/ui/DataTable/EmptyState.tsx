import { MagnifyingGlassIcon, InboxIcon } from '@heroicons/react/24/outline';
import Button from '../Button';

export interface EmptyStateProps {
    search?: string;
    onClearSearch?: () => void;
}

export default function EmptyState({ search, onClearSearch }: EmptyStateProps) {
    if (search) {
        return (
            <div className="flex flex-col items-center gap-2 text-gray-500">
                <MagnifyingGlassIcon className="h-8 w-8 text-gray-300" aria-hidden="true" />
                <p className="text-sm">
                    No results for “<span className="font-medium">{search}</span>”.
                </p>
                {onClearSearch && (
                    <Button variant="link" onClick={onClearSearch}>
                        Clear search
                    </Button>
                )}
            </div>
        );
    }

    return (
        <div className="flex flex-col items-center gap-2 text-gray-500">
            <InboxIcon className="h-8 w-8 text-gray-300" aria-hidden="true" />
            <p className="text-sm">No records found.</p>
        </div>
    );
}
