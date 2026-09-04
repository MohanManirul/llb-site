import { TrashIcon } from '@heroicons/react/24/outline';

export interface DeleteButtonProps {
    onDelete: () => void;
    label?: string;
    disabled?: boolean;
}

export default function DeleteButton({
    onDelete,
    label = 'Delete',
    disabled = false,
}: DeleteButtonProps) {
    return (
        <button
            type="button"
            disabled={disabled}
            aria-label={label}
            onClick={(event) => {
                event.stopPropagation();
                onDelete();
            }}
            className="inline-flex items-center gap-1 text-red-600 hover:text-red-800 disabled:cursor-not-allowed disabled:text-gray-300"
        >
            <TrashIcon className="h-4 w-4" />
            {label}
        </button>
    );
}
