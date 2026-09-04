import { useEffect, useState, type ReactNode } from 'react';
import { ArrowPathIcon } from '@heroicons/react/24/outline';
import DashboardLayout from '@/components/common/DashboardLayout';
import PageHeader from '@/components/common/PageHeader';
import RoleForm from '../components/RoleForm';
import api from '@/lib/api-client';
import { flash, errorMessage } from '@/lib/flash';
import { Role } from '../types';

interface RolesEditProps {
    roleId: number | string;
}

export default function RolesEdit({ roleId }: RolesEditProps) {
    const [role, setRole] = useState<Role | null>(null);

    useEffect(() => {
        let cancelled = false;

        api.get(`/admin/roles/${roleId}`)
            .then(({ data }) => {
                if (!cancelled) setRole(data.result);
            })
            .catch((error) => {
                flash.error(errorMessage(error, 'Could not load the role.'));
            });

        return () => {
            cancelled = true;
        };
    }, [roleId]);

    return (
        <>
            <PageHeader
                title={role ? `Edit Role — ${role.name}` : 'Edit Role'} />

            {role ? (
                <RoleForm role={role} />
            ) : (
                <div className="flex justify-center p-10">
                    <ArrowPathIcon className="h-6 w-6 animate-spin text-gray-400" />
                </div>
            )}
        </>
    );
}

RolesEdit.layout = (page: ReactNode) => <DashboardLayout>{page}</DashboardLayout>;
