import { type ReactNode } from 'react';
import DashboardLayout from '@/components/common/DashboardLayout';
import Dashboard from '@/pages/admin/dashboard/page';

export default function ClientDashboard() {
    return <Dashboard reportUrl="/client/dashboard/report" />;
}

ClientDashboard.layout = (page: ReactNode) => <DashboardLayout>{page}</DashboardLayout>;
