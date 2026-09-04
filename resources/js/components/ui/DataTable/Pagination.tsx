import { ReactNode } from 'react';
import { Link } from '@inertiajs/react';
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/react/24/outline';

export interface PaginationLinks {
    first?: string | null;
    last?: string | null;
    prev?: string | null;
    next?: string | null;
}

export interface PaginationProps {
    links?: PaginationLinks;
    onPageChange?: (page: number | null) => void;
}

const BOX =
    'flex h-7 w-7 items-center justify-center rounded-lg border text-[13px] font-medium transition ' +
    'focus:outline-none focus:ring-2 focus:ring-brand-accent/30';

const ENTITIES: Record<string, string> = {
    '&laquo;': '«',
    '&raquo;': '»',
    '&hellip;': '…',
    '&nbsp;': ' ',
    '&amp;': '&',
};

function decodeLabel(label: string): string {
    return label.replace(
        /&laquo;|&raquo;|&hellip;|&nbsp;|&amp;/g,
        (entity) => ENTITIES[entity] ?? entity,
    );
}

function pageFromUrl(url?: string | null): number | null {
    if (!url) return null;
    const page = new URL(url, window.location.origin).searchParams.get('page');
    return page ? Number(page) : null;
}

interface ArrowButtonProps {
    url: string | null;
    children?: ReactNode;
    label: string;
    onPageChange?: (page: number | null) => void;
}

function ArrowButton({ url, children, label, onPageChange }: ArrowButtonProps) {
    const classes =
        BOX +
        (url
            ? ' cursor-pointer border-gray-300 text-gray-600 hover:bg-gray-50'
            : ' cursor-not-allowed border-gray-200 bg-gray-50 text-gray-300');

    if (!url) {
        return (
            <span className={classes} aria-disabled="true" aria-label={label}>
                {children}
            </span>
        );
    }

    if (onPageChange) {
        return (
            <button
                type="button"
                onClick={() => onPageChange(pageFromUrl(url))}
                className={classes}
                aria-label={label}
            >
                {children}
            </button>
        );
    }

    return (
        <Link
            href={url}
            preserveScroll
            preserveState
            className={classes}
            aria-label={label}
        >
            {children}
        </Link>
    );
}

export default function Pagination({ links, onPageChange }: PaginationProps) {
    if (!links?.prev && !links?.next) return null;

    return (
        <nav
            className="flex items-center gap-2.5"
            role="navigation"
            aria-label="Pagination"
        >
            <ArrowButton
                url={links.prev ?? null}
                label="Previous page"
                onPageChange={onPageChange}
            >
                <ChevronLeftIcon className="h-3.5 w-3.5" />
            </ArrowButton>

            <ArrowButton
                url={links.next ?? null}
                label="Next page"
                onPageChange={onPageChange}
            >
                <ChevronRightIcon className="h-3.5 w-3.5" />
            </ArrowButton>
        </nav>
    );
}
