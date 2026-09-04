import { useMemo, useState } from 'react';
import {
    Line,
    LineChart,
    ReferenceLine,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import { formatMoney, withCurrency } from '@/lib/format';
import type { TrendPoint } from '../types';

type SeriesKey = 'sales' | 'spend' | 'orders' | 'cpr';

const LINE_COLOR = '#33ACEE';

interface TrendCardProps {
    points: TrendPoint[];
    loading?: boolean;
}

interface ChartRow {
    date: string;
    sales: number;
    spend: number;
    orders: number;
    cpr: number;
}

function taka(value: number): string {
    return formatMoney(value);
}

function count(value: number): string {
    return Math.round(value).toLocaleString('en-US');
}

function compactTaka(value: number): string {
    if (value >= 1_000_000) return withCurrency((value / 1_000_000).toFixed(1) + 'M');
    if (value >= 1_000) return withCurrency(Math.round(value / 1_000) + 'k');

    return withCurrency(String(Math.round(value)));
}

function niceMax(value: number): number {
    if (value <= 0) return 1;

    const magnitude = 10 ** Math.floor(Math.log10(value));
    const normalized = value / magnitude;

    if (normalized <= 1) return magnitude;
    if (normalized <= 2) return 2 * magnitude;
    if (normalized <= 5) return 5 * magnitude;

    return 10 * magnitude;
}

function shortDate(value: string): string {
    return new Date(value).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
    });
}

function longDate(value: string): string {
    return new Date(value).toLocaleDateString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

export default function TrendCard({ points, loading = false }: TrendCardProps) {
    const [active, setActive] = useState<SeriesKey>('sales');

    const rows = useMemo<ChartRow[]>(
        () =>
            points.map((point) => ({
                date: point.date,
                sales: point.sales,
                spend: point.spend,
                orders: point.orders,
                cpr: point.orders > 0 ? point.spend / point.orders : 0,
            })),
        [points],
    );

    const totals = useMemo(() => {
        const sales = rows.reduce((sum, row) => sum + row.sales, 0);
        const spend = rows.reduce((sum, row) => sum + row.spend, 0);
        const orders = rows.reduce((sum, row) => sum + row.orders, 0);

        return {
            sales,
            spend,
            orders,
            cpr: orders > 0 ? spend / orders : 0,
        };
    }, [rows]);

    const isMoney = active !== 'orders';

    const stats: { key: SeriesKey; label: string; value: string }[] = [
        { key: 'sales', label: 'Total Sales', value: taka(totals.sales) },
        { key: 'spend', label: 'Ad Spend', value: taka(totals.spend) },
        { key: 'orders', label: 'Orders', value: count(totals.orders) },
        { key: 'cpr', label: 'CPR', value: taka(totals.cpr) },
    ];

    const max = niceMax(Math.max(...rows.map((row) => row[active]), 0));
    const ticks = [0, max / 2, max];

    return (
        <div className="rounded-2xl bg-white p-4">
            <div className="mb-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
                {stats.map((stat) => (
                    <button
                        key={stat.key}
                        type="button"
                        onClick={() => setActive(stat.key)}
                        className={
                            'rounded-[10px] border px-4 py-2 text-left transition ' +
                            (active === stat.key
                                ? 'border-transparent bg-[#ebebeb]'
                                : 'border-gray-200 bg-white hover:bg-gray-50')
                        }
                    >
                        <span className="mb-2 inline-block border-b-2 border-dotted border-[#BFBFBF] text-[14px] font-medium text-[#4D4D4D]">
                            {stat.label}
                        </span>
                        <p className="truncate text-[15px] font-bold text-[#313131]">
                            {stat.value}
                        </p>
                    </button>
                ))}
            </div>

            {loading ? (
                <div className="h-[220px] animate-pulse rounded-lg bg-gray-100" />
            ) : (
                <div className="h-[220px] w-full">
                    <ResponsiveContainer width="100%" height="100%">
                        <LineChart data={rows}>
                            {ticks
                                .filter((tick) => tick !== 0)
                                .map((tick) => (
                                    <ReferenceLine
                                        key={tick}
                                        y={tick}
                                        stroke="#e5e7eb"
                                    />
                                ))}

                            <ReferenceLine
                                y={0}
                                stroke={LINE_COLOR}
                                strokeWidth={1.5}
                                strokeDasharray="3 3"
                            />

                            <XAxis
                                dataKey="date"
                                scale="point"
                                axisLine={false}
                                tickLine={false}
                                tickFormatter={shortDate}
                                tick={{ fill: '#6b7280', fontSize: 12 }}
                                minTickGap={17}
                            />

                            <YAxis
                                axisLine={false}
                                tickLine={false}
                                ticks={ticks}
                                domain={[0, max]}
                                width={56}
                                tickMargin={7}
                                tick={{ fill: '#6b7280', fontSize: 12 }}
                                tickFormatter={(value: number) =>
                                    isMoney ? compactTaka(value) : count(value)
                                }
                            />

                            <Tooltip
                                cursor={{ stroke: '#d1d5db', strokeWidth: 1 }}
                                content={({ active: shown, payload, label }) => {
                                    if (!shown || !payload?.length) return null;

                                    const value = payload[0].value as number;

                                    return (
                                        <div className="min-w-36 rounded-lg border border-gray-200 bg-white px-4 py-3 text-xs shadow-lg">
                                            <p className="mb-1 text-gray-500">
                                                {longDate(String(label))}
                                            </p>
                                            <p className="font-semibold text-gray-800">
                                                {isMoney
                                                    ? taka(value)
                                                    : count(value)}
                                            </p>
                                        </div>
                                    );
                                }}
                            />

                            <Line
                                type="monotone"
                                dataKey={active}
                                stroke={LINE_COLOR}
                                strokeWidth={2.5}
                                dot={false}
                                activeDot={{
                                    r: 5,
                                    fill: LINE_COLOR,
                                    stroke: '#ffffff',
                                    strokeWidth: 2,
                                }}
                            />
                        </LineChart>
                    </ResponsiveContainer>
                </div>
            )}
        </div>
    );
}
