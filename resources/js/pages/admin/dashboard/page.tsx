import { useEffect, useState } from 'react';
import DashboardLayout from '@/components/common/DashboardLayout';
import PageHeader from '@/components/common/PageHeader';
import api from '@/lib/api-client';
import { flash, errorMessage } from '@/lib/flash';
import { DateRangeInput } from '@/components/ui';
import {
    UsersIcon,
    UserGroupIcon,
    BriefcaseIcon,
    CurrencyBangladeshiIcon,
    CheckCircleIcon,
    ShoppingBagIcon,
    TicketIcon,
    DocumentTextIcon,
    ArrowPathIcon,
} from '@heroicons/react/24/outline';
import type { ComponentType, ReactNode, SVGProps } from 'react';
import type { DashboardReport } from './types';
import StatCard from './components/StatCard';

const DEFAULT_RANGE = (() => {
    const to = new Date();
    const from = new Date();
    from.setDate(from.getDate() - 29);

    const iso = (date: Date) =>
        `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;

    return { from: iso(from), to: iso(to) };
})();

const ICONS: Record<string, ComponentType<SVGProps<SVGSVGElement>>> = {
    customers: UsersIcon,
    leads: UserGroupIcon,
    deals: BriefcaseIcon,
    money: CurrencyBangladeshiIcon,
    check: CheckCircleIcon,
    orders: ShoppingBagIcon,
    tickets: TicketIcon,
    invoice: DocumentTextIcon,
};

const COLORS: Record<string, string> = {
    blue: 'text-blue-600',
    indigo: 'text-indigo-600',
    purple: 'text-purple-600',
    amber: 'text-amber-600',
    green: 'text-green-600',
    red: 'text-red-600',
};

interface DashboardProps {
    reportUrl?: string;
}

export default function Dashboard({ reportUrl = '/admin/dashboard/report' }: DashboardProps) {
    const [report, setReport] = useState<DashboardReport | null>(null);
    const [loading, setLoading] = useState(true);
    const [dateFrom, setDateFrom] = useState(DEFAULT_RANGE.from);
    const [dateTo, setDateTo] = useState(DEFAULT_RANGE.to);
    const [reloadKey, setReloadKey] = useState(0);

    useEffect(() => {
        let cancelled = false;

        setLoading(true);

        api.get(reportUrl, {
            params: {
                date_from: dateFrom || undefined,
                date_to: dateTo || undefined,
            },
        })
            .then(({ data }) => {
                if (!cancelled) setReport(data.result);
            })
            .catch((error) => {
                if (!cancelled) {
                    flash.error(
                        errorMessage(error, 'Could not load the dashboard.')
                    );
                }
            })
            .finally(() => {
                if (!cancelled) setLoading(false);
            });

        return () => {
            cancelled = true;
        };
    }, [dateFrom, dateTo, reloadKey, reportUrl]);

    const { heading, cards = [] } = report ?? {};

    function changeRange(from: string, to: string) {
        setDateFrom(from);
        setDateTo(to);
    }

    if (loading && !report) {
        return (
            <>
                <PageHeader title="Dashboard" />

                <div className="flex justify-center p-10">
                    <ArrowPathIcon className="h-6 w-6 animate-spin text-gray-400" />
                </div>
            </>
        );
    }

    return (
        <>
            <div>
                <PageHeader
                    title={heading ?? 'Dashboard'}
                    action={
                        <>
                            <DateRangeInput
                                from={dateFrom}
                                to={dateTo}
                                onChange={changeRange}
                                placeholder="All time"
                                align="end"
                            />

                            <button
                                type="button"
                                onClick={() => setReloadKey((key) => key + 1)}
                                disabled={loading}
                                className="flex h-8.5 items-center justify-center gap-2 rounded-md border border-gray-200 bg-white px-3 text-[13px] font-semibold text-ink shadow-sm transition hover:bg-blue-50 disabled:opacity-60"
                            >
                                <ArrowPathIcon
                                    className={
                                        'h-4 w-4 ' + (loading ? 'animate-spin' : '')
                                    }
                                />
                                Refresh
                            </button>
                        </>
                    }
                />

                {cards.length > 0 && (
                    <div
                        className={
                            'mt-5 grid grid-cols-2 gap-4 rounded-2xl bg-white p-5 transition-opacity lg:grid-cols-4 ' +
                            (loading ? 'opacity-60' : 'opacity-100')
                        }
                    >
                        {cards.map((card) => {
                            const CardIcon =
                                ICONS[card.icon ?? ''] ?? CurrencyBangladeshiIcon;
                            return (
                                <StatCard
                                    key={card.label}
                                    label={card.label}
                                    value={card.value}
                                    icon={CardIcon}
                                    tone={COLORS[card.color ?? ''] ?? 'text-gray-600'}
                                />
                            );
                        })}
                    </div>
                )}
            </div>
        </>
    );
}

Dashboard.layout = (page: ReactNode) => <DashboardLayout>{page}</DashboardLayout>;
