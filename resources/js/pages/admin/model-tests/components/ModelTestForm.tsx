import { FormEvent, useEffect, useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { Button, SelectInput, Textarea, TextInput } from '@/components/ui';
import api from '@/lib/api-client';
import type { ApiEnvelope } from '@/lib/api-types';
import { errorMessage, flash, validationErrors } from '@/lib/flash';
import { ModelTest, ModelTestFilterOptions, ModelTestFormData, ProgramOption } from '../types';

interface ModelTestFormProps {
    modelTest?: ModelTest;
    onSaved?: (modelTest: ModelTest) => void;
}

export default function ModelTestForm({ modelTest, onSaved }: ModelTestFormProps) {
    const isEdit = Boolean(modelTest);

    const [programs, setPrograms] = useState<ProgramOption[]>([]);
    const [enums, setEnums] = useState<ModelTestFilterOptions | null>(null);

    const [data, setDataState] = useState<ModelTestFormData>({
        title_bn: modelTest?.title_bn ?? '',
        title_en: modelTest?.title_en ?? '',
        description_bn: modelTest?.description_bn ?? '',
        description_en: modelTest?.description_en ?? '',
        program_id: modelTest?.program_id ? String(modelTest.program_id) : '',
        exam_stage: modelTest?.exam_stage ?? '',
        duration_minutes: modelTest ? String(modelTest.duration_minutes) : '60',
        negative_mark: modelTest ? String(Number(modelTest.negative_mark)) : '0.25',
    });

    const [errors, setErrors] = useState<Record<string, string>>({});
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        let cancelled = false;

        Promise.all([
            api.get<ApiEnvelope<ProgramOption[]>>('/admin/programs/options'),
            api.get<ApiEnvelope<ModelTestFilterOptions>>('/admin/model-tests/filters'),
        ])
            .then(([programsRes, enumsRes]) => {
                if (cancelled) return;
                setPrograms(programsRes.data.result);
                setEnums(enumsRes.data.result);
            })
            .catch((error) => {
                if (!cancelled) flash.error(errorMessage(error, 'Could not load form options.'));
            });

        return () => {
            cancelled = true;
        };
    }, []);

    const setData = <K extends keyof ModelTestFormData>(field: K, value: ModelTestFormData[K]) => {
        setDataState((current) => ({ ...current, [field]: value }));
    };

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        setSaving(true);
        setErrors({});

        const payload = {
            title_bn: data.title_bn,
            title_en: data.title_en || null,
            description_bn: data.description_bn || null,
            description_en: data.description_en || null,
            program_id: data.program_id || null,
            exam_stage: data.exam_stage || null,
            duration_minutes: data.duration_minutes,
            negative_mark: data.negative_mark === '' ? 0 : data.negative_mark,
        };

        try {
            if (isEdit && modelTest) {
                const { data: response } = await api.put<ApiEnvelope<ModelTest>>(
                    `/admin/model-tests/${modelTest.id}`,
                    payload,
                );
                flash.success('Model test updated successfully.');
                onSaved?.(response.result);
            } else {
                const { data: response } = await api.post<ApiEnvelope<ModelTest>>('/admin/model-tests', payload);
                flash.success('Model test created. Now add its questions.');
                router.visit(`/admin/model-tests/${response.result.id}/edit`);
            }
        } catch (error) {
            const status = (error as { response?: { status?: number } })?.response?.status;

            if (status === 422) {
                setErrors(validationErrors(error));
            } else {
                flash.error(errorMessage(error, 'Could not save the model test.'));
            }
        } finally {
            setSaving(false);
        }
    };

    return (
        <form onSubmit={submit}>
            <div className="rounded-card border border-hairline bg-white shadow-sm">
                <div className="border-b border-hairline px-5 py-4">
                    <h2 className="font-semibold text-ink">Details</h2>
                </div>

                <div className="grid gap-4 p-5">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <TextInput
                            label="Title (Bangla)"
                            value={data.title_bn}
                            onChange={(e) => setData('title_bn', e.target.value)}
                            error={errors.title_bn}
                            required
                        />
                        <TextInput
                            label="Title (English)"
                            value={data.title_en}
                            onChange={(e) => setData('title_en', e.target.value)}
                            error={errors.title_en}
                        />
                    </div>

                    <Textarea
                        label="Description (Bangla)"
                        rows={3}
                        value={data.description_bn}
                        onChange={(e) => setData('description_bn', e.target.value)}
                        error={errors.description_bn}
                        maxCharacters={2000}
                    />

                    <Textarea
                        label="Description (English)"
                        rows={3}
                        value={data.description_en}
                        onChange={(e) => setData('description_en', e.target.value)}
                        error={errors.description_en}
                        maxCharacters={2000}
                    />

                    <div className="grid gap-4 sm:grid-cols-2">
                        <SelectInput
                            label="Program"
                            value={data.program_id}
                            onChange={(e) => setData('program_id', e.target.value)}
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

                        <SelectInput
                            label="Exam stage"
                            value={data.exam_stage}
                            onChange={(e) => setData('exam_stage', e.target.value)}
                            error={errors.exam_stage}
                        >
                            <option value="">Not specific</option>
                            {enums?.exam_stages.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label_en}
                                </option>
                            ))}
                        </SelectInput>

                        <TextInput
                            label="Duration (minutes)"
                            type="number"
                            inputMode="numeric"
                            min={5}
                            max={600}
                            value={data.duration_minutes}
                            onChange={(e) => setData('duration_minutes', e.target.value)}
                            error={errors.duration_minutes}
                            required
                        />

                        <TextInput
                            label="Negative mark per wrong answer"
                            type="number"
                            inputMode="decimal"
                            min={0}
                            max={5}
                            step={0.05}
                            value={data.negative_mark}
                            onChange={(e) => setData('negative_mark', e.target.value)}
                            error={errors.negative_mark}
                            hint="Use 0 for no negative marking."
                            required
                        />
                    </div>
                </div>

                <div className="flex items-center justify-end gap-3 border-t border-hairline px-5 py-4">
                    <Link href="/admin/model-tests">
                        <Button type="button" variant="secondary">
                            {isEdit ? 'Back to list' : 'Cancel'}
                        </Button>
                    </Link>
                    <Button type="submit" loading={saving}>
                        {isEdit ? 'Save changes' : 'Create & add questions'}
                    </Button>
                </div>
            </div>
        </form>
    );
}
