import { FormEvent, useEffect, useState } from 'react';
import { Button, Modal, TextInput, Toggle, NumberStepper } from '@/components/ui';
import api from '@/lib/api-client';
import { errorMessage, flash, validationErrors } from '@/lib/flash';
import { Program, ProgramFormData } from '../types';

interface ProgramFormModalProps {
    show: boolean;
    program: Program | null;
    onClose: () => void;
    onSaved: () => void;
}

const EMPTY: ProgramFormData = {
    name_bn: '',
    name_en: '',
    short_name_bn: '',
    short_name_en: '',
    has_levels: true,
    level_label_bn: '',
    level_label_en: '',
    has_exam_stages: false,
    has_sessions: true,
    sort_order: 0,
    is_active: true,
};

export default function ProgramFormModal({ show, program, onClose, onSaved }: ProgramFormModalProps) {
    const [data, setDataState] = useState<ProgramFormData>(EMPTY);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (!show) return;

        setErrors({});
        setDataState(
            program
                ? {
                      name_bn: program.name_bn,
                      name_en: program.name_en,
                      short_name_bn: program.short_name_bn ?? '',
                      short_name_en: program.short_name_en ?? '',
                      has_levels: program.has_levels,
                      level_label_bn: program.level_label_bn ?? '',
                      level_label_en: program.level_label_en ?? '',
                      has_exam_stages: program.has_exam_stages,
                      has_sessions: program.has_sessions,
                      sort_order: program.sort_order,
                      is_active: program.is_active,
                  }
                : EMPTY,
        );
    }, [show, program]);

    const setData = <K extends keyof ProgramFormData>(field: K, value: ProgramFormData[K]) => {
        setDataState((current) => ({ ...current, [field]: value }));
    };

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        setSaving(true);
        setErrors({});

        const payload = {
            ...data,
            short_name_bn: data.short_name_bn || null,
            short_name_en: data.short_name_en || null,
            level_label_bn: data.has_levels ? data.level_label_bn || null : null,
            level_label_en: data.has_levels ? data.level_label_en || null : null,
        };

        try {
            if (program) {
                await api.put(`/admin/programs/${program.id}`, payload);
                flash.success('Program updated successfully.');
            } else {
                await api.post('/admin/programs', payload);
                flash.success('Program created successfully.');
            }

            onSaved();
        } catch (error) {
            const status = (error as { response?: { status?: number } })?.response?.status;

            if (status === 422) {
                setErrors(validationErrors(error));
            } else {
                flash.error(errorMessage(error, 'Could not save the program.'));
            }
        } finally {
            setSaving(false);
        }
    };

    return (
        <Modal show={show} onClose={onClose} maxWidth="lg" label={program ? 'Edit program' : 'Create program'}>
            <form onSubmit={submit} className="p-6">
                <h2 className="text-lg font-semibold text-ink">
                    {program ? 'Edit program' : 'Create program'}
                </h2>

                <div className="mt-4 grid gap-4 sm:grid-cols-2">
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
                </div>

                <div className="mt-5 space-y-3 rounded-control border border-hairline bg-field p-4">
                    <Toggle
                        checked={data.has_levels}
                        onChange={(checked) => setData('has_levels', checked)}
                        label="Has years/parts (levels)"
                    />

                    {data.has_levels && (
                        <div className="grid gap-4 sm:grid-cols-2">
                            <TextInput
                                label="Level label (Bangla)"
                                placeholder="বর্ষ / পর্ব"
                                value={data.level_label_bn}
                                onChange={(e) => setData('level_label_bn', e.target.value)}
                                error={errors.level_label_bn}
                                required
                            />
                            <TextInput
                                label="Level label (English)"
                                placeholder="Year / Part"
                                value={data.level_label_en}
                                onChange={(e) => setData('level_label_en', e.target.value)}
                                error={errors.level_label_en}
                                required
                            />
                        </div>
                    )}

                    <Toggle
                        checked={data.has_exam_stages}
                        onChange={(checked) => setData('has_exam_stages', checked)}
                        label="Has exam stages (MCQ / Written / Viva)"
                    />

                    <Toggle
                        checked={data.has_sessions}
                        onChange={(checked) => setData('has_sessions', checked)}
                        label="Content is organised by academic session"
                    />
                </div>

                <div className="mt-5 grid gap-4 sm:grid-cols-2">
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

                <div className="mt-6 flex items-center justify-end gap-3">
                    <Button type="button" variant="secondary" onClick={onClose}>
                        Cancel
                    </Button>
                    <Button type="submit" loading={saving}>
                        {program ? 'Save changes' : 'Create program'}
                    </Button>
                </div>
            </form>
        </Modal>
    );
}
