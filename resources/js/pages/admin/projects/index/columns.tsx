import { Link } from '@inertiajs/react';
import {
    PencilSquareIcon,
    ChatBubbleLeftRightIcon,
    ChartBarSquareIcon,
} from '@heroicons/react/24/outline';
import { DeleteButton, SalesProgress } from '@/components/ui';
import { formatDate, formatMoney } from '@/lib/format';
import { ContactCell, HealthBadge, ProjectNameCell, StatusCell } from './components/cells';
import type { ProjectColumn, ProjectListRow } from '../types';

interface BuildColumnsOptions {
    canViewClient: boolean;
    onRequestStatusChange: (
        project: ProjectListRow,
        value: number | string,
        label: string,
    ) => void;
    onOpenNotes: (project: ProjectListRow) => void;
    onDelete: (project: ProjectListRow) => void;
}

export default function buildColumns({
    canViewClient,
    onRequestStatusChange,
    onOpenNotes,
    onDelete,
}: BuildColumnsOptions): ProjectColumn[] {
    return (
        [
            {
                key: 'project_name',
                header: 'Project',
                className: 'font-medium',
                sortable: true,
                render: (row) => (
                    <ProjectNameCell row={row} showClient={canViewClient} />
                ),
            },
            canViewClient && {
                key: 'client',
                header: 'Client',
                render: (row) => (
                    <ContactCell
                        name={row.client}
                        email={row.client_email}
                        phone={row.client_phone}
                    />
                ),
            },
            {
                key: 'company',
                header: 'Company',
                render: (row) => row.company ?? '—',
            },
            {
                key: 'department',
                header: 'Department',
                render: (row) => row.department ?? '—',
            },
            { key: 'team', header: 'Team', render: (row) => row.team ?? '—' },
            {
                key: 'assigned_employee',
                header: 'Marketer ',
                render: (row) => (
                    <ContactCell
                        name={row.assigned_employee}
                        email={row.assigned_employee_email}
                        phone={row.assigned_employee_phone}
                    />
                ),
            },
            {
                key: 'package_amount',
                header: 'Package',
                sortable: true,
                className: 'text-right',
                render: (row) => formatMoney(row.package_amount),
            },
            {
                key: 'amount_due',
                header: 'Due',
                sortable: true,
                className: 'text-right',
                render: (row) => formatMoney(row.amount_due),
            },
            {
                key: 'achieved_sales',
                header: 'Sales (this month)',
                sortable: true,
                className: 'text-right',
                render: (row) => (
                    <SalesProgress
                        achieved={row.achieved_sales}
                        target={row.monthly_target ?? row.sales_target}
                        projectType={row.project_type}
                    />
                ),
            },
            {
                key: 'health_status',
                header: 'Health',
                sortable: true,
                render: (row) => (
                    <HealthBadge
                        color={row.health_color}
                        label={row.health_label}
                    />
                ),
            },
            {
                key: 'business_status',
                header: 'Status',
                sortable: true,
                render: (row) => (
                    <StatusCell
                        row={row}
                        editable={row.can_edit === true}
                        onChange={onRequestStatusChange}
                    />
                ),
            },
            {
                key: 'end_date',
                header: 'Ends',
                sortable: true,
                render: (row) => formatDate(row.end_date),
            },
            {
                key: 'actions',
                header: 'Actions',
                className: 'text-right',
                render: (row) => {
                    const showEdit = row.can_edit === true;
                    const showReports = row.can_view_reports === true;
                    const showNote = row.can_view_notes === true;
                    const showDelete = row.can_delete === true;
                    const hasPriorAction = showEdit || showReports;

                    return (
                        <div className="flex items-center justify-end gap-3">
                            {showEdit && (
                                <Link
                                    href={`/admin/projects/${row.id}/edit`}
                                    className="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800"
                                >
                                    <PencilSquareIcon className="h-4 w-4" />
                                    Edit
                                </Link>
                            )}
                            {showReports && (
                                <>
                                    {showEdit && (
                                        <span className="text-gray-300">|</span>
                                    )}
                                    <Link
                                        href={`/admin/projects/${row.id}/reports`}
                                        className="inline-flex items-center gap-1 text-emerald-600 hover:text-emerald-800"
                                    >
                                        <ChartBarSquareIcon className="h-4 w-4" />
                                        Reports
                                    </Link>
                                </>
                            )}
                            {showNote && (
                                <>
                                    {hasPriorAction && (
                                        <span className="text-gray-300">|</span>
                                    )}
                                    <button
                                        type="button"
                                        onClick={() => onOpenNotes(row)}
                                        className="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800"
                                    >
                                        <ChatBubbleLeftRightIcon className="h-4 w-4" />
                                        Note
                                    </button>
                                </>
                            )}
                            {showDelete && (
                                <>
                                    {(hasPriorAction || showNote) && (
                                        <span className="text-gray-300">|</span>
                                    )}
                                    <DeleteButton
                                        onDelete={() => onDelete(row)}
                                    />
                                </>
                            )}
                        </div>
                    );
                },
            },
        ] as (ProjectColumn | false)[]
    ).filter((c): c is ProjectColumn => Boolean(c));
}
