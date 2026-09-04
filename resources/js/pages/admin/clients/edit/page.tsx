import { useEffect, useState, type ReactNode } from 'react';
import { ArrowPathIcon } from '@heroicons/react/24/outline';
import DashboardLayout from '@/components/common/DashboardLayout';
import PageHeader from '@/components/common/PageHeader';
import ClientForm, { type ClientFormClient } from '../components/ClientForm';
import api from '@/lib/api-client';
import { flash, errorMessage } from '@/lib/flash';

interface ClientsEditProps {
    clientId: string;
}

export default function ClientsEdit({ clientId }: ClientsEditProps) {
    const [client, setClient] = useState<ClientFormClient | null>(null);

    useEffect(() => {
        let cancelled = false;

        api.get(`/admin/clients/${clientId}`)
            .then(({ data }) => {
                if (!cancelled) setClient(data.result);
            })
            .catch((error) => {
                flash.error(errorMessage(error, 'Could not load the client.'));
            });

        return () => {
            cancelled = true;
        };
    }, [clientId]);

    return (
        <>
            <PageHeader
                title="Edit Client" />

            {client ? (
                <ClientForm client={client} />
            ) : (
                <div className="flex justify-center p-10">
                    <ArrowPathIcon className="h-6 w-6 animate-spin text-gray-400" />
                </div>
            )}
        </>
    );
}

ClientsEdit.layout = (page: ReactNode) => <DashboardLayout>{page}</DashboardLayout>;
