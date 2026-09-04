import { useCallback, useEffect, useState, type ReactNode } from 'react';
import { ArrowPathIcon, ArrowUturnLeftIcon, CheckCircleIcon } from '@heroicons/react/24/outline';
import DashboardLayout from '@/components/common/DashboardLayout';
import PageHeader from '@/components/common/PageHeader';
import { Button, StatusBadge } from '@/components/ui';
import api from '@/lib/api-client';
import type { ApiEnvelope } from '@/lib/api-types';
import { errorMessage, flash } from '@/lib/flash';
import usePermissions from '@/hooks/usePermissions';
import ModelTestForm from '../components/ModelTestForm';
import QuestionBuilder from '../components/QuestionBuilder';
import { ModelTest } from '../types';

interface ModelTestEditProps {
    modelTestId: number | string;
}

export default function ModelTestEdit({ modelTestId }: ModelTestEditProps) {
    const { can } = usePermissions();
    const canEdit = can('edit model tests');
    const canPublish = can('publish model tests');

    const [modelTest, setModelTest] = useState<ModelTest | null>(null);
    const [toggling, setToggling] = useState(false);

    const load = useCallback(async () => {
        try {
            const { data } = await api.get<ApiEnvelope<ModelTest>>(`/admin/model-tests/${modelTestId}`);
            setModelTest(data.result);
        } catch (error) {
            flash.error(errorMessage(error, 'Could not load the model test.'));
        }
    }, [modelTestId]);

    useEffect(() => {
        load();
    }, [load]);

    const togglePublish = async () => {
        if (!modelTest) return;

        setToggling(true);

        const publishing = modelTest.status !== 'published';

        try {
            await api.patch(`/admin/model-tests/${modelTest.id}/${publishing ? 'publish' : 'unpublish'}`);
            flash.success(publishing ? 'Model test published.' : 'Model test unpublished.');
            await load();
        } catch (error) {
            flash.error(errorMessage(error, 'Could not change the publish state.'));
        } finally {
            setToggling(false);
        }
    };

    const merge = (next: ModelTest) => {
        setModelTest((current) => (current ? { ...current, ...next } : next));
    };

    return (
        <>
            <PageHeader
                title="Edit Model Test"
                backHref="/admin/model-tests"
                subtitle={
                    modelTest ? (
                        <>
                            <StatusBadge status={modelTest.status} />
                            <span>
                                {modelTest.questions?.length ?? 0} questions ·{' '}
                                {modelTest.attempts_count ?? 0} attempts
                            </span>
                        </>
                    ) : undefined
                }
                action={
                    modelTest && canPublish ? (
                        modelTest.status === 'published' ? (
                            <Button size="sm" variant="secondary" onClick={togglePublish} loading={toggling}>
                                <ArrowUturnLeftIcon className="h-4 w-4" />
                                Unpublish
                            </Button>
                        ) : (
                            <Button
                                size="sm"
                                onClick={togglePublish}
                                loading={toggling}
                                disabled={(modelTest.questions?.length ?? 0) === 0}
                            >
                                <CheckCircleIcon className="h-4 w-4" />
                                Publish
                            </Button>
                        )
                    ) : undefined
                }
            />

            {modelTest ? (
                <div className="grid gap-5 xl:grid-cols-[minmax(0,26rem)_1fr]">
                    <ModelTestForm modelTest={modelTest} onSaved={merge} />
                    <QuestionBuilder modelTest={modelTest} onChange={merge} canEdit={canEdit} />
                </div>
            ) : (
                <div className="flex justify-center p-10">
                    <ArrowPathIcon className="h-6 w-6 animate-spin text-gray-400" />
                </div>
            )}
        </>
    );
}

ModelTestEdit.layout = (page: ReactNode) => <DashboardLayout wide>{page}</DashboardLayout>;
