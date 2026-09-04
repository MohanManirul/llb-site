import { useState } from 'react';
import { XMarkIcon } from '@heroicons/react/24/outline';
import { Button, ImageUpload, Modal, StatusRadio, TextInput, EmailInput } from '@/components/ui';
import api from '@/lib/api-client';
import { flash, errorMessage, validationErrors } from '@/lib/flash';
import type { Company } from '../types';

interface CompanyFormModalProps {
    show: boolean;
    company: Company | null;
    onClose: () => void;
    onSaved?: () => void;
}

interface CompanyFormData {
    name: string;
    logo: File | null;
    email: string;
    phone: string;
    website: string;
    address: string;
    is_active: boolean;
}

export default function CompanyFormModal({ show, company, onClose, onSaved }: CompanyFormModalProps) {
    const isEdit = Boolean(company);

    const [data, setDataState] = useState<CompanyFormData>({
        name: company?.name ?? '',
        logo: null,
        email: company?.email ?? '',
        phone: company?.phone ?? '',
        website: company?.website ?? '',
        address: company?.address ?? '',
        is_active: company?.is_active ?? true,
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);

    function setData<K extends keyof CompanyFormData>(field: K, value: CompanyFormData[K]) {
        setDataState((current) => ({ ...current, [field]: value }));
    }

    async function submit(e: React.FormEvent) {
        e.preventDefault();
        setProcessing(true);
        setErrors({});

        const payload = new FormData();
        payload.append('name', data.name);
        payload.append('email', data.email ?? '');
        payload.append('phone', data.phone ?? '');
        payload.append('website', data.website ?? '');
        payload.append('address', data.address ?? '');
        const isActiveValue = data.is_active ? '1' : '0';
        console.log('Form data is_active:', data.is_active, 'Type:', typeof data.is_active, 'Sending as:', isActiveValue);
        payload.append('is_active', isActiveValue);
        if (data.logo) {
            payload.append('logo', data.logo);
        }

        if (isEdit) {
            payload.append('_method', 'put');
        }

        try {
            await api.post(
                isEdit ? `/admin/companies/${company!.id}` : '/admin/companies',
                payload
            );

            flash.success(
                isEdit
                    ? 'Company updated successfully.'
                    : 'Company created successfully.'
            );
            onSaved?.();
        } catch (error) {
            if ((error as { response?: { status?: number } })?.response?.status === 422) {
                setErrors(validationErrors(error));
            } else {
                flash.error(errorMessage(error, 'Could not save the company.'));
            }
            setProcessing(false);
        }
    }

    return (
        <Modal show={show} onClose={processing ? undefined : onClose} maxWidth="lg">
            <form onSubmit={submit}>
                <div className="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                    <h3 className="text-base font-semibold text-gray-900">
                        {isEdit ? 'Edit Company' : 'Create Company'}
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
                    <ImageUpload
                        layout="inline"
                        name="logo"
                        label="Upload Image"
                        value={company?.logo_url ?? null}
                        onChange={(file) => setData('logo', file)}
                        error={errors.logo}
                        helperText="JPG, PNG or WebP, max 2MB. Click the image to upload."
                    />

                    <TextInput
                        label="Name"
                        name="name"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        error={errors.name}
                        placeholder="Enter company name"
                        required
                        autoFocus
                    />

                    <div className="grid grid-cols-1 gap-x-5 gap-y-5 sm:grid-cols-2">
                        <EmailInput
                            label="Email"
                            name="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            error={errors.email}
                            placeholder="name@company.com"
                        />

                        <TextInput
                            label="Phone"
                            name="phone"
                            value={data.phone}
                            onChange={(e) => setData('phone', e.target.value)}
                            error={errors.phone}
                            placeholder="+880 1XXX-XXXXXX"
                        />
                    </div>

                    <TextInput
                        label="Website"
                        name="website"
                        type="url"
                        value={data.website}
                        onChange={(e) => setData('website', e.target.value)}
                        error={errors.website}
                        placeholder="https://www.company.com"
                    />

                    <TextInput
                        label="Address"
                        name="address"
                        value={data.address}
                        onChange={(e) => setData('address', e.target.value)}
                        error={errors.address}
                        placeholder="Enter address"
                    />

                    <StatusRadio
                        value={data.is_active}
                        onChange={(value) => setData('is_active', value)}
                        error={errors.is_active}
                    />
                </div>

                <div className="flex justify-end gap-3 border-t border-gray-200 px-6 py-4">
                    <Button type="submit" disabled={processing}>
                        {isEdit ? 'Save Changes' : 'Create Company'}
                    </Button>
                </div>
            </form>
        </Modal>
    );
}
