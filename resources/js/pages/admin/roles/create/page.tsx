import { type ReactNode } from 'react';
import DashboardLayout from '@/components/common/DashboardLayout';
import PageHeader from '@/components/common/PageHeader';
import RoleForm from '../components/RoleForm';

export default function RolesCreate() {
    return (
        <>
            <PageHeader
                title="Create Role" />

            <RoleForm />
        </>
    );
}

RolesCreate.layout = (page: ReactNode) => <DashboardLayout>{page}</DashboardLayout>;
