import { useMemo, useState, type ReactNode } from 'react';
import { Link } from '@inertiajs/react';
import {
    PlusIcon,
    PencilSquareIcon,
    ArrowUpTrayIcon,
} from '@heroicons/react/24/outline';
import DashboardLayout from '@/components/common/DashboardLayout';
import PageHeader from '@/components/common/PageHeader';
import {
    Button,
    ConfirmationModal,
    DataTable,
    DeleteButton,
    TableFilters,
    TableSelect,
} from '@/components/ui';
import useDeleteResource from '@/hooks/useDeleteResource';
import usePermissions from '@/hooks/usePermissions';
import useResourceIndex from '@/hooks/useResourceIndex';
import ClientViewModal, { type ClientDetails } from '../components/ClientViewModal';
import ClientCsvUploadModal from '../components/ClientCsvUploadModal';
import { formatDate } from '@/lib/format';

type Client = ClientDetails;

interface Column<T> {
    key: string;
    header: string;
    className?: string;
    sortable?: boolean;
    render?: (row: T) => ReactNode;
}

export default function ClientsIndex() {
    const { can } = usePermissions();
    const canDelete = can('delete clients');

    const [viewingClient, setViewingClient] = useState<Client | null>(null);
    const [uploadOpen, setUploadOpen] = useState(false);

    const [isActive, setIsActive] = useState('');

    const { tableProps, refetch } = useResourceIndex<Client>({
        url: '/admin/clients',
        storageKey: 'clients',
        filters: { is_active: isActive },
        errorMessage: 'Could not load clients.',
    });

    const remove = useDeleteResource<Client>({
        url: (client) => `/admin/clients/${client.id}`,
        onDeleted: refetch,
        successMessage: 'Client deleted successfully.',
        errorMessage: 'Could not delete this client.',
    });

    const columns = useMemo<Column<Client>[]>(
        () => [
            {
                key: 'name',
                header: 'Name',
                className: 'font-medium',
                sortable: true,
                render: (row) => row.name,
            },
            { key: 'email', header: 'Email', sortable: true },
            { key: 'phone', header: 'Phone', render: (row) => row.phone ?? '—' },
            {
                key: 'created_at',
                header: 'Joined',
                sortable: true,
                render: (row) => formatDate(row.created_at),
            },
            {
                key: 'is_active',
                header: 'Status',
                sortable: true,
                render: (row) => (
                    <span
                        className={`inline-flex rounded-full px-2 py-1 text-xs font-medium ${
                            row.is_active
                                ? 'bg-green-100 text-green-700'
                                : 'bg-red-100 text-red-700'
                        }`}
                    >
                        {row.is_active ? 'Active' : 'Inactive'}
                    </span>
                ),
            },
            {
                key: 'actions',
                header: 'Actions',
                className: 'text-right',
                render: (row) => (
                    <div className="flex items-center justify-end gap-3">
                        <Link
                            href={`/admin/clients/${row.id}/edit`}
                            className="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800"
                            onClick={(event) => event.stopPropagation()}
                        >
                            <PencilSquareIcon className="h-4 w-4" />
                            Edit
                        </Link>

                        {canDelete && (
                            <DeleteButton onDelete={() => remove.request(row)} />
                        )}
                    </div>
                ),
            },
        ],
        [canDelete, remove.request],
    );

    return (
        <>
            <PageHeader
                title="Clients"
            action={
                <div className="flex items-center gap-2">
                    <Button
                        size="sm"
                        variant="secondary"
                        onClick={() => setUploadOpen(true)}
                    >
                        <ArrowUpTrayIcon className="h-4 w-4" />
                        Upload CSV
                    </Button>
                    <Link href="/admin/clients/create">
                        <Button size="sm">
                            <PlusIcon className="h-4 w-4" />
                            Create
                        </Button>
                    </Link>
                </div>
                }
            />

            <div className="flex flex-col">
                <DataTable
                    columns={columns}
                    {...tableProps}
                    onRowClick={(row: Client) => setViewingClient(row)}
                    filters={
                        <TableFilters
                            activeCount={isActive ? 1 : 0}
                            onClear={() => setIsActive('')}
                        >
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-700">
                                    Status
                                </label>
                                <TableSelect
                                    value={isActive}
                                    onChange={(e: React.ChangeEvent<HTMLSelectElement>) => setIsActive(e.target.value)}
                                >
                                    <option value="">Status</option>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </TableSelect>
                            </div>
                        </TableFilters>
                    }
                />
            </div>

            <ClientViewModal
                client={viewingClient}
                onClose={() => setViewingClient(null)}
            />

            <ClientCsvUploadModal
                show={uploadOpen}
                onClose={() => setUploadOpen(false)}
                onImported={refetch}
            />

            <ConfirmationModal
                show={remove.pending !== null}
                onClose={remove.cancel}
                onConfirm={remove.confirm}
                processing={remove.deleting}
                title="Delete client"
                confirmText="Delete"
            >
                Are you sure you want to delete{' '}
                <span className="font-medium">{remove.pending?.name}</span>?
                This action cannot be undone.
            </ConfirmationModal>
        </>
    );
}

ClientsIndex.layout = (page: ReactNode) => <DashboardLayout wide>{page}</DashboardLayout>;
