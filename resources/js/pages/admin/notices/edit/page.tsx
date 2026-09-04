import { useEffect, useState, type ReactNode } from 'react';
import { ArrowPathIcon } from '@heroicons/react/24/outline';
import DashboardLayout from '@/components/common/DashboardLayout';
import PageHeader from '@/components/common/PageHeader';
import NoticeForm from '../components/NoticeForm';
import api from '@/lib/api-client';
import { flash, errorMessage } from '@/lib/flash';
import { Notice } from '../types';

interface NoticeEditProps {
    noticeId: number | string;
}

export default function NoticeEdit({ noticeId }: NoticeEditProps) {
    const [notice, setNotice] = useState<Notice | null>(null);

    useEffect(() => {
        let cancelled = false;

        api.get(`/admin/notices/${noticeId}`)
            .then(({ data }) => {
                if (!cancelled) setNotice(data.result);
            })
            .catch((error) => {
                flash.error(errorMessage(error, 'Could not load the notice.'));
            });

        return () => {
            cancelled = true;
        };
    }, [noticeId]);

    return (
        <>
            <PageHeader title="Edit Notice" backHref="/admin/notices" />

            {notice ? (
                <NoticeForm notice={notice} />
            ) : (
                <div className="flex justify-center p-10">
                    <ArrowPathIcon className="h-6 w-6 animate-spin text-gray-400" />
                </div>
            )}
        </>
    );
}

NoticeEdit.layout = (page: ReactNode) => <DashboardLayout>{page}</DashboardLayout>;
