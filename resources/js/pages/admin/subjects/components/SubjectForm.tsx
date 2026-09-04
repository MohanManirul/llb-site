import { FormEvent, useEffect, useMemo, useState } from 'react';
import { Link, router } from '@inertiajs/react';
import {
    Button,
    NumberStepper,
    SelectInput,
    Textarea,
    TextInput,
    Toggle,
} from '@/components/ui';
import api from '@/lib/api-client';
import type { ApiEnvelope } from '@/lib/api-types';
import { errorMessage, flash, validationErrors } from '@/lib/flash';
import { ProgramOption, Subject, SubjectFormData } from '../types';

interface SubjectFormProps {
    subject?: Subject;
}

export default function SubjectForm({ subject }: SubjectFormProps) {
    const isEdit = Boolean(subject);

    const [programs, setPrograms] = useState<ProgramOption[]>([]);
    const [data, setDataState] = useState<SubjectFormData>({
        program_id: subject ? String(subject.program_id) : '',
        program_level_id: subject?.program_level_id ? String(subject.program_level_id) : '',
        code: subject?.code ?? '',
        name_bn: subject?.name_bn ?? '',
        name_en: subject?.name_en ?? '',
        description_bn: subject?.description_bn ?? '',
        description_en: subject?.description_en ?? '',
        marks: subject?.marks ? String(subject.marks) : '',
        sort_order: subject?.sort_order ?? 0,
        is_active: subject?.is_active ?? true,
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        let cancelled = false;

        api.get<ApiEnvelope<ProgramOption[]>>('/admin/programs/options')
            .then(({ data: response }) => {
                if (!cancelled) setPrograms(response.result);
            })
            .catch((error) => {
                if (!cancelled) flash.error(errorMessage(error, 'Could not load programs.'));
            });

        return () => {
            cancelled = true;
        };
    }, []);

    const setData = <K extends keyof SubjectFormData>(field: K, value: SubjectFormData[K]) => {
        setDataState((current) => ({ ...current, [field]: value }));
    };

    const selectedProgram = useMemo(
        () => programs.find((program) => String(program.value) === data.program_id) ?? null,
        [programs, data.program_id],
    );

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        setSaving(true);
        setErrors({});

        const payload = {
            program_id: data.program_id ? Number(data.program_id) : null,
            program_level_id:
                selectedProgram?.has_levels && data.program_level_id
                    ? Number(data.program_level_id)
                    : null,
            code: data.code || null,
            name_bn: data.name_bn,
            name_en: data.name_en,
            description_bn: data.description_bn || null,
            description_en: data.description_en || null,
            marks: data.marks ? Number(data.marks) : null,
            sort_order: data.sort_order,
            is_active: data.is_active,
        };

        try {
            if (isEdit && subject) {
                await api.put(`/admin/subjects/${subject.id}`, payload);
                flash.success('Subject updated successfully.');
            } else {
                await api.post('/admin/subjects', payload);
                flash.success('Subject created successfully.');
            }

            router.visit('/admin/academic/subjects');
        } catch (error) {
            const status = (error as { response?: { status?: number } })?.response?.status;

            if (status === 422) {
                setErrors(validationErrors(error));
            } else {
                flash.error(errorMessage(error, 'Could not save the subject.'));
            }
        } finally {
            setSaving(false);
        }
    };

    return (
        <form onSubmit={submit} className="max-w-3xl">
            <div className="rounded-card border border-hairline bg-white shadow-sm">
                <div className="border-b border-hairline px-5 py-4">
                    <h2 className="font-semibold text-ink">Placement</h2>
                    <p className="mt-0.5 text-sm text-ink-muted">
                        Which program (and year/part, where the program has them) this subject
                        belongs to.
                    </p>
                </div>

                <div className="grid gap-4 p-5 sm:grid-cols-2">
                    <SelectInput
                        label="Program"
                        value={data.program_id}
                        onChange={(e) => {
                            setData('program_id', e.target.value);
                            setData('program_level_id', '');
                        }}
                        error={errors.program_id}
                        required
                    >
                        <option value="">Select a program</option>
                        {programs.map((program) => (
                            <option key={program.value} value={program.value}>
                                {program.label}
                            </option>
                        ))}
                    </SelectInput>

                    {selectedProgram?.has_levels && (
                        <SelectInput
                            label={selectedProgram.level_label.en ?? 'Level'}
                            value={data.program_level_id}
                            onChange={(e) => setData('program_level_id', e.target.value)}
                            error={errors.program_level_id}
                            hint="Leave empty if the subject spans every level."
                        >
                            <option value="">All levels</option>
                            {selectedProgram.levels.map((level) => (
                                <option key={level.value} value={level.value}>
                                    {level.label}
                                </option>
                            ))}
                        </SelectInput>
                    )}
                </div>
            </div>

            <div className="mt-5 rounded-card border border-hairline bg-white shadow-sm">
                <div className="border-b border-hairline px-5 py-4">
                    <h2 className="font-semibold text-ink">Subject</h2>
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
                        required
                    />
                    <TextInput
                        label="Paper code"
                        value={data.code}
                        onChange={(e) => setData('code', e.target.value)}
                        error={errors.code}
                        hint="Optional NU paper code."
                    />
                    <TextInput
                        label="Marks"
                        type="number"
                        value={data.marks}
                        onChange={(e) => setData('marks', e.target.value)}
                        error={errors.marks}
                        hint="Full marks, e.g. 100."
                    />
                    <Textarea
                        label="Description (Bangla)"
                        rows={3}
                        value={data.description_bn}
                        onChange={(e) => setData('description_bn', e.target.value)}
                        error={errors.description_bn}
                    />
                    <Textarea
                        label="Description (English)"
                        rows={3}
                        value={data.description_en}
                        onChange={(e) => setData('description_en', e.target.value)}
                        error={errors.description_en}
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
                    <Link href="/admin/academic/subjects">
                        <Button type="button" variant="secondary">
                            Cancel
                        </Button>
                    </Link>
                    <Button type="submit" loading={saving}>
                        {isEdit ? 'Save changes' : 'Create subject'}
                    </Button>
                </div>
            </div>
        </form>
    );
}
