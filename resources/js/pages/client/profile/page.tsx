import { type ReactNode } from 'react';
import DashboardLayout from '@/components/common/DashboardLayout';
import Profile from '@/pages/admin/profile/page';

export default function ClientProfile() {
    return <Profile />;
}

ClientProfile.layout = (page: ReactNode) => <DashboardLayout>{page}</DashboardLayout>;
