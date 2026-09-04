import { type ReactNode } from 'react';
import DashboardLayout from '@/components/common/DashboardLayout';
import PageHeader from '@/components/common/PageHeader';
import UserForm from '../components/UserForm';

export default function UsersCreate() {
    return (
        <>
            <PageHeader
                title="Create User" />

            <UserForm />
        </>
    );
}

UsersCreate.layout = (page: ReactNode) => <DashboardLayout>{page}</DashboardLayout>;
