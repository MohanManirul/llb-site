import { ReactNode } from 'react';
import DashboardLayout from '@/components/common/DashboardLayout';
import PageHeader from '@/components/common/PageHeader';
import CollegeForm from '../components/CollegeForm';

export default function CollegeCreate() {
    return (
        <>
            <PageHeader title="Create College" backHref="/admin/colleges" />
            <CollegeForm />
        </>
    );
}

CollegeCreate.layout = (page: ReactNode) => <DashboardLayout>{page}</DashboardLayout>;
