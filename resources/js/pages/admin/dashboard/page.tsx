import { Fragment, useEffect, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import DashboardLayout from '@/components/common/DashboardLayout';
import PageHeader from '@/components/common/PageHeader';
import api from '@/lib/api-client';
import { flash, errorMessage } from '@/lib/flash';
import { DateRangeInput } from '@/components/ui';
import ClientProjectsTable from './components/ClientProjectsTable';
import EmployeeProjectsTable from './components/EmployeeProjectsTable';
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
    ChevronRightIcon,
    ChevronDownIcon,
} from '@heroicons/react/24/outline';
import type { ComponentType, ReactNode, SVGProps } from 'react';
import type { DashboardReport, TeamProjectRow } from './types';
import TopProjectsPanel from './components/TopProjectsPanel';
import StatCard from './components/StatCard';
import TrendCard from './components/TrendCard';
import DistributionPanel from './components/DistributionPanel';

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

function openRow(href?: string) {
    if (href) {
        router.visit(href);
    }
}

function roleBadge(role: string) {
    return role === 'leader'
        ? 'bg-indigo-100 text-indigo-700'
        : 'bg-gray-100 text-gray-600';
}

interface MemberProjectsProps {
    projects: TeamProjectRow[];
    showClient: boolean;
}

function MemberProjects({ projects, showClient }: MemberProjectsProps) {
    if (projects.length === 0) {
        return (
            <p className="px-6 py-4 text-sm text-gray-500">
                No projects assigned to this member yet.
            </p>
        );
    }

    return (
        <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-sidebar">
                <tr>
                    <th className="w-16 px-6 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                        SL
                    </th>
                    {[
                        "Project",
                        showClient && "Client",
                        "Package",
                        "Due",
                        "Status",
                        "Health",
                        "Ends",
                    ]
                        .filter(Boolean)
                        .map((header) => (
                            <th
                                key={header as string}
                                className="px-6 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                            >
                                {header}
                            </th>
                        ))}
                    <th className="w-10 px-4 py-2">
                        <span className="sr-only">Open</span>
                    </th>
                </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
                {projects.map((row, index) => (
                    <tr
                        key={row.id}
                        onClick={() => openRow(row.href)}
                        onKeyDown={(e) => {
                            if (
                                row.href &&
                                (e.key === "Enter" || e.key === " ")
                            ) {
                                e.preventDefault();
                                openRow(row.href);
                            }
                        }}
                        role="link"
                        tabIndex={0}
                        className="cursor-pointer transition even:bg-gray-50 hover:bg-indigo-50/80 focus:bg-indigo-50 focus:outline-none"
                    >
                        <td className="whitespace-nowrap px-6 py-3 text-sm text-gray-500">
                            {index + 1}
                        </td>
                        <td className="whitespace-nowrap px-6 py-3 text-sm font-medium text-gray-800">
                            {row.project}
                        </td>
                        {showClient && (
                            <td className="whitespace-nowrap px-6 py-3 text-sm text-gray-600">
                                {row.client}
                            </td>
                        )}
                        <td className="whitespace-nowrap px-6 py-3 text-sm text-gray-600">
                            {row.package}
                        </td>
                        <td className="whitespace-nowrap px-6 py-3 text-sm text-gray-600">
                            {row.due}
                        </td>
                        <td className="whitespace-nowrap px-6 py-3 text-sm text-gray-600">
                            {row.status}
                        </td>
                        <td className="whitespace-nowrap px-6 py-3 text-sm text-gray-600">
                            {row.health}
                        </td>
                        <td className="whitespace-nowrap px-6 py-3 text-sm text-gray-600">
                            {row.ends}
                        </td>
                        <td className="px-4 py-3 text-gray-400">
                            <ChevronRightIcon className="h-4 w-4" />
                        </td>
                    </tr>
                ))}
            </tbody>
        </table>
    );
}

interface DashboardProps {
    reportUrl?: string;
}

