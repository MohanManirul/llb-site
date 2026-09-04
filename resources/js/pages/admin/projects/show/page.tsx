import { useEffect, useState, type ReactNode } from 'react';
import { Link, usePage } from '@inertiajs/react';
import {
    ArrowPathIcon,
    ChatBubbleLeftRightIcon,
    ChartBarSquareIcon,
} from '@heroicons/react/24/outline';

import DashboardLayout from '@/components/common/DashboardLayout';
import PageHeader from '@/components/common/PageHeader';
import { Button, SalesProgress } from '@/components/ui';
import ProjectNotesModal from '../components/ProjectNotesModal';
import PaymentModal from '@/components/ui/PaymentModal';
import { PaymentType } from '@/pages/admin/payment/types';
import api from '@/lib/api-client';
import {
    businessStatusBadgeStyle,
    businessStatusLabel,
} from '@/config/businessStatus';
import { flash, errorMessage } from '@/lib/flash';
import { CURRENCY_SYMBOL, formatDate, formatMoney } from '@/lib/format';
import type { ProjectDetail, ProjectEmployee, ProjectPayment, ProjectInvoice } from '../types';

const HEALTH_BADGE: Record<string, string> = {
    green: 'bg-green-100 text-green-700',
    yellow: 'bg-yellow-100 text-yellow-700',
    red: 'bg-red-100 text-red-700',
    gray: 'bg-gray-100 text-gray-600',
};

interface InfoRowProps {
    label: string;
    children?: ReactNode;
}

function InfoRow({ label, children }: InfoRowProps) {
    return (
        <div>
            <dt className="text-xs text-gray-500">{label}</dt>
            <dd className="mt-1 text-sm font-semibold text-gray-800">
                {children}
            </dd>
        </div>
    );
}

interface CardProps {
    title?: string;
    children?: ReactNode;
}

function Card({ title, children }: CardProps) {
    return (
        <div className="rounded-card border border-hairline bg-white shadow-sm">
            {title && (
                <div className="border-b border-gray-100 px-6 py-4">
                    <h3 className="text-sm font-semibold text-gray-900">{title}</h3>
                </div>
            )}
            {children}
        </div>
    );
}

function EmployeeAvatar({ employee }: { employee?: ProjectEmployee | null }) {
    const [failed, setFailed] = useState(false);
    const src = employee?.thumbnail_url ?? null;

    if (!src || failed) {
        return (
            <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-medium text-gray-500">
                {employee?.name?.charAt(0)?.toUpperCase() ?? '—'}
            </span>
        );
    }

    return (
        <img
            src={src}
            alt=""
            onError={() => setFailed(true)}
            className="h-8 w-8 shrink-0 rounded-full object-cover"
        />
    );
}

interface ProjectShowProps {
    projectId: string;
    basePath?: string;
}

