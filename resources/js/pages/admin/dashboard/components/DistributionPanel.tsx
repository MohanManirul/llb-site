import type { DistributionSlice } from '../types';

interface DistributionPanelProps {
    title: string;
    subtitle?: string;
    slices: DistributionSlice[];
}

export default function DistributionPanel({
    title,
    subtitle,
    slices,
}: DistributionPanelProps) {
    const total = slices.reduce((sum, slice) => sum + slice.count, 0);

    return (
        <div className="flex h-full flex-col rounded-card border border-hairline bg-white shadow-sm">
            <div className="flex items-baseline justify-between border-b border-gray-100 px-5 py-4">
                <div>
                    <h3 className="text-md font-semibold text-gray-800">
                        {title}
                    </h3>
                    {subtitle && (
                        <p className="text-sm text-gray-500">{subtitle}</p>
                    )}
                </div>
                <span className="text-sm text-gray-500">{total}</span>
            </div>

            {slices.length === 0 ? (
                <p className="flex-1 px-5 py-8 text-center text-sm text-gray-500">
                    Nothing to show yet.
                </p>
            ) : (
                <ul className="flex-1 space-y-3 px-5 py-4">
                    {slices.map((slice) => {
                        const pct =
                            total === 0
                                ? 0
                                : Math.round((slice.count / total) * 100);

                        return (
                            <li key={slice.key}>
                                <div className="mb-1 flex items-center justify-between gap-2 text-sm">
                                    <span className="flex min-w-0 items-center gap-2">
                                        <span
                                            className="h-2.5 w-2.5 shrink-0 rounded-full"
                                            style={{
                                                backgroundColor: slice.color,
                                            }}
                                        />
                                        <span
                                            className="truncate text-gray-700"
                                            title={slice.label}
                                        >
                                            {slice.label}
                                        </span>
                                    </span>
                                    <span className="shrink-0 text-gray-500">
                                        {slice.count}
                                        <span className="ml-1 text-xs text-gray-400">
                                            {pct}%
                                        </span>
                                    </span>
                                </div>
                                <div className="h-1.5 overflow-hidden rounded-full bg-gray-100">
                                    <div
                                        className="h-full rounded-full"
                                        style={{
                                            width: `${pct}%`,
                                            backgroundColor: slice.color,
                                        }}
                                    />
                                </div>
                            </li>
                        );
                    })}
                </ul>
            )}
        </div>
    );
}
