import { ReactNode, useEffect, useMemo, useState } from 'react';
import { Link } from '@inertiajs/react';
import {
    ArrowUturnLeftIcon,
    CheckCircleIcon,
    MapPinIcon,
    PencilSquareIcon,
    PlusIcon,
} from '@heroicons/react/24/outline';
import DashboardLayout from '@/components/common/DashboardLayout';
import PageHeader from '@/components/common/PageHeader';
import {
    Button,
    ConfirmationModal,
    DataTable,
    DeleteButton,
    StatusBadge,
    TableFilters,
    TableSelect,
} from '@/components/ui';
import api from '@/lib/api-client';
import type { ApiEnvelope } from '@/lib/api-types';
import { errorMessage, flash } from '@/lib/flash';
import { formatDate } from '@/lib/format';
import useDeleteResource from '@/hooks/useDeleteResource';
import usePermissions from '@/hooks/usePermissions';
import useResourceIndex from '@/hooks/useResourceIndex';
import { Notice, NoticeFilterOptions } from '../types';

interface Column {
    key: string;
    header: string;
    className?: string;
    sortable?: boolean;
    render?: (row: Notice) => ReactNode;
}

export default function NoticesIndex() {
    const { can } = usePermissions();
    const canCreate = can('create notices');
    const canEdit = can('edit notices');
    const canPublish = can('publish notices');
    const canDelete = can('delete notices');

    const [options, setOptions] = useState<NoticeFilterOptions | null>(null);
    const [statusTab, setStatusTab] = useState('');
    const [category, setCategory] = useState('');
    const [togglingId, setTogglingId] = useState<number | null>(null);

    useEffect(() => {
        let cancelled = false;

        api.get<ApiEnvelope<NoticeFilterOptions>>('/admin/notices/filters')
            .then(({ data }) => {
                if (!cancelled) setOptions(data.result);
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
            status: statusTab || undefined,
            category: category || undefined,
        }),
        [statusTab, category],
    );

    const { tableProps, paginator, refetch } = useResourceIndex<Notice>({
        url: '/admin/notices',
        storageKey: 'notices',
        errorMessage: 'Could not load notices.',
        filters,
    });

    const remove = useDeleteResource<Notice>({
        url: (notice) => `/admin/notices/${notice.id}`,
        onDeleted: refetch,
        successMessage: 'Notice deleted successfully.',
        errorMessage: 'Could not delete this notice.',
    });

    const statusCount = paginator?.status_count ?? {};

    const tabs = useMemo(
        () => [
            {
                value: '',
                label: `All (${(statusCount.draft ?? 0) + (statusCount.published ?? 0) + (statusCount.archived ?? 0)})`,
            },
            { value: 'draft', label: `Draft (${statusCount.draft ?? 0})` },
            { value: 'published', label: `Published (${statusCount.published ?? 0})` },
            { value: 'archived', label: `Archived (${statusCount.archived ?? 0})` },
        ],
        [statusCount.draft, statusCount.published, statusCount.archived],
    );

    const togglePublish = async (notice: Notice) => {
        setTogglingId(notice.id);

        const publishing = notice.status !== 'published';

        try {
            await api.patch(`/admin/notices/${notice.id}/${publishing ? 'publish' : 'unpublish'}`);
            flash.success(publishing ? 'Notice published.' : 'Notice unpublished.');
            refetch();
        } catch (error) {
            flash.error(errorMessage(error, 'Could not change the publish state.'));
        } finally {
            setTogglingId(null);
        }
    };

    const columns = useMemo<Column[]>(
        () => [
            {
                key: 'title_bn',
                header: 'Title',
                className: 'font-medium',
                sortable: true,
                render: (row) => (
                    <span className="flex items-center gap-2">
                        {row.is_pinned && <MapPinIcon className="h-4 w-4 shrink-0 text-amber-500" />}
                        <span className="flex flex-col">
                            <span>{row.title_bn}</span>
                            <span className="text-xs text-gray-500">
                                {row.program?.name_en ?? 'All programs'}
                                {row.session ? ` · ${row.session.label}` : ''}
                            </span>
                        </span>
                    </span>
                ),
            },
            {
                key: 'category',
                header: 'Category',
                sortable: true,
                render: (row) => <StatusBadge status={row.category} tone="blue" />,
            },
            {
                key: 'status',
                header: 'Status',
                render: (row) => <StatusBadge status={row.status} />,
            },
            {
                key: 'published_at',
                header: 'Published',
                sortable: true,
                render: (row) => formatDate(row.published_at),
            },
            {
                key: 'expires_at',
                header: 'Expires',
                render: (row) => formatDate(row.expires_at),
            },
            {
                key: 'actions',
                header: 'Actions',
                className: 'text-right',
                render: (row) => (
                    <div className="flex items-center justify-end gap-4">
                        {canPublish &&
                            (row.status === 'published' ? (
                                <button
                                    type="button"
                                    onClick={() => togglePublish(row)}
                                    disabled={togglingId === row.id}
                                    className="inline-flex items-center gap-1 text-amber-600 hover:text-amber-800 disabled:opacity-50"
                                >
                                    <ArrowUturnLeftIcon className="h-4 w-4" />
                                    Unpublish
                                </button>
                            ) : (
                                <button
                                    type="button"
                                    onClick={() => togglePublish(row)}
                                    disabled={togglingId === row.id}
                                    className="inline-flex items-center gap-1 text-emerald-600 hover:text-emerald-800 disabled:opacity-50"
                                >
                                    <CheckCircleIcon className="h-4 w-4" />
                                    Publish
                                </button>
                            ))}

                        {canEdit && (
                            <Link
                                href={`/admin/notices/${row.id}/edit`}
                                className="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800"
                            >
                                <PencilSquareIcon className="h-4 w-4" />
                                Edit
                            </Link>
                        )}

                        {canDelete && <DeleteButton onDelete={() => remove.request(row)} />}
                    </div>
                ),
            },
        ],
        [canPublish, canEdit, canDelete, togglingId, remove.request],
    );

    return (
        <>
            <PageHeader
                title="Notices"
                action={
                    canCreate ? (
                        <Link href="/admin/notices/create">
                            <Button size="sm">
                                <PlusIcon className="h-4 w-4" />
                                Create
                            </Button>
                        </Link>
                    ) : undefined
                }
            />

            <div className="flex flex-col">
                <DataTable
                    columns={columns}
                    {...tableProps}
                    tabs={tabs}
                    activeTab={statusTab}
                    onTabChange={(value) => setStatusTab(value)}
                    filters={
                        <TableFilters
                            activeCount={category ? 1 : 0}
                            onClear={() => setCategory('')}
                        >
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-700">
                                    Category
                                </label>
                                <TableSelect
                                    value={category}
                                    onChange={(e) => setCategory(e.target.value)}
                                >
                                    <option value="">All categories</option>
                                    {options?.categories.map((option) => (
                                        <option key={option.value} value={option.value}>
                                            {option.label_en}
                                        </option>
                                    ))}
                                </TableSelect>
                            </div>
                        </TableFilters>
                    }
                />
            </div>

            <ConfirmationModal
                show={remove.pending !== null}
                onClose={remove.cancel}
                onConfirm={remove.confirm}
                processing={remove.deleting}
                title="Delete notice"
                confirmText="Delete"
            >
                Are you sure you want to delete{' '}
                <span className="font-medium">{remove.pending?.title_bn}</span>?
            </ConfirmationModal>
        </>
    );
}

NoticesIndex.layout = (page: ReactNode) => <DashboardLayout wide>{page}</DashboardLayout>;
