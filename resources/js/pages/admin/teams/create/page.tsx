import { type ReactNode } from 'react';
import DashboardLayout from "@/components/common/DashboardLayout";
import PageHeader from '@/components/common/PageHeader';
import TeamFormComponent from "../components/TeamFormComponent";

export default function TeamCreate() {
    return (
        <>
            <PageHeader
                title="Create Team" />

            <TeamFormComponent />
        </>
    );
}

TeamCreate.layout = (page: ReactNode) => <DashboardLayout>{page}</DashboardLayout>;