export default function Dashboard({ reportUrl = '/admin/dashboard/report' }: DashboardProps) {
    const [report, setReport] = useState<DashboardReport | null>(null);
    const [loading, setLoading] = useState(true);
    const [dateFrom, setDateFrom] = useState(DEFAULT_RANGE.from);
    const [dateTo, setDateTo] = useState(DEFAULT_RANGE.to);
    const [reloadKey, setReloadKey] = useState(0);
    const [openMember, setOpenMember] = useState<string | null>(null);

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

    const {
        heading,
        cards = [],
        recent,
        teams = [],
        finance,
        top_projects,
        distributions,
        trend = [],
    } = report ?? {};
    const { auth } = usePage().props;
    const roles = auth?.user?.roles ?? [];
    const canViewClient =
        roles.includes('super-admin') ||
        (auth?.user?.permissions ?? []).includes('view project client');
    const isClient = roles.includes('client');
    const isEmployee =
        roles.includes('employee') || roles.includes('team-leader');
    const hasClickableRows = recent?.rows?.some((row) => row.href);
    const isTeamLeader = teams.length > 0;

    function changeRange(from: string, to: string) {
        setDateFrom(from);
        setDateTo(to);
        setOpenMember(null);
    }

    if (loading && !report) {
        return (
            <>
            <PageHeader
                title="Dashboard" />

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

                <TrendCard points={trend} loading={loading} />

                <div
                    className={
                        'mt-5 grid grid-cols-2 gap-4 rounded-2xl bg-white p-5 transition-opacity lg:grid-cols-4 ' +
                        (loading ? 'opacity-60' : 'opacity-100')
                    }
                >
                    {[...cards, ...(finance ?? [])].map((card) => {
                        const CardIcon = ICONS[card.icon ?? ''] ?? CurrencyBangladeshiIcon;
                        return (
                            <StatCard
                                key={card.label}
                                label={card.label}
                                value={card.value}
                                icon={CardIcon}
                                tone={COLORS[card.color ?? ''] ?? COLORS.blue}
                            />
                        );
                    })}
                </div>

                {(top_projects || distributions) && (
                    <div className="mt-6 grid grid-cols-1 gap-5 lg:grid-cols-3">
                        {top_projects && (
                            <TopProjectsPanel
                                title="Top risk projects"
                                subtitle="Furthest behind this month's target."
                                rows={top_projects.risk}
                                viewAllHref="/admin/projects?performance=risk"
                                tone="risk"
                            />
                        )}
                        {top_projects && (
                            <TopProjectsPanel
                                title="Top performing projects"
                                subtitle="Furthest ahead of this month's target."
                                rows={top_projects.performing}
                                viewAllHref="/admin/projects?performance=performing"
                                tone="performing"
                            />
                        )}
                        {distributions && (
                            <DistributionPanel
                                title="Business status"
                                slices={distributions.business_status}
                            />
                        )}
                    </div>
                )}

                {isTeamLeader && (
                    <>
                        <EmployeeProjectsTable
                            scope="led"
                            title="Projects I lead"
                            subtitle="Every project of the teams I lead."
                            storageKey="employee-projects-led"
                            from={dateFrom}
                            to={dateTo}
                        />

                        <EmployeeProjectsTable
                            scope="assigned"
                            title="Projects assigned to me"
                            subtitle="Projects where I am the assigned member."
                            storageKey="employee-projects-assigned"
                            from={dateFrom}
                            to={dateTo}
                        />
                    </>
                )}

                {isTeamLeader &&
                    teams.map(({ team, members = [], unassigned_projects = [] }) => (
                        <div key={team.id} className="mt-6 space-y-4">
                            <h3 className="text-md font-semibold text-gray-800">
                                Team — {team.name}
                            </h3>

                            <div className="overflow-hidden rounded-card border border-hairline bg-white shadow-sm">
                                <div className="border-b border-gray-200 px-4 py-3">
                                    <h4 className="text-sm font-semibold text-gray-800">
                                        Team members
                                    </h4>
                                    <p className="mt-0.5 text-xs text-gray-500">
                                        Click a member to see the projects they are
                                        working on.
                                    </p>
                                </div>
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-sidebar">
                                            <tr>
                                                <th className="w-16 px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                                    SL
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                                    Name
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                                    Designation
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                                    Role
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                                    Contact
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                                    Projects
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-200 bg-white">
                                            {members.length === 0 && (
                                                <tr>
                                                    <td
                                                        colSpan={6}
                                                        className="px-6 py-8 text-center text-sm text-gray-500"
                                                    >
                                                        No members on this team.
                                                    </td>
                                                </tr>
                                            )}
                                            {members.map((member, index) => {
                                                const key = `${team.id}-${member.id}`;
                                                const expanded = openMember === key;

                                                return (
                                                    <Fragment key={key}>
                                                        <tr
                                                            onClick={() =>
                                                                setOpenMember(
                                                                    expanded ? null : key,
                                                                )
                                                            }
                                                            onKeyDown={(e) => {
                                                                if (
                                                                    e.key === 'Enter' ||
                                                                    e.key === ' '
                                                                ) {
                                                                    e.preventDefault();
                                                                    setOpenMember(
                                                                        expanded ? null : key,
                                                                    );
                                                                }
                                                            }}
                                                            role="button"
                                                            tabIndex={0}
                                                            aria-expanded={expanded}
                                                            className="cursor-pointer transition hover:bg-gray-50 focus:bg-gray-50 focus:outline-none"
                                                        >
                                                            <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                                                {index + 1}
                                                            </td>
                                                            <td className="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-800">
                                                                {member.name}
                                                            </td>
                                                            <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                                                {member.designation ?? '—'}
                                                            </td>
                                                            <td className="whitespace-nowrap px-6 py-4 text-sm">
                                                                <span
                                                                    className={
                                                                        'inline-flex rounded-full px-2 py-1 text-xs font-medium capitalize ' +
                                                                        roleBadge(member.role)
                                                                    }
                                                                >
                                                                    {member.role}
                                                                </span>
                                                            </td>
                                                            <td className="px-6 py-4 text-sm text-gray-600">
                                                                <div className="flex flex-col">
                                                                    <span>
                                                                        {member.email ?? '—'}
                                                                    </span>
                                                                    {member.phone && (
                                                                        <span className="text-xs text-gray-500">
                                                                            {member.phone}
                                                                        </span>
                                                                    )}
                                                                </div>
                                                            </td>
                                                            <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                                                <span className="inline-flex items-center gap-1">
                                                                    <span className="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">
                                                                        {member.projects_count ?? 0}
                                                                    </span>
                                                                    <ChevronDownIcon
                                                                        className={
                                                                            'h-4 w-4 text-gray-400 transition ' +
                                                                            (expanded
                                                                                ? 'rotate-180'
                                                                                : '')
                                                                        }
                                                                    />
                                                                </span>
                                                            </td>
                                                        </tr>

                                                        {expanded && (
                                                            <tr>
                                                                <td
                                                                    colSpan={6}
                                                                    className="bg-gray-50 p-0"
                                                                >
                                                                    <MemberProjects
                                                                        projects={
                                                                            member.projects ?? []
                                                                        }
                                                                        showClient={
                                                                            canViewClient
                                                                        }
                                                                    />
                                                                </td>
                                                            </tr>
                                                        )}
                                                    </Fragment>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div className="overflow-hidden rounded-card border border-hairline bg-white shadow-sm">
                                <div className="border-b border-gray-200 px-4 py-3">
                                    <h4 className="text-sm font-semibold text-gray-800">
                                        Unassigned projects
                                    </h4>
                                </div>
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-sidebar">
                                            <tr>
                                                <th className="w-16 px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                                    SL
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                                    Business
                                                </th>
                                                {canViewClient && (
                                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                                        Client
                                                    </th>
                                                )}
                                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                                    Package
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                                    Due
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                                    Status
                                                </th>
                                                <th className="w-10 px-4 py-3">
                                                    <span className="sr-only">Open</span>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-200 bg-white">
                                            {unassigned_projects.length === 0 && (
                                                <tr>
                                                    <td
                                                        colSpan={canViewClient ? 7 : 6}
                                                        className="px-6 py-8 text-center text-sm text-gray-500"
                                                    >
                                                        No unassigned projects on this team.
                                                    </td>
                                                </tr>
                                            )}
                                            {unassigned_projects.map((row, index) => (
                                                <tr
                                                    key={row.id}
                                                    onClick={() => openRow(row.href)}
                                                    onKeyDown={(e) => {
                                                        if (
                                                            row.href &&
                                                            (e.key === 'Enter' ||
                                                                e.key === ' ')
                                                        ) {
                                                            e.preventDefault();
                                                            openRow(row.href);
                                                        }
                                                    }}
                                                    role={row.href ? 'link' : undefined}
                                                    tabIndex={row.href ? 0 : undefined}
                                                    className="cursor-pointer transition even:bg-gray-50 hover:bg-indigo-50/80 focus:bg-indigo-50 focus:outline-none"
                                                >
                                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                                        {index + 1}
                                                    </td>
                                                    <td className="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-800">
                                                        {row.business}
                                                    </td>
                                                    {canViewClient && (
                                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                                            {row.client}
                                                        </td>
                                                    )}
                                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                                        {row.package}
                                                    </td>
                                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                                        {row.due}
                                                    </td>
                                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                                        {row.status}
                                                    </td>
                                                    <td className="px-4 py-4 text-gray-400">
                                                        <ChevronRightIcon className="h-4 w-4" />
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    ))}

                {isClient && <ClientProjectsTable from={dateFrom} to={dateTo} />}
                {isEmployee && !isTeamLeader && (
                    <EmployeeProjectsTable from={dateFrom} to={dateTo} />
                )}

                {!isClient && !isEmployee && recent && (
                    <div className="mt-6 overflow-hidden rounded-card border border-hairline bg-white shadow-sm">
                        <div className="flex items-center justify-between gap-3 border-b border-gray-200 px-4 py-3">
                            <h3 className="text-md font-semibold text-gray-800">
                                {recent.title}
                            </h3>

                            <Link
                                href="/admin/projects"
                                className="shrink-0 rounded-chip border border-gray-300 px-2.5 py-1 text-[12px] font-medium text-ink-soft transition hover:bg-gray-50"
                            >
                                View all
                            </Link>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-sidebar">
                                    <tr>
                                        <th className="w-16 whitespace-nowrap px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            SL
                                        </th>
                                        {recent.columns.map((col) => (
                                            <th
                                                key={col.key}
                                                className="whitespace-nowrap px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                            >
                                                {col.header}
                                            </th>
                                        ))}
                                        {hasClickableRows && (
                                            <th className="w-10 px-4 py-3">
                                                <span className="sr-only">Open</span>
                                            </th>
                                        )}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 bg-white">
                                    {recent.rows.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={
                                                    recent.columns.length +
                                                    1 +
                                                    (hasClickableRows ? 1 : 0)
                                                }
                                                className="px-6 py-8 text-center text-sm text-gray-500"
                                            >
                                                No projects yet.
                                            </td>
                                        </tr>
                                    )}
                                    {recent.rows.map((row, index) => (
                                        <tr
                                            key={row.id ?? index}
                                            onClick={() => openRow(row.href)}
                                            onKeyDown={(e) => {
                                                if (
                                                    row.href &&
                                                    (e.key === 'Enter' ||
                                                        e.key === ' ')
                                                ) {
                                                    e.preventDefault();
                                                    openRow(row.href);
                                                }
                                            }}
                                            role={row.href ? 'link' : undefined}
                                            tabIndex={row.href ? 0 : undefined}
                                            className={
                                                'even:bg-gray-50 ' +
                                                (row.href
                                                    ? 'cursor-pointer transition hover:bg-indigo-50/80 focus:bg-indigo-50 focus:outline-none'
                                                    : 'hover:bg-[#f1f1f1]')
                                            }
                                        >
                                            <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                                {index + 1}
                                            </td>
                                            {recent.columns.map((col) => (
                                                <td
                                                    key={col.key}
                                                    className="whitespace-nowrap px-6 py-4 text-sm text-gray-800"
                                                >
                                                    {row[col.key] as ReactNode}
                                                </td>
                                            ))}
                                            {hasClickableRows && (
                                                <td className="px-4 py-4 text-gray-400">
                                                    {row.href && (
                                                        <ChevronRightIcon className="h-4 w-4" />
                                                    )}
                                                </td>
                                            )}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}

Dashboard.layout = (page: ReactNode) => <DashboardLayout>{page}</DashboardLayout>;
