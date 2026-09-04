import { useEffect, useState, type ReactNode } from 'react';
import { ArrowPathIcon } from '@heroicons/react/24/outline';
import DashboardLayout from '@/components/common/DashboardLayout';
import PageHeader from '@/components/common/PageHeader';
import QuestionForm from '../components/QuestionForm';
import api from '@/lib/api-client';
import type { ApiEnvelope } from '@/lib/api-types';
import { flash, errorMessage } from '@/lib/flash';
import { Question } from '../types';

interface QuestionEditProps {
    questionId: number | string;
}

export default function QuestionEdit({ questionId }: QuestionEditProps) {
    const [question, setQuestion] = useState<Question | null>(null);

    useEffect(() => {
        let cancelled = false;

        api.get<ApiEnvelope<Question>>(`/admin/questions/${questionId}`)
            .then(({ data }) => {
                if (!cancelled) setQuestion(data.result);
            })
            .catch((error) => {
                flash.error(errorMessage(error, 'Could not load the question.'));
            });

        return () => {
            cancelled = true;
        };
    }, [questionId]);

    return (
        <>
            <PageHeader title="Edit Question" backHref="/admin/questions" />

            {question ? (
                <QuestionForm question={question} />
            ) : (
                <div className="flex justify-center p-10">
                    <ArrowPathIcon className="h-6 w-6 animate-spin text-gray-400" />
                </div>
            )}
        </>
    );
}

QuestionEdit.layout = (page: ReactNode) => <DashboardLayout>{page}</DashboardLayout>;
