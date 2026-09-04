import { useEffect, type ReactNode } from 'react';
import { XMarkIcon } from '@heroicons/react/24/outline';
import { formatDateTime } from '@/lib/format';

export interface ClientDetails {
    id: number;
    name: string;
    email: string;
    phone?: string | null;
    address?: string | null;
    description?: string | null;
    image_url?: string | null;
    thumbnail_url?: string | null;
    is_active: boolean;
    created_at: string;
}

interface DetailRowProps {
    label: string;
    value?: ReactNode;
}

function DetailRow({ label, value }: DetailRowProps) {
    return (
        <div className="grid grid-cols-3 gap-4 py-3">
            <dt className="text-sm font-medium text-gray-500">{label}</dt>
            <dd className="col-span-2 text-sm text-gray-800">
                {value ?? <span className="text-gray-400">—</span>}
            </dd>
        </div>
    );
}

interface ClientViewModalProps {
    client: ClientDetails | null;
    onClose: () => void;
}

export default function ClientViewModal({ client, onClose }: ClientViewModalProps) {
    const open = Boolean(client);

    useEffect(() => {
        if (!open) return;

        function handleKey(e: KeyboardEvent) {
            if (e.key === 'Escape') onClose();
        }

        document.addEventListener('keydown', handleKey);
        document.body.style.overflow = 'hidden';

        return () => {
            document.removeEventListener('keydown', handleKey);
            document.body.style.overflow = '';
        };
    }, [open, onClose]);

    if (!open || !client) return null;

    return (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
        >
            <div
                className="fixed inset-0 bg-black/40"
                onClick={onClose}
                aria-hidden="true"
            />

            <div className="relative w-full max-w-lg rounded-lg bg-white shadow-xl">
                <div className="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <h3 className="text-md font-semibold text-gray-800">
                        Client details
                    </h3>
                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                    >
                        <XMarkIcon className="h-5 w-5" />
                    </button>
                </div>

                <div className="max-h-[70vh] overflow-y-auto px-5 py-2">
                    {client.image_url && (
                        <div className="flex justify-center py-4">
                            <img
                                src={client.image_url}
                                alt={`${client.name} image`}
                                className="h-20 w-20 rounded-lg border border-gray-200 object-cover"
                            />
                        </div>
                    )}

                    <dl className="divide-y divide-gray-100">
                        <DetailRow label="Name" value={client.name} />
                        <DetailRow label="Email" value={client.email} />
                        <DetailRow label="Phone" value={client.phone} />
                        <DetailRow label="Address" value={client.address} />
                        <DetailRow label="Description" value={client.description} />
                        <DetailRow
                            label="Status"
                            value={
                                <span
                                    className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${
                                        client.is_active
                                            ? 'bg-green-100 text-green-700'
                                            : 'bg-red-100 text-red-700'
                                    }`}
                                >
                                    {client.is_active ? 'Active' : 'Inactive'}
                                </span>
                            }
                        />
                        <DetailRow
                            label="Created at"
                            value={formatDateTime(client.created_at)}
                        />
                    </dl>
                </div>

                <div className="flex justify-end border-t border-gray-100 px-5 py-3">
                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    );
}
