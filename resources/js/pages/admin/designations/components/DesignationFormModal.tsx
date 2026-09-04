import { useState } from 'react';
import { XMarkIcon } from '@heroicons/react/24/outline';
import { Button, Modal, StatusRadio, TextInput } from '@/components/ui';
import api from '@/lib/api-client';
import { flash, errorMessage, validationErrors } from '@/lib/flash';
import type { Designation } from '../types';

interface DesignationFormModalProps {
    show: boolean;
    designation: Designation | null;
    onClose: () => void;
    onSaved?: () => void;
}

interface DesignationFormData {
    name: string;
    isActive: boolean;
}

export default function DesignationFormModal({
    show,
    designation,
    onClose,
    onSaved,
}: DesignationFormModalProps) {
    const isEdit = Boolean(designation);

    const [data, setDataState] = useState<DesignationFormData>({
        name: designation?.name ?? '',
        isActive: designation?.is_active ?? true,
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);

    function setData<K extends keyof DesignationFormData>(field: K, value: DesignationFormData[K]) {
        setDataState((current) => ({ ...current, [field]: value }));
    }

    async function submit(e: React.FormEvent) {
        e.preventDefault();
        setProcessing(true);
        setErrors({});

        const payload = {
            name: data.name,
            is_active: Boolean(data.isActive),
        };

        try {
            if (isEdit) {
                await api.put(`/admin/designations/${designation!.id}`, payload);
            } else {
                await api.post('/admin/designations', payload);
            }

            flash.success(
                isEdit
                    ? 'Designation updated successfully.'
                    : 'Designation created successfully.'
            );
            onSaved?.();
        } catch (error) {
            if ((error as { response?: { status?: number } })?.response?.status === 422) {
                setErrors(validationErrors(error));
            } else {
                flash.error(
                    errorMessage(error, 'Could not save the designation.')
                );
            }
            setProcessing(false);
        }
    }

    return (
        <Modal
            show={show}
            onClose={processing ? undefined : onClose}
            maxWidth="lg"
        >
            <form onSubmit={submit}>
                <div className="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                    <h3 className="text-base font-semibold text-gray-900">
                        {isEdit ? 'Edit Designation' : 'Create Designation'}
                    </h3>
                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded-md border border-gray-200 p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                        aria-label="Close"
                    >
                        <XMarkIcon className="h-4 w-4" />
                    </button>
                </div>

                <div className="max-h-[70vh] space-y-5 overflow-y-auto px-6 py-5">
                    <TextInput
                        label="Name"
                        name="name"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        error={errors.name}
                        placeholder="Enter designation name"
                        required
                        autoFocus
                    />

                    <StatusRadio
                        value={data.isActive}
                        onChange={(value) => setData('isActive', value)}
                        error={errors.is_active}
                    />
                </div>

                <div className="flex justify-end gap-3 border-t border-gray-200 px-6 py-4">
                    <Button type="submit" disabled={processing}>
                        {isEdit ? 'Save Changes' : 'Create Designation'}
                    </Button>
                </div>
            </form>
        </Modal>
    );
}
