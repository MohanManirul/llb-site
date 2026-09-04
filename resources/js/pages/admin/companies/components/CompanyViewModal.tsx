import { useEffect, type ReactNode } from 'react';
import { XMarkIcon } from '@heroicons/react/24/outline';
import { Link } from '@inertiajs/react';
import type { Company } from '../types';

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

interface CompanyViewModalProps {
    company: Company | null;
    onClose: () => void;
}

export default function CompanyViewModal({ company, onClose }: CompanyViewModalProps) {
    const open = Boolean(company);

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

    if (!open || !company) return null;

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
                        Company details
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
                    {company.logo_url && (
                        <div className="flex justify-center py-4">
                            <img
                                src={company.logo_url}
                                alt={`${company.name} logo`}
                                className="h-20 w-20 rounded-lg border border-gray-200 object-cover"
                            />
                        </div>
                    )}

                    <dl className="divide-y divide-gray-100">
                        <DetailRow label="Name" value={company.name} />
                        <DetailRow label="Email" value={company.email} />
                        <DetailRow label="Phone" value={company.phone} />
                        <DetailRow
                            label="Website"
                            value={
                                company.website && (
                                    <Link
                                        href={company.website}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="text-blue-600 hover:underline"
                                    >
                                        {company.website}
                                    </Link>
                                )
                            }
                        />
                        <DetailRow label="Address" value={company.address} />
                        <DetailRow
                            label="Status"
                            value={
                                <span
                                    className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${
                                        company.is_active
                                            ? "bg-green-100 text-green-700"
                                            : "bg-red-100 text-red-700"
                                    }`}
                                >
                                    {company.is_active ? "Active" : "Inactive"}
                                </span>
                            }
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
