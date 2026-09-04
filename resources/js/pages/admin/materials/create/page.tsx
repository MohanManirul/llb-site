import { ReactNode } from 'react';
import DashboardLayout from '@/components/common/DashboardLayout';
import PageHeader from '@/components/common/PageHeader';
import MaterialForm from '../components/MaterialForm';

export default function MaterialCreate() {
    return (
        <>
            <PageHeader title="Create Study Material" backHref="/admin/study-materials" />
            <MaterialForm />
        </>
    );
}

MaterialCreate.layout = (page: ReactNode) => <DashboardLayout>{page}</DashboardLayout>;
