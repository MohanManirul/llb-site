import { type ReactNode } from 'react';
import DashboardLayout from '@/components/common/DashboardLayout';
import PageHeader from '@/components/common/PageHeader';
import ProjectForm from '../components/ProjectFormComponent';

export default function ProjectCreate() {
    return (
        <>
            <PageHeader
                title="Create Project" />

            <div>
                <ProjectForm />
            </div>
        </>
    );
}

ProjectCreate.layout = (page: ReactNode) => <DashboardLayout>{page}</DashboardLayout>;
