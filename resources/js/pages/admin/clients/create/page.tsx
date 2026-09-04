import { type ReactNode } from 'react';
import DashboardLayout from '@/components/common/DashboardLayout';
import PageHeader from '@/components/common/PageHeader';
import ClientForm from '../components/ClientForm';

export default function ClientsCreate() {
    return (
        <>
            <PageHeader
                title="Create Client" />

            <ClientForm />
        </>
    );
}

ClientsCreate.layout = (page: ReactNode) => <DashboardLayout>{page}</DashboardLayout>;
