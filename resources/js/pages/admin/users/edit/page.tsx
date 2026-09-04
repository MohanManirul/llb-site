import { useEffect, useState, type ReactNode } from 'react';
import { ArrowPathIcon } from '@heroicons/react/24/outline';
import DashboardLayout from '@/components/common/DashboardLayout';
import PageHeader from '@/components/common/PageHeader';
import UserForm from '../components/UserForm';
import api from '@/lib/api-client';
import { flash, errorMessage } from '@/lib/flash';
import { User } from '../types';

interface UsersEditProps {
    userId: number | string;
}

export default function UsersEdit({ userId }: UsersEditProps) {
    const [user, setUser] = useState<User | null>(null);

    useEffect(() => {
        let cancelled = false;

        api.get(`/admin/users/${userId}`)
            .then(({ data }) => {
                if (!cancelled) setUser(data.result);
            })
            .catch((error) => {
                flash.error(errorMessage(error, 'Could not load the user.'));
            });

        return () => {
            cancelled = true;
        };
    }, [userId]);

    return (
        <>
            <PageHeader
                title="Edit User" />

            {user ? (
                <UserForm user={user} />
            ) : (
                <div className="flex justify-center p-10">
                    <ArrowPathIcon className="h-6 w-6 animate-spin text-gray-400" />
                </div>
            )}
        </>
    );
}

UsersEdit.layout = (page: ReactNode) => <DashboardLayout>{page}</DashboardLayout>;
