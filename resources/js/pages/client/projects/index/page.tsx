import { type ReactNode } from 'react';
import DashboardLayout from '@/components/common/DashboardLayout';
import PageHeader from '@/components/common/PageHeader';
import ClientProjectsTable from '@/pages/admin/dashboard/components/ClientProjectsTable';

export default function ClientProjects() {
    return (
        <>
            <PageHeader
                title="Projects" />

            <ClientProjectsTable />
        </>
    );
}

ClientProjects.layout = (page: ReactNode) => <DashboardLayout wide>{page}</DashboardLayout>;
