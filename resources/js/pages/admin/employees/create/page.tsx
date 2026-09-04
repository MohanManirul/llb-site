import { type ReactNode } from 'react';
import DashboardLayout from "@/components/common/DashboardLayout";
import PageHeader from '@/components/common/PageHeader';
import EmployeeFormComponent from "../components/EmployeeFormComponent";

export default function EmployeeCreate() {
    return (
        <>
            <PageHeader
                title="Create Employee" />

            <EmployeeFormComponent />
        </>
    );
}

EmployeeCreate.layout = (page: ReactNode) => <DashboardLayout>{page}</DashboardLayout>;
