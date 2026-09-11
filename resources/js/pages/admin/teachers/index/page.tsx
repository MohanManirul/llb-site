import { ReactNode, useEffect, useMemo, useState } from 'react';
import DashboardLayout from '@/components/common/DashboardLayout';
import PageHeader from '@/components/common/PageHeader';
import { DataTable, StatusBadge, TableFilters, TableSelect, Toggle } from '@/components/ui';
import api from '@/lib/api-client';
import type { ApiEnvelope } from '@/lib/api-types';
import { errorMessage, flash } from '@/lib/flash';
import { displayPhone, formatDateTime } from '@/lib/format';
import usePermissions from '@/hooks/usePermissions';
import useResourceIndex from '@/hooks/useResourceIndex';
import type { CollegeOption } from '../../colleges/types';
import { Teacher } from '../types';

interface Column {
    key: string;
    header: string;
    className?: string;
    sortable?: boolean;
    render?: (row: Teacher) => ReactNode;
}

export default function TeachersIndex() {
    const { can } = usePermissions();
    const canEdit = can('edit teachers');

    const [colleges, setColleges] = useState<CollegeOption[]>([]);
    const [activeTab, setActiveTab] = useState('');
    const [collegeId, setCollegeId] = useState('');
    const [togglingId, setTogglingId] = useState<number | null>(null);

    useEffect(() => {
        let cancelled = false;

        api.get<ApiEnvelope<CollegeOption[]>>('/admin/colleges/options')
            .then(({ data }) => {
                if (!cancelled) setColleges(data.result);
            })
            .catch((error) => {
                if (!cancelled) flash.error(errorMessage(error, 'Could not load filters.'));
            });

        return () => {
            cancelled = true;
        };
    }, []);

    const filters = useMemo(
        () => ({
            is_active: activeTab === '' ? undefined : activeTab,
            college_id: collegeId || undefined,
        }),
        [activeTab, collegeId],
    );

    const { tableProps, setPaginator } = useResourceIndex<Teacher>({
        url: '/admin/teachers',
        storageKey: 'teachers',
        errorMessage: 'Could not load teachers.',
        filters,
    });

    const tabs = useMemo(
        () => [
            { value: '', label: 'All' },
            { value: '0', label: 'Pending' },
            { value: '1', label: 'Approved' },
        ],
        [],
    );

    const toggleActive = async (teacher: Teacher) => {
        setTogglingId(teacher.id);

        try {
            const { data } = await api.patch<ApiEnvelope<Teacher>>(
                `/admin/teachers/${teacher.id}/active`,
            );
            const updated = data.result;

            setPaginator((current) =>
                current
                    ? {
                          ...current,
                          data: current.data.map((row) =>
                              row.id === updated.id
                                  ? { ...row, is_active: updated.is_active, approved_at: updated.approved_at }
                                  : row,
                          ),
                      }
                    : current,
            );

            flash.success(updated.is_active ? 'Teacher approved.' : 'Teacher deactivated.');
        } catch (error) {
            flash.error(errorMessage(error, 'Could not update the teacher.'));
        } finally {
            setTogglingId(null);
        }
    };

    const columns = useMemo<Column[]>(
        () => [
            {
                key: 'name',
                header: 'Teacher',
                className: 'font-medium',
                render: (row) => (
                    <span className="flex flex-col">
                        <span>{row.name}</span>
                        <span className="text-xs text-gray-500">{row.email}</span>
                    </span>
                ),
            },
            {
                key: 'phone',
                header: 'Phone',
                render: (row) => displayPhone(row.phone),
            },
            {
                key: 'college',
                header: 'College',
                render: (row) => row.college?.name_en ?? row.college?.name_bn ?? '—',
            },
            {
                key: 'designation',
                header: 'Designation',
                render: (row) => row.designation_en ?? row.designation_bn ?? '—',
            },
            {
                key: 'content',
                header: 'Content',
                render: (row) =>
                    (row.class_routines_count ?? 0) +
                    (row.notices_count ?? 0) +
                    (row.class_notes_count ?? 0),
            },
            {
                key: 'last_login_at',
                header: 'Last login',
                sortable: true,
                render: (row) => formatDateTime(row.last_login_at),
            },
            {
                key: 'created_at',
                header: 'Registered',
                sortable: true,
                render: (row) => formatDateTime(row.created_at),
            },
            {
                key: 'is_active',
                header: 'Approved',
                className: 'text-right',
                render: (row) =>
                    canEdit ? (
                        <div className="flex justify-end">
                            <Toggle
                                checked={row.is_active}
                                onChange={() => toggleActive(row)}
                                disabled={togglingId === row.id}
                                ariaLabel={row.is_active ? 'Deactivate teacher' : 'Approve teacher'}
                            />
                        </div>
                    ) : (
                        <StatusBadge
                            status={row.is_active ? 'Approved' : 'Pending'}
                            tone={row.is_active ? 'green' : 'yellow'}
                        />
                    ),
            },
        ],
        [canEdit, togglingId],
    );

    return (
        <>
            <PageHeader title="Teachers" />

            <div className="flex flex-col">
                <DataTable
                    columns={columns}
                    {...tableProps}
                    tabs={tabs}
                    activeTab={activeTab}
                    onTabChange={(value) => setActiveTab(value)}
                    filters={
                        <TableFilters
                            activeCount={collegeId ? 1 : 0}
                            onClear={() => setCollegeId('')}
                        >
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-700">
                                    College
                                </label>
                                <TableSelect
                                    value={collegeId}
                                    onChange={(e) => setCollegeId(e.target.value)}
                                >
                                    <option value="">All colleges</option>
                                    {colleges.map((college) => (
                                        <option key={college.value} value={college.value}>
                                            {college.label}
                                        </option>
                                    ))}
                                </TableSelect>
                            </div>
                        </TableFilters>
                    }
                />
            </div>
        </>
    );
}

TeachersIndex.layout = (page: ReactNode) => <DashboardLayout wide>{page}</DashboardLayout>;
