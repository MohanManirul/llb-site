import { ReactNode } from 'react';
import DashboardLayout from '@/components/common/DashboardLayout';
import PageHeader from '@/components/common/PageHeader';
import ModelTestForm from '../components/ModelTestForm';

export default function ModelTestCreate() {
    return (
        <>
            <PageHeader title="Create Model Test" backHref="/admin/model-tests" />
            <div className="max-w-3xl">
                <ModelTestForm />
            </div>
        </>
    );
}

ModelTestCreate.layout = (page: ReactNode) => <DashboardLayout>{page}</DashboardLayout>;
