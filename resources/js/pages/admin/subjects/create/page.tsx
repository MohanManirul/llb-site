import { ReactNode } from 'react';
import DashboardLayout from '@/components/common/DashboardLayout';
import PageHeader from '@/components/common/PageHeader';
import SubjectForm from '../components/SubjectForm';

export default function SubjectCreate() {
    return (
        <>
            <PageHeader title="Create Subject" backHref="/admin/academic/subjects" />
            <SubjectForm />
        </>
    );
}

SubjectCreate.layout = (page: ReactNode) => <DashboardLayout>{page}</DashboardLayout>;
