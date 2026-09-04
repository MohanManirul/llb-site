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
import { ProgramOption, Student } from '../types';

interface Column {
    key: string;
    header: string;
    className?: string;
    sortable?: boolean;
    render?: (row: Student) => ReactNode;
}

export default function StudentsIndex() {
    const { can } = usePermissions();
    const canEdit = can('edit students');

    const [programs, setPrograms] = useState<ProgramOption[]>([]);
    const [activeTab, setActiveTab] = useState('');
    const [programId, setProgramId] = useState('');
    const [togglingId, setTogglingId] = useState<number | null>(null);

    useEffect(() => {
        let cancelled = false;

        api.get<ApiEnvelope<ProgramOption[]>>('/admin/programs/options')
            .then(({ data }) => {
                if (!cancelled) setPrograms(data.result);
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
            program_id: programId || undefined,
        }),
        [activeTab, programId],
    );

    const { tableProps, setPaginator } = useResourceIndex<Student>({
        url: '/admin/students',
        storageKey: 'students',
        errorMessage: 'Could not load students.',
        filters,
    });

    const tabs = useMemo(
        () => [
            { value: '', label: 'All' },
            { value: '1', label: 'Active' },
            { value: '0', label: 'Inactive' },
        ],
        [],
    );

    const toggleActive = async (student: Student) => {
        setTogglingId(student.id);

        try {
            const { data } = await api.patch<ApiEnvelope<Student>>(`/admin/students/${student.id}/active`);
            const updated = data.result;

            setPaginator((current) =>
                current
                    ? {
                          ...current,
                          data: current.data.map((row) =>
                              row.id === updated.id ? { ...row, is_active: updated.is_active } : row,
                          ),
                      }
                    : current,
            );

            flash.success(updated.is_active ? 'Student activated.' : 'Student deactivated.');
        } catch (error) {
            flash.error(errorMessage(error, 'Could not update the student.'));
        } finally {
            setTogglingId(null);
        }
    };

    const columns = useMemo<Column[]>(
        () => [
            {
                key: 'name',
                header: 'Student',
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
                key: 'program',
                header: 'Program',
                render: (row) => row.program?.name_en ?? '—',
            },
            {
                key: 'attempts_count',
                header: 'Tests',
                render: (row) => row.attempts_count ?? 0,
            },
            {
                key: 'practice_sessions_count',
                header: 'Practice',
                render: (row) => row.practice_sessions_count ?? 0,
            },
            {
                key: 'last_login_at',
                header: 'Last login',
                sortable: true,
                render: (row) => formatDateTime(row.last_login_at),
            },
            {
                key: 'created_at',
                header: 'Joined',
                sortable: true,
                render: (row) => formatDateTime(row.created_at),
            },
            {
                key: 'is_active',
                header: 'Status',
                className: 'text-right',
                render: (row) =>
                    canEdit ? (
                        <div className="flex justify-end">
                            <Toggle
                                checked={row.is_active}
                                onChange={() => toggleActive(row)}
                                disabled={togglingId === row.id}
                                ariaLabel={row.is_active ? 'Deactivate student' : 'Activate student'}
                            />
                        </div>
                    ) : (
                        <StatusBadge status={row.is_active ? 'active' : 'inactive'} />
                    ),
            },
        ],
        [canEdit, togglingId],
    );

    return (
        <>
            <PageHeader title="Students" />

            <div className="flex flex-col">
                <DataTable
                    columns={columns}
                    {...tableProps}
                    tabs={tabs}
                    activeTab={activeTab}
                    onTabChange={(value) => setActiveTab(value)}
                    filters={
                        <TableFilters activeCount={programId ? 1 : 0} onClear={() => setProgramId('')}>
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-700">Program</label>
                                <TableSelect value={programId} onChange={(e) => setProgramId(e.target.value)}>
                                    <option value="">All programs</option>
                                    {programs.map((program) => (
                                        <option key={program.value} value={program.value}>
                                            {program.label}
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

StudentsIndex.layout = (page: ReactNode) => <DashboardLayout wide>{page}</DashboardLayout>;
