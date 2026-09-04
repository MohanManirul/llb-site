import { Link } from '@inertiajs/react';
import { ChevronRightIcon } from '@heroicons/react/24/outline';
import type { TopProjectRow } from '../types';

interface TopProjectsPanelProps {
    title: string;
    subtitle: string;
    rows: TopProjectRow[];
    viewAllHref: string;
    tone: 'risk' | 'performing';
}

export default function TopProjectsPanel({
    title,
    subtitle,
    rows,
    viewAllHref,
    tone,
}: TopProjectsPanelProps) {
    const bar = tone === 'risk' ? 'bg-red-500' : 'bg-emerald-500';
    const pill =
        tone === 'risk'
            ? 'bg-red-50 text-red-700 ring-red-600/20'
            : 'bg-emerald-50 text-emerald-700 ring-emerald-600/20';

    return (
        <div className="flex h-full flex-col rounded-card border border-hairline bg-white shadow-sm">
            <div className="flex items-start justify-between gap-3 border-b border-gray-100 px-5 py-4">
                <div className="min-w-0">
                    <h3 className="text-md font-semibold text-ink">{title}</h3>
                    <p className="text-sm text-ink-muted">{subtitle}</p>
                </div>

                <Link
                    href={viewAllHref}
                    className="shrink-0 rounded-chip border border-gray-300 px-2.5 py-1 text-[12px] font-medium text-ink-soft transition hover:bg-gray-50"
                >
                    View all
                </Link>
            </div>

            {rows.length === 0 ? (
                <p className="flex-1 px-5 py-8 text-center text-sm text-ink-muted">
                    No projects with a target yet.
                </p>
            ) : (
                <ul className="flex-1 divide-y divide-gray-100">
                    {rows.map((row) => (
                        <li key={row.id}>
                            <Link
                                href={row.href}
                                className="flex items-center gap-3 px-5 py-3 transition hover:bg-gray-50"
                            >
                                <span className="min-w-0 flex-1">
                                    <span
                                        className="block truncate text-sm text-ink"
                                        title={row.name}
                                    >
                                        {row.name}
                                    </span>
                                    <span
                                        className="block truncate text-xs text-ink-muted"
                                        title={row.client ?? undefined}
                                    >
                                        {row.achieved} of {row.target}
                                    </span>
                                    <span className="mt-1.5 block h-1.5 overflow-hidden rounded-full bg-gray-100">
                                        <span
                                            className={'block h-full rounded-full ' + bar}
                                            style={{
                                                width: `${Math.min(100, row.percent)}%`,
                                            }}
                                        />
                                    </span>
                                </span>

                                <span
                                    className={
                                        'shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold ring-1 ring-inset ' +
                                        pill
                                    }
                                >
                                    {row.percent}%
                                </span>

                                <ChevronRightIcon className="h-4 w-4 shrink-0 text-gray-400" />
                            </Link>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
