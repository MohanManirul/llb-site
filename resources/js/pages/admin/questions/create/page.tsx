import { ReactNode } from 'react';
import DashboardLayout from '@/components/common/DashboardLayout';
import PageHeader from '@/components/common/PageHeader';
import QuestionForm from '../components/QuestionForm';

export default function QuestionCreate() {
    return (
        <>
            <PageHeader title="Create Question" backHref="/admin/questions" />
            <QuestionForm />
        </>
    );
}

QuestionCreate.layout = (page: ReactNode) => <DashboardLayout>{page}</DashboardLayout>;
