import { ReactNode } from 'react';
import DashboardLayout from '@/components/common/DashboardLayout';
import PageHeader from '@/components/common/PageHeader';
import NoticeForm from '../components/NoticeForm';

export default function NoticeCreate() {
    return (
        <>
            <PageHeader title="Create Notice" backHref="/admin/notices" />
            <NoticeForm />
        </>
    );
}

NoticeCreate.layout = (page: ReactNode) => <DashboardLayout>{page}</DashboardLayout>;
