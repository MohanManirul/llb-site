import { FormEvent, useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { Button, NumberStepper, Textarea, TextInput, Toggle } from '@/components/ui';
import api from '@/lib/api-client';
import { errorMessage, flash, validationErrors } from '@/lib/flash';
import { College, CollegeFormData } from '../types';

interface CollegeFormProps {
    college?: College;
}

export default function CollegeForm({ college }: CollegeFormProps) {
    const isEdit = Boolean(college);

    const [data, setDataState] = useState<CollegeFormData>({
        name_bn: college?.name_bn ?? '',
        name_en: college?.name_en ?? '',
        short_name_bn: college?.short_name_bn ?? '',
        short_name_en: college?.short_name_en ?? '',
        eiin_code: college?.eiin_code ?? '',
        college_code: college?.college_code ?? '',
        district_bn: college?.district_bn ?? '',
        district_en: college?.district_en ?? '',
        address_bn: college?.address_bn ?? '',
        address_en: college?.address_en ?? '',
        phone: college?.phone ?? '',
        email: college?.email ?? '',
        website: college?.website ?? '',
        sort_order: college?.sort_order ?? 0,
        is_active: college?.is_active ?? true,
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [saving, setSaving] = useState(false);

    const setData = <K extends keyof CollegeFormData>(field: K, value: CollegeFormData[K]) => {
        setDataState((current) => ({ ...current, [field]: value }));
    };

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        setSaving(true);
        setErrors({});

        const payload = {
            name_bn: data.name_bn,
            name_en: data.name_en || null,
            short_name_bn: data.short_name_bn || null,
            short_name_en: data.short_name_en || null,
            eiin_code: data.eiin_code || null,
            college_code: data.college_code || null,
            district_bn: data.district_bn || null,
            district_en: data.district_en || null,
            address_bn: data.address_bn || null,
            address_en: data.address_en || null,
            phone: data.phone || null,
            email: data.email || null,
            website: data.website || null,
            sort_order: data.sort_order,
            is_active: data.is_active,
        };

        try {
            if (isEdit && college) {
                await api.put(`/admin/colleges/${college.id}`, payload);
                flash.success('College updated successfully.');
            } else {
                await api.post('/admin/colleges', payload);
                flash.success('College created successfully.');
            }

            router.visit('/admin/colleges');
        } catch (error) {
            const status = (error as { response?: { status?: number } })?.response?.status;

            if (status === 422) {
                setErrors(validationErrors(error));
            } else {
                flash.error(errorMessage(error, 'Could not save the college.'));
            }
        } finally {
            setSaving(false);
        }
    };

    return (
        <form onSubmit={submit} className="max-w-3xl">
            <div className="rounded-card border border-hairline bg-white shadow-sm">
                <div className="border-b border-hairline px-5 py-4">
                    <h2 className="font-semibold text-ink">College</h2>
                    <p className="mt-0.5 text-sm text-ink-muted">
                        Students and teachers pick this college when they register.
                    </p>
                </div>

                <div className="grid gap-4 p-5 sm:grid-cols-2">
                    <TextInput
                        label="Name (Bangla)"
                        value={data.name_bn}
                        onChange={(e) => setData('name_bn', e.target.value)}
                        error={errors.name_bn}
                        required
                    />
                    <TextInput
                        label="Name (English)"
                        value={data.name_en}
                        onChange={(e) => setData('name_en', e.target.value)}
                        error={errors.name_en}
                    />
                    <TextInput
                        label="Short name (Bangla)"
                        value={data.short_name_bn}
                        onChange={(e) => setData('short_name_bn', e.target.value)}
                        error={errors.short_name_bn}
                    />
                    <TextInput
                        label="Short name (English)"
                        value={data.short_name_en}
                        onChange={(e) => setData('short_name_en', e.target.value)}
                        error={errors.short_name_en}
                    />
                    <TextInput
                        label="EIIN"
                        value={data.eiin_code}
                        onChange={(e) => setData('eiin_code', e.target.value)}
                        error={errors.eiin_code}
                        hint="Optional institution EIIN number."
                    />
                    <TextInput
                        label="NU college code"
                        value={data.college_code}
                        onChange={(e) => setData('college_code', e.target.value)}
                        error={errors.college_code}
                    />
                </div>
            </div>

            <div className="mt-5 rounded-card border border-hairline bg-white shadow-sm">
                <div className="border-b border-hairline px-5 py-4">
                    <h2 className="font-semibold text-ink">Location and contact</h2>
                </div>

                <div className="grid gap-4 p-5 sm:grid-cols-2">
                    <TextInput
                        label="District (Bangla)"
                        value={data.district_bn}
                        onChange={(e) => setData('district_bn', e.target.value)}
                        error={errors.district_bn}
                    />
                    <TextInput
                        label="District (English)"
                        value={data.district_en}
                        onChange={(e) => setData('district_en', e.target.value)}
                        error={errors.district_en}
                    />
                    <Textarea
                        label="Address (Bangla)"
                        rows={2}
                        value={data.address_bn}
                        onChange={(e) => setData('address_bn', e.target.value)}
                        error={errors.address_bn}
                    />
                    <Textarea
                        label="Address (English)"
                        rows={2}
                        value={data.address_en}
                        onChange={(e) => setData('address_en', e.target.value)}
                        error={errors.address_en}
                    />
                    <TextInput
                        label="Phone"
                        value={data.phone}
                        onChange={(e) => setData('phone', e.target.value)}
                        error={errors.phone}
                    />
                    <TextInput
                        label="Email"
                        type="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        error={errors.email}
                    />
                    <TextInput
                        label="Website"
                        value={data.website}
                        onChange={(e) => setData('website', e.target.value)}
                        error={errors.website}
                    />
                    <NumberStepper
                        label="Sort order"
                        value={String(data.sort_order)}
                        onChange={(value) => setData('sort_order', Number(value) || 0)}
                        min={0}
                        error={errors.sort_order}
                    />

                    <div className="flex items-end pb-2">
                        <Toggle
                            checked={data.is_active}
                            onChange={(checked) => setData('is_active', checked)}
                            label="Active"
                        />
                    </div>
                </div>

                <div className="flex items-center justify-end gap-3 border-t border-hairline px-5 py-4">
                    <Link href="/admin/colleges">
                        <Button type="button" variant="secondary">
                            Cancel
                        </Button>
                    </Link>
                    <Button type="submit" loading={saving}>
                        {isEdit ? 'Save changes' : 'Create college'}
                    </Button>
                </div>
            </div>
        </form>
    );
}
