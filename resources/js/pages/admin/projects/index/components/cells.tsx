import { memo } from 'react';
import BusinessStatusSelect from '../../components/BusinessStatusSelect';
import { BUSINESS_STATUS_OPTIONS, businessStatusLabel } from '@/config/businessStatus';
import { HEALTH_BADGE } from '../constants';
import { useSavingStatusId } from '../SavingStatusContext';
import type { ProjectListRow } from '../../types';

interface ContactCellProps {
    name?: string | null;
    email?: string | null;
    phone?: string | null;
}

export const ContactCell = memo(function ContactCell({
    name,
    email,
    phone,
}: ContactCellProps) {
    if (!name) return <>—</>;

    return (
        <div className="flex max-w-56 flex-col">
            <span className="truncate" title={name}>
                {name}
            </span>
            {email && (
                <span className="truncate text-xs text-gray-500" title={email}>
                    {email}
                </span>
            )}
            {phone && (
                <span className="truncate text-xs text-gray-500" title={phone}>
                    {phone}
                </span>
            )}
        </div>
    );
});

interface HealthBadgeProps {
    color?: string | null;
    label?: string | null;
}

export const HealthBadge = memo(function HealthBadge({
    color,
    label,
}: HealthBadgeProps) {
    return (
        <span
            className={`inline-flex rounded-full px-2 py-1 text-xs font-medium ${
                HEALTH_BADGE[color ?? ''] ?? HEALTH_BADGE.gray
            }`}
        >
            {label}
        </span>
    );
});

interface ProjectNameCellProps {
    row: ProjectListRow;
    showClient: boolean;
}

export const ProjectNameCell = memo(function ProjectNameCell({
    row,
    showClient,
}: ProjectNameCellProps) {
    const title = row.project_name ?? row.business_name ?? '';
    const subtitle = [row.business_name, showClient ? row.client : null]
        .filter(Boolean)
        .filter((part, index, parts) => parts.indexOf(part) === index)
        .join(' · ');

    return (
        <div className="flex max-w-64 flex-col">
            <span className="truncate" title={title}>
                {title}
            </span>
            {subtitle && (
                <span
                    className="truncate text-xs text-gray-500"
                    title={subtitle}
                >
                    {subtitle}
                </span>
            )}
        </div>
    );
});

interface StatusCellProps {
    row: ProjectListRow;
    editable: boolean;
    onChange: (
        project: ProjectListRow,
        value: number | string,
        label: string,
    ) => void;
}

export const StatusCell = memo(function StatusCell({
    row,
    editable,
    onChange,
}: StatusCellProps) {
    const savingStatusId = useSavingStatusId();

    return (
        <BusinessStatusSelect
            value={row.business_status}
            label={businessStatusLabel(row.business_status)}
            options={BUSINESS_STATUS_OPTIONS}
            editable={editable}
            saving={savingStatusId === row.id}
            onChange={(value, label) => onChange(row, value, label)}
        />
    );
});
