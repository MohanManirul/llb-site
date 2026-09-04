import { type ReactNode } from 'react';
import DashboardLayout from '@/components/common/DashboardLayout';
import ProjectShow from '@/pages/admin/projects/show/page';

interface ClientProjectShowProps {
    projectId: string;
}

export default function ClientProjectShow({ projectId }: ClientProjectShowProps) {
    return <ProjectShow projectId={projectId} basePath="/client/projects" />;
}

ClientProjectShow.layout = (page: ReactNode) => <DashboardLayout>{page}</DashboardLayout>;
