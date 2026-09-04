import { useEffect, useState, type ReactNode } from 'react';
import { ArrowPathIcon } from '@heroicons/react/24/outline';
import DashboardLayout from '@/components/common/DashboardLayout';
import PageHeader from '@/components/common/PageHeader';
import SubjectForm from '../components/SubjectForm';
import api from '@/lib/api-client';
import { flash, errorMessage } from '@/lib/flash';
import { Subject } from '../types';

interface SubjectEditProps {
    subjectId: number | string;
}

export default function SubjectEdit({ subjectId }: SubjectEditProps) {
    const [subject, setSubject] = useState<Subject | null>(null);

    useEffect(() => {
        let cancelled = false;

        api.get(`/admin/subjects/${subjectId}`)
            .then(({ data }) => {
                if (!cancelled) setSubject(data.result);
            })
            .catch((error) => {
                flash.error(errorMessage(error, 'Could not load the subject.'));
            });

        return () => {
            cancelled = true;
        };
    }, [subjectId]);

    return (
        <>
            <PageHeader title="Edit Subject" backHref="/admin/academic/subjects" />

            {subject ? (
                <SubjectForm subject={subject} />
            ) : (
                <div className="flex justify-center p-10">
                    <ArrowPathIcon className="h-6 w-6 animate-spin text-gray-400" />
                </div>
            )}
        </>
    );
}

SubjectEdit.layout = (page: ReactNode) => <DashboardLayout>{page}</DashboardLayout>;
