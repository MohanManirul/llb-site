import { useEffect, useState, type ReactNode } from 'react';
import { ArrowPathIcon } from '@heroicons/react/24/outline';
import DashboardLayout from '@/components/common/DashboardLayout';
import PageHeader from '@/components/common/PageHeader';
import CollegeForm from '../components/CollegeForm';
import api from '@/lib/api-client';
import { errorMessage, flash } from '@/lib/flash';
import { College } from '../types';

interface CollegeEditProps {
    collegeId: number | string;
}

export default function CollegeEdit({ collegeId }: CollegeEditProps) {
    const [college, setCollege] = useState<College | null>(null);

    useEffect(() => {
        let cancelled = false;

        api.get(`/admin/colleges/${collegeId}`)
            .then(({ data }) => {
                if (!cancelled) setCollege(data.result);
            })
            .catch((error) => {
                flash.error(errorMessage(error, 'Could not load the college.'));
            });

        return () => {
            cancelled = true;
        };
    }, [collegeId]);

    return (
        <>
            <PageHeader title="Edit College" backHref="/admin/colleges" />

            {college ? (
                <CollegeForm college={college} />
            ) : (
                <div className="flex justify-center p-10">
                    <ArrowPathIcon className="h-6 w-6 animate-spin text-gray-400" />
                </div>
            )}
        </>
    );
}

CollegeEdit.layout = (page: ReactNode) => <DashboardLayout>{page}</DashboardLayout>;
