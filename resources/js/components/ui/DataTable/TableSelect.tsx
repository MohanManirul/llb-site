import { SelectHTMLAttributes } from 'react';
import { ChevronDownIcon } from '@heroicons/react/24/outline';
import { ControlSize, controlSizeClasses, fieldStateClasses } from '../tokens';

export interface TableSelectProps
    extends Omit<SelectHTMLAttributes<HTMLSelectElement>, 'size'> {
    size?: ControlSize;
    className?: string;
}

export default function TableSelect({
    size = 'md',
    className = '',
    children,
    ...props
}: TableSelectProps) {
    return (
        <div className="relative">
            <select
                className={
                    'w-full appearance-none rounded-lg border bg-white pl-3 pr-9 text-gray-700 transition ' +
                    'disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500 ' +
                    `${controlSizeClasses[size]} ${fieldStateClasses()} ${className}`
                }
                {...props}
            >
                {children}
            </select>

            <ChevronDownIcon className="pointer-events-none absolute inset-y-0 right-2.5 my-auto h-4 w-4 text-gray-400" />
        </div>
    );
}