export default function ProjectShow({ projectId, basePath = '/admin/projects' }: ProjectShowProps) {
    const [project, setProject] = useState<ProjectDetail | null>(null);
    const [loadError, setLoadError] = useState(false);
    const [notesOpen, setNotesOpen] = useState(false);
    const [paymentModalOpen, setPaymentModalOpen] = useState(false);
    const { auth, portal, paymentTypes = [] } = usePage().props;
    const base = portal?.base ?? '/admin';
    const roles = auth?.user?.roles ?? [];
    const isClient = roles.includes('client');
    const canViewClient =
        roles.includes('super-admin') ||
        roles.includes('client') ||
        (auth?.user?.permissions ?? []).includes('view project client');
    const canViewContact =
        roles.includes('super-admin') ||
        roles.includes('client') ||
        (auth?.user?.permissions ?? []).includes('view project contact');
    const hasProjectTable =
        roles.includes('client') ||
        roles.includes('super-admin') ||
        (auth?.user?.permissions ?? []).includes('view projects');
    const canManagePayments =
        roles.includes('super-admin') ||
        (auth?.user?.permissions ?? []).includes('manage payments');
    const backHref = hasProjectTable ? `${base}/projects` : `${base}/dashboard`;
    const backLabel = hasProjectTable ? 'Back to projects' : 'Back to dashboard';

    const loadProject = (cancelled?: { current: boolean }) => {
        api.get(`${basePath}/${projectId}`)
            .then(({ data }) => {
                if (!cancelled || !cancelled.current) setProject(data.result);
            })
            .catch((error) => {
                if (!cancelled || !cancelled.current) {
                    setLoadError(true);
                    flash.error(errorMessage(error, 'Could not load the project.'));
                }
            });
    };

    useEffect(() => {
        const cancelled = { current: false };
        loadProject(cancelled);

        return () => {
            cancelled.current = true;
        };
    }, [projectId, basePath]);

    if (!project) {
        return (
            <>
            <PageHeader
                title="Project"
                backHref={backHref}
                backLabel={backLabel}
            />

                <div className="w-full space-y-4">
                    <div className="flex justify-center p-10">
                        {loadError ? (
                            <p className="text-sm text-gray-500">
                                This project could not be loaded.
                            </p>
                        ) : (
                            <ArrowPathIcon className="h-6 w-6 animate-spin text-gray-400" />
                        )}
                    </div>
                </div>
        </>
        );
    }

    const milestones = project.milestones ?? [];
    const sales = project.sales ?? [];
    const assignments = project.assignments ?? [];
    const payments = project.payments ?? [];
    const invoices = project.invoices ?? [];
    const isDuePaid = parseFloat(String(project.amount_due || 0)) <= 0;

    return (
        <>
            <PageHeader
                title="Project"
                backHref={backHref}
                backLabel={backLabel}
                action={
                    <>
                        {project.can_view_reports && (
                            <Link href={`${base}/projects/${projectId}/reports`}>
                                <Button variant="secondary" size="sm">
                                    <ChartBarSquareIcon className="h-4 w-4" />
                                    Reports
                                </Button>
                            </Link>
                        )}

                        {project.can_view_notes && (
                            <Button
                                variant="secondary"
                                size="sm"
                                onClick={() => setNotesOpen(true)}
                            >
                                <ChatBubbleLeftRightIcon className="h-4 w-4" />
                                Notes
                            </Button>
                        )}

                        <div className="flex items-center gap-4">
                             
                            {parseFloat(String(project.amount_due || 0)) > 0 ? (
                                canManagePayments && (
                                    <Button
                                        size="sm"
                                        onClick={() => setPaymentModalOpen(true)}
                                    >
                                        {CURRENCY_SYMBOL} Payment Collection
                                    </Button>
                                )
                            ) : (
                                <span className="inline-flex rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800">
                                    ✓ Fully Paid
                                </span>
                            )}
                        </div>
                    </>
                }
            />

            <div className="w-full space-y-6">
                <div className="rounded-card border border-hairline bg-white shadow-sm p-6">
                    <div className="flex flex-col gap-6 mb-6 lg:flex-row lg:items-start lg:justify-between">
                        <div className="flex-1">
                            <h2 className="text-2xl font-bold text-gray-900">
                                {project.project_name ?? project.business_name}
                            </h2>
                            <p className="mt-1 text-sm text-gray-600">
                                {project.business_name}
                            </p>
                            {project.description && (
                                <p className="mt-2 text-sm text-gray-500">
                                    {project.description}
                                </p>
                            )}
                            {project.website_url && (
                                <a
                                    href={project.website_url}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="mt-2 inline-flex items-center text-sm text-blue-600 hover:underline"
                                >
                                    🌐 {project.website_url}
                                </a>
                            )}
                        </div>

                        <div className="w-full lg:w-auto lg:min-w-80 lg:max-w-md lg:shrink-0">
                            <div className="mb-4 flex flex-wrap gap-2 lg:justify-end">
                                <span
                                    className={`inline-flex rounded-full px-3 py-1 text-xs font-medium ${
                                        HEALTH_BADGE[project.health_color ?? ''] ?? HEALTH_BADGE.gray
                                    }`}
                                >
                                    {project.health_label}
                                </span>
                                <span
                                    style={businessStatusBadgeStyle(project.business_status)}
                                    className="inline-flex rounded-full border px-3 py-1 text-xs font-medium"
                                >
                                    {businessStatusLabel(project.business_status)}
                                </span>
                                <span className="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600">
                                    {project.project_type_label}
                                </span>
                            </div>

                            <div className={`grid gap-2 sm:gap-3 ${isDuePaid ? 'grid-cols-2' : 'grid-cols-2 sm:grid-cols-3'}`}>
                                <div className="rounded-lg border border-gray-200 bg-gray-50 p-3 sm:p-4">
                                    <p className="text-xs text-gray-600">Total</p>
                                    <p className="mt-1 text-base font-bold wrap-break-word text-gray-900 sm:mt-2 sm:text-lg">{formatMoney(project.package_amount)}</p>
                                </div>
                                <div className="rounded-lg border border-gray-200 bg-gray-50 p-3 sm:p-4">
                                    <p className="text-xs text-gray-600">Paid</p>
                                    <p className="mt-1 text-base font-bold wrap-break-word text-gray-900 sm:mt-2 sm:text-lg">{formatMoney(project.amount_paid)}</p>
                                </div>
                                {!isDuePaid && (
                                    <div className="col-span-2 rounded-lg border border-gray-200 bg-gray-50 p-3 sm:col-span-1 sm:p-4">
                                        <p className="text-xs text-gray-600">Due</p>
                                        <p className="mt-1 text-base font-bold wrap-break-word text-gray-900 sm:mt-2 sm:text-lg">{formatMoney(project.amount_due)}</p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="border-t border-gray-100 pt-6">
                        <div className="grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-6">
                            {canViewClient && (
                                <div>
                                    <p className="text-xs font-medium text-gray-500 uppercase">Client</p>
                                    <p className="mt-1 text-sm font-semibold text-gray-900">{project.client?.name ?? '—'}</p>
                                    {project.client?.email && <p className="text-xs text-gray-500">{project.client.email}</p>}
                                    {project.client?.phone && <p className="text-xs text-gray-500">{project.client.phone}</p>}
                                </div>
                            )}
                            <div>
                                <p className="text-xs font-medium text-gray-500 uppercase">Marketer</p>
                                {project.assigned_employee ? (
                                    <div className="mt-1 flex items-center gap-2">
                                        <EmployeeAvatar employee={project.assigned_employee} />
                                        <div>
                                            <p className="text-sm font-semibold text-gray-900">{project.assigned_employee.name}</p>
                                            {project.assigned_employee.designation && <p className="text-xs text-gray-500">{project.assigned_employee.designation}</p>}
                                        </div>
                                    </div>
                                ) : (
                                    <p className="mt-1 text-sm text-gray-500">—</p>
                                )}
                            </div>
                            {canViewContact && (
                                <div>
                                    <p className="text-xs font-medium text-gray-500 uppercase">Contact</p>
                                    <p className="mt-1 text-sm font-semibold text-gray-900">{project.contact_person ?? '—'}</p>
                                    {project.contact_email && <p className="text-xs text-gray-500">{project.contact_email}</p>}
                                    {project.contact_phone && <p className="text-xs text-gray-500">{project.contact_phone}</p>}
                                </div>
                            )}
                            <div>
                                <p className="text-xs font-medium text-gray-500 uppercase">Team</p>
                                <p className="mt-1 text-sm font-semibold text-gray-900">{project.team ?? '—'}</p>
                            </div>
                            <div>
                                <p className="text-xs font-medium text-gray-500 uppercase">Company</p>
                                <p className="mt-1 text-sm font-semibold text-gray-900">{project.company ?? '—'}</p>
                            </div>
                            <div>
                                <p className="text-xs font-medium text-gray-500 uppercase">Department</p>
                                <p className="mt-1 text-sm font-semibold text-gray-900">{project.department ?? '—'}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-1 items-start gap-6 lg:grid-cols-2">
                    <Card title="Contract & Payment">
                        <dl className="grid grid-cols-2 gap-x-6 gap-y-5 p-6">
                            <InfoRow label="Start">{formatDate(project.start_date)}</InfoRow>
                            <InfoRow label="End">{formatDate(project.end_date)}</InfoRow>
                            <InfoRow label="Contract duration">
                                {project.contract_months} month(s)
                                {project.contract_days
                                    ? ` ${project.contract_days} day(s)`
                                    : ''}
                            </InfoRow>
                            {!isDuePaid && (
                                <InfoRow label="Next payment">
                                    {formatDate(project.next_payment_date)}
                                </InfoRow>
                            )}
                            <InfoRow label="Package">
                                {formatMoney(project.package_amount)}
                            </InfoRow>
                            <InfoRow label="Paid">{formatMoney(project.amount_paid)}</InfoRow>
                            {!isDuePaid && (
                                <InfoRow label="Due">{formatMoney(project.amount_due)}</InfoRow>
                            )}
                        </dl>
                    </Card>

                    <Card title="Sales Target">
                        {project.project_type === 'challenge_based' ? (
                            <div className="flex items-start gap-6 p-6">
                                <dl className="grid flex-1 grid-cols-2 gap-x-6 gap-y-5">
                                    <InfoRow label="Total target">
                                        {formatMoney(project.sales_target)}
                                    </InfoRow>
                                    <InfoRow label="Total achieved">
                                        {formatMoney(project.total_achieved_sales)}
                                    </InfoRow>
                                    <InfoRow label="Monthly target">
                                        {formatMoney(project.milestone?.target)}
                                    </InfoRow>
                                    <InfoRow label="Achieved this month">
                                        {formatMoney(project.milestone?.achieved)}
                                    </InfoRow>
                                    <InfoRow label="Current milestone">
                                        {project.milestone?.period_start
                                            ? `${formatDate(project.milestone.period_start)} – ${formatDate(project.milestone.period_end)}`
                                            : '—'}
                                    </InfoRow>
                                    <InfoRow label="Target start">
                                        {formatDate(project.target_start_date)}
                                    </InfoRow>
                                    <InfoRow label="Target duration">
                                        {project.target_months} month(s)
                                        {project.target_days
                                            ? ` ${project.target_days} day(s)`
                                            : ''}
                                    </InfoRow>
                                    <InfoRow label="Deadline">
                                        {formatDate(project.target_deadline)}
                                    </InfoRow>
                                </dl>
                                <div className="shrink-0">
                                    <SalesProgress
                                        achieved={project.achieved_sales}
                                        target={project.monthly_target ?? project.sales_target}
                                        size="lg"
                                    />
                                </div>
                            </div>
                        ) : (
                            <p className="p-6 text-sm text-gray-500">
                                Regular project — no sales goal.
                            </p>
                        )}
                    </Card>
                </div>

                <Card title={`${isClient ? 'My' : 'Collected'} Payments (${payments.length})`}>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-100 text-sm">
                            <thead className="bg-sidebar">
                                <tr className="text-left text-xs uppercase tracking-wide text-gray-500">
                                    <th className="w-16 px-6 py-3">SL</th>
                                    <th className="px-6 py-3">Date</th>
                                    <th className="px-6 py-3 text-right">Amount</th>
                                    <th className="px-6 py-3">Type</th>
                                    <th className="px-6 py-3">Reference</th>
                                    <th className="px-6 py-3">Notes</th>
                                    <th className="px-6 py-3">Collected By</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {payments.length === 0 && (
                                    <tr>
                                        <td colSpan={7} className="px-6 py-4 text-gray-400">
                                            {isClient ? 'No Payments yet.' : 'No payments collected yet.'}
                                        </td>
                                    </tr>
                                )}
                                {payments.map((p, index) => (
                                    <tr key={p.id} className="even:bg-gray-50">
                                        <td className="px-6 py-3 text-gray-500">
                                            {index + 1}
                                        </td>
                                        <td className="px-6 py-3">{formatDate(p.payment_date)}</td>
                                        <td className="px-6 py-3 text-right font-medium">
                                            {formatMoney(p.amount)}
                                        </td>
                                        <td className="px-6 py-3">
                                            <span className="inline-flex rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-700">
                                                {p.payment_type ?? '—'}
                                            </span>
                                        </td>
                                        <td className="px-6 py-3 text-gray-500">
                                            {p.reference_number ?? '—'}
                                        </td>
                                        <td className="px-6 py-3 text-gray-500 max-w-xs truncate">
                                            {p.notes ?? '—'}
                                        </td>
                                        <td className="px-6 py-3">{p.created_by ?? '—'}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </Card>

                <Card title={`Milestones (${milestones.length})`}>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-100 text-sm">
                            <thead className="bg-sidebar">
                                <tr className="text-left text-xs uppercase tracking-wide text-gray-500">
                                    <th className="w-16 px-6 py-3">SL</th>
                                    <th className="px-6 py-3">#</th>
                                    <th className="px-6 py-3">Period</th>
                                    <th className="px-6 py-3 text-right">Target</th>
                                    <th className="px-6 py-3 text-right">Achieved</th>
                                    <th className="px-6 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {milestones.length === 0 && (
                                    <tr>
                                        <td colSpan={6} className="px-6 py-4 text-gray-400">
                                            No milestones.
                                        </td>
                                    </tr>
                                )}
                                {milestones.map((m, index) => (
                                    <tr key={m.id} className="even:bg-gray-50">
                                        <td className="px-6 py-3 text-gray-500">
                                            {index + 1}
                                        </td>
                                        <td className="px-6 py-3">{m.sequence}</td>
                                        <td className="px-6 py-3">
                                            {formatDate(m.period_start)} –{' '}
                                            {formatDate(m.period_end)}
                                        </td>
                                        <td className="px-6 py-3 text-right">
                                            {formatMoney(m.target_amount)}
                                        </td>
                                        <td className="px-6 py-3 text-right">
                                            {formatMoney(m.achieved_amount)}
                                        </td>
                                        <td className="px-6 py-3">
                                            {m.status_label ?? m.status ?? '—'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </Card>

                <Card title={`Invoice List (${invoices.length})`}>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-100 text-sm">
                            <thead className="bg-sidebar">
                                <tr className="text-left text-xs uppercase tracking-wide text-gray-500">
                                    <th className="w-16 px-6 py-3">SL</th>
                                    <th className="px-6 py-3">INV/ Number</th>
                                    <th className="px-6 py-3">Invoice Date</th>
                                    <th className="px-6 py-3 text-right">Total Amount</th>
                                    <th className="px-6 py-3 text-right">Paid Amount</th>
                                    <th className="px-6 py-3 text-right">Due Amount</th>
                                    <th className="px-6 py-3">Due Date</th>
                                    <th className="px-6 py-3">Email Sent To</th>
                                    <th className="px-6 py-3">Email Sent At</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {invoices.length === 0 && (
                                    <tr>
                                        <td colSpan={9} className="px-6 py-4 text-gray-400">
                                            No invoices.
                                        </td>
                                    </tr>
                                )}
                                {invoices.map((invoice, index) => (
                                    <tr key={invoice.id} className="even:bg-gray-50">
                                        <td className="px-6 py-3 text-gray-500">
                                            {index + 1}
                                        </td>
                                        <td className="px-6 py-3 font-medium">
                                            {invoice.invoice_number ?? '—'}
                                        </td>
                                        <td className="px-6 py-3">
                                            {formatDate(invoice.invoice_date)}
                                        </td>
                                        <td className="px-6 py-3 text-right">
                                            {formatMoney(invoice.total_amount)}
                                        </td>
                                        <td className="px-6 py-3 text-right">
                                            {formatMoney(invoice.paid_amount)}
                                        </td>
                                        <td className="px-6 py-3 text-right">
                                            {formatMoney(invoice.due_amount)}
                                        </td>
                                        <td className="px-6 py-3">
                                            {formatDate(invoice.due_date)}
                                        </td>
                                        <td className="px-6 py-3 text-blue-600">
                                            {invoice.email_sent_to ?? '—'}
                                        </td>
                                        <td className="px-6 py-3 text-sm text-gray-500">
                                            {invoice.email_sent_at ? formatDate(invoice.email_sent_at) : '—'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </Card>

                <Card title={`Assignment History (${assignments.length})`}>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-100 text-sm">
                            <thead className="bg-sidebar">
                                <tr className="text-left text-xs uppercase tracking-wide text-gray-500">
                                    <th className="w-16 px-6 py-3">SL</th>
                                    <th className="px-6 py-3">Team</th>
                                    <th className="px-6 py-3">Marketer</th>
                                    <th className="px-6 py-3">From</th>
                                    <th className="px-6 py-3">Until</th>
                                    <th className="px-6 py-3">Reason</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {assignments.length === 0 && (
                                    <tr>
                                        <td colSpan={6} className="px-6 py-4 text-gray-400">
                                            No assignments recorded.
                                        </td>
                                    </tr>
                                )}
                                {assignments.map((a, index) => (
                                    <tr key={a.id} className="even:bg-gray-50">
                                        <td className="px-6 py-3 text-gray-500">
                                            {index + 1}
                                        </td>
                                        <td className="px-6 py-3">{a.team ?? '—'}</td>
                                        <td className="px-6 py-3">{a.employee ?? '—'}</td>
                                        <td className="px-6 py-3">
                                            {formatDate(a.assigned_at)}
                                        </td>
                                        <td className="px-6 py-3">
                                            {a.unassigned_at ? (
                                                formatDate(a.unassigned_at)
                                            ) : (
                                                <span className="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700">
                                                    Current
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-6 py-3 text-gray-500">
                                            {a.reason ?? '—'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </Card>

                <Card title={`Recent Sales (${sales.length})`}>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-100 text-sm">
                            <thead className="bg-sidebar">
                                <tr className="text-left text-xs uppercase tracking-wide text-gray-500">
                                    <th className="w-16 px-6 py-3">SL</th>
                                    <th className="px-6 py-3">Date</th>
                                    <th className="px-6 py-3 text-right">Amount</th>
                                    <th className="px-6 py-3">Reference</th>
                                    <th className="px-6 py-3">Employee</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {sales.length === 0 && (
                                    <tr>
                                        <td colSpan={5} className="px-6 py-4 text-gray-400">
                                            No sales recorded.
                                        </td>
                                    </tr>
                                )}
                                {sales.map((s, index) => (
                                    <tr key={s.id} className="even:bg-gray-50">
                                        <td className="px-6 py-3 text-gray-500">
                                            {index + 1}
                                        </td>
                                        <td className="px-6 py-3">{formatDate(s.sale_date)}</td>
                                        <td className="px-6 py-3 text-right">
                                            {formatMoney(s.amount)}
                                        </td>
                                        <td className="px-6 py-3 text-gray-500">
                                            {s.reference ?? '—'}
                                        </td>
                                        <td className="px-6 py-3">{s.employee ?? '—'}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>

            <ProjectNotesModal
                show={notesOpen}
                project={project}
                onClose={() => setNotesOpen(false)}
                canAdd={project.can_add_notes === true}
            />

            <PaymentModal
                isOpen={paymentModalOpen}
                onClose={() => setPaymentModalOpen(false)}
                projectId={parseInt(projectId)}
                paymentTypes={paymentTypes as PaymentType[]}
                project={project ? {
                    package_amount: project.package_amount,
                    amount_paid: project.amount_paid,
                    amount_due: project.amount_due,
                } : undefined}
                onPaymentSuccess={() => loadProject()}
            />
        </>
    );
}

ProjectShow.layout = (page: ReactNode) => <DashboardLayout>{page}</DashboardLayout>;
