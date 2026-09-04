import { useState } from 'react';
import { XMarkIcon } from '@heroicons/react/24/outline';
import { Button, Modal, SearchableSelect, StatusRadio, TextInput, Textarea } from '@/components/ui';
import api from '@/lib/api-client';
import { flash, errorMessage, validationErrors } from '@/lib/flash';
import type { Department } from '../types';

interface DepartmentFormModalProps {
    show: boolean;
    department: Department | null;
    onClose: () => void;
    onSaved?: () => void;
}

interface DepartmentFormData {
    company_id: number | string;
    name: string;
    description: string;
    is_active: boolean;
}

interface CompanyOption {
    value: number | string;
    label: string;
    image_url?: string | null;
    thumbnail_url?: string | null;
}

export default function DepartmentFormModal({
    show,
    department,
    onClose,
    onSaved,
}: DepartmentFormModalProps) {
    const isEdit = Boolean(department);

    const [data, setDataState] = useState<DepartmentFormData>({
        company_id: department?.company_id ?? '',
        name: department?.name ?? '',
        description: department?.description ?? '',
        is_active: department?.is_active ?? true,
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);

    function setData<K extends keyof DepartmentFormData>(field: K, value: DepartmentFormData[K]) {
        setDataState((current) => ({ ...current, [field]: value }));
    }

    const [selectedCompany, setSelectedCompany] = useState<CompanyOption | null>(
        department?.company_id
            ? {
                  value: department.company_id,
                  label: department.company_name ?? '',
                  image_url: department.company_logo_url ?? null,
                  thumbnail_url: department.company_thumbnail_url ?? null,
              }
            : null,
    );

    function changeCompany(value: number | string, option?: CompanyOption | null) {
        setData('company_id', value);
        setSelectedCompany(value ? (option ?? null) : null);
    }

    async function submit(e: React.FormEvent) {
        e.preventDefault();
        setProcessing(true);
        setErrors({});

        const payload = {
            company_id: data.company_id,
            name: data.name,
            description: data.description ?? '',
            is_active: Boolean(data.is_active),
        };

        try {
            if (isEdit) {
                await api.put(`/admin/departments/${department!.id}`, payload);
            } else {
                await api.post('/admin/departments', payload);
            }

            flash.success(
                isEdit
                    ? 'Department updated successfully.'
                    : 'Department created successfully.',
            );
            onSaved?.();
        } catch (error) {
            if ((error as { response?: { status?: number } })?.response?.status === 422) {
                setErrors(validationErrors(error));
            } else {
                flash.error(
                    errorMessage(error, 'Could not save the department.'),
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
                        {isEdit ? 'Edit Department' : 'Create Department'}
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
                    <SearchableSelect
                        label="Company"
                        required
                        value={data.company_id}
                        onChange={changeCompany}
                        error={errors.company_id}
                        placeholder="Select a Company"
                        searchPlaceholder="Search companies"
                        fetchUrl="/v1/admin/companies/search"
                        selectedOption={selectedCompany}
                    />

                    <TextInput
                        label="Name"
                        name="name"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        error={errors.name}
                        placeholder="Enter department name"
                        required
                    />

                    <Textarea
                        label="Description"
                        name="description"
                        rows={4}
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        error={errors.description}
                        placeholder="Enter a short description"
                    />

                    <StatusRadio
                        value={data.is_active}
                        onChange={(value) => setData('is_active', value)}
                        error={errors.is_active}
                    />
                </div>

                <div className="flex justify-end gap-3 border-t border-gray-200 px-6 py-4">
                    <Button type="submit" disabled={processing}>
                        {isEdit ? 'Save Changes' : 'Create Department'}
                    </Button>
                </div>
            </form>
        </Modal>
    );
}
