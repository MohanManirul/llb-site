import { useCallback, useEffect, useState, type ReactNode } from 'react';
import { ArrowPathIcon } from '@heroicons/react/24/outline';
import DashboardLayout from '@/components/common/DashboardLayout';
import PageHeader from '@/components/common/PageHeader';
import MaterialForm from '../components/MaterialForm';
import FilesCard from '../components/FilesCard';
import api from '@/lib/api-client';
import { flash, errorMessage } from '@/lib/flash';
import { StudyMaterial } from '../types';

interface MaterialEditProps {
    materialId: number | string;
}

export default function MaterialEdit({ materialId }: MaterialEditProps) {
    const [material, setMaterial] = useState<StudyMaterial | null>(null);

    const load = useCallback(() => {
        api.get(`/admin/study-materials/${materialId}`)
            .then(({ data }) => setMaterial(data.result))
            .catch((error) => {
                flash.error(errorMessage(error, 'Could not load the material.'));
            });
    }, [materialId]);

    useEffect(() => {
        load();
    }, [load]);

    return (
        <>
            <PageHeader title="Edit Study Material" backHref="/admin/study-materials" />

            {material ? (
                <>
                    <MaterialForm key={material.id} material={material} />
                    <FilesCard material={material} onChanged={load} />
                </>
            ) : (
                <div className="flex justify-center p-10">
                    <ArrowPathIcon className="h-6 w-6 animate-spin text-gray-400" />
                </div>
            )}
        </>
    );
}

MaterialEdit.layout = (page: ReactNode) => <DashboardLayout>{page}</DashboardLayout>;
