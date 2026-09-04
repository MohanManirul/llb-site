import { useEffect, useState, type ReactNode } from 'react';
import { ArrowPathIcon } from '@heroicons/react/24/outline';
import DashboardLayout from '@/components/common/DashboardLayout';
import PageHeader from '@/components/common/PageHeader';
import ProjectForm from '../components/ProjectFormComponent';
import api from '@/lib/api-client';
import { flash, errorMessage } from '@/lib/flash';
import type { ProjectDetail } from '../types';

interface ProjectEditProps {
    projectId: string;
}

export default function ProjectEdit({ projectId }: ProjectEditProps) {
    const [project, setProject] = useState<ProjectDetail | null>(null);

    useEffect(() => {
        let cancelled = false;

        api.get(`/admin/projects/${projectId}`)
            .then(({ data }) => {
                if (!cancelled) setProject(data.result);
            })
            .catch((error) => {
                flash.error(errorMessage(error, 'Could not load the project.'));
            });

        return () => {
            cancelled = true;
        };
    }, [projectId]);

    return (
        <>
            <PageHeader
                title="Edit Project" />

            <div>
                {project ? (
                    <ProjectForm project={project} />
                ) : (
                    <div className="flex justify-center p-10">
                        <ArrowPathIcon className="h-6 w-6 animate-spin text-gray-400" />
                    </div>
                )}
            </div>
        </>
    );
}

ProjectEdit.layout = (page: ReactNode) => <DashboardLayout>{page}</DashboardLayout>;
