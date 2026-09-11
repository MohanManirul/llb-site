import { FormEvent, useState } from 'react';
import { router } from '@inertiajs/react';
import AppLink from '@/components/public/AppLink';
import { Button, CalendarInput, FileUpload, Textarea, TextInput } from '@/components/ui';
import api from '@/lib/api-client';
import { errorMessage, flash, validationErrors } from '@/lib/flash';
import useTranslation from '@/hooks/useTranslation';
import type { RoutineFormData, TeacherRoutine } from '../../types';

interface RoutineFormProps {
    routine?: TeacherRoutine;
}

export default function RoutineForm({ routine }: RoutineFormProps) {
    const { t, localeHref } = useTranslation();
    const isEdit = Boolean(routine);

    const [data, setDataState] = useState<RoutineFormData>({
        title_bn: routine?.title_bn ?? '',
        title_en: routine?.title_en ?? '',
        description_bn: routine?.description_bn ?? '',
        description_en: routine?.description_en ?? '',
        effective_from: routine?.effective_from ?? '',
        expires_at: routine?.expires_at ? routine.expires_at.slice(0, 10) : '',
    });
    const [attachment, setAttachment] = useState<File | null>(null);
    const [removeAttachment, setRemoveAttachment] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [saving, setSaving] = useState(false);

    const setData = <K extends keyof RoutineFormData>(field: K, value: RoutineFormData[K]) => {
        setDataState((current) => ({ ...current, [field]: value }));
    };

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        setSaving(true);
        setErrors({});

        const form = new FormData();
        form.append('title_bn', data.title_bn);
        if (data.title_en) form.append('title_en', data.title_en);
        if (data.description_bn) form.append('description_bn', data.description_bn);
        if (data.description_en) form.append('description_en', data.description_en);
        if (data.effective_from) form.append('effective_from', data.effective_from);
        if (data.expires_at) form.append('expires_at', data.expires_at);
        if (attachment) form.append('attachment', attachment);
        if (removeAttachment) form.append('remove_attachment', '1');

        try {
            if (isEdit && routine) {
                form.append('_method', 'put');
                await api.post(`/teacher/college/routines/${routine.id}`, form);
            } else {
                await api.post('/teacher/college/routines', form);
            }

            flash.success(t('teacher.saved'));
            router.visit(localeHref('/teacher/routines'));
        } catch (error) {
            const status = (error as { response?: { status?: number } })?.response?.status;

            if (status === 422) {
                setErrors(validationErrors(error));
            } else {
                flash.error(errorMessage(error, t('teacher.save_failed')));
            }
        } finally {
            setSaving(false);
        }
    };

    return (
        <form onSubmit={submit} className="space-y-4">
            <div className="grid gap-4 sm:grid-cols-2">
                <TextInput
                    label={t('teacher.f_title_bn')}
                    value={data.title_bn}
                    onChange={(e) => setData('title_bn', e.target.value)}
                    error={errors.title_bn}
                    required
                />
                <TextInput
                    label={t('teacher.f_title_en')}
                    value={data.title_en}
                    onChange={(e) => setData('title_en', e.target.value)}
                    error={errors.title_en}
                />
            </div>

            <Textarea
                label={t('teacher.f_body_bn')}
                rows={4}
                value={data.description_bn}
                onChange={(e) => setData('description_bn', e.target.value)}
                error={errors.description_bn}
            />

            <Textarea
                label={t('teacher.f_body_en')}
                rows={4}
                value={data.description_en}
                onChange={(e) => setData('description_en', e.target.value)}
                error={errors.description_en}
            />

            <div className="grid gap-4 sm:grid-cols-2">
                <CalendarInput
                    label={t('teacher.f_effective_from')}
                    value={data.effective_from}
                    onChange={(e) => setData('effective_from', e.target.value)}
                    error={errors.effective_from}
                />
                <CalendarInput
                    label={t('teacher.f_expires_at')}
                    value={data.expires_at}
                    onChange={(e) => setData('expires_at', e.target.value)}
                    error={errors.expires_at}
                />
            </div>

            <FileUpload
                label={t('teacher.f_attachment')}
                hint={t('teacher.f_attachment_hint')}
                accept="application/pdf,.pdf,image/jpeg,image/png"
                value={attachment}
                onChange={(file) => {
                    setAttachment(file);
                    if (file) setRemoveAttachment(false);
                }}
                currentName={removeAttachment ? null : (routine?.attachment_name ?? null)}
                currentSize={removeAttachment ? null : (routine?.attachment_size ?? null)}
                onRemove={() => setRemoveAttachment(true)}
                error={errors.attachment}
            />

            <div className="flex items-center justify-end gap-3 pt-2">
                <AppLink href="/teacher/routines">
                    <Button type="button" variant="secondary">
                        {t('common.cancel')}
                    </Button>
                </AppLink>
                <Button type="submit" loading={saving}>
                    {t('account.save')}
                </Button>
            </div>
        </form>
    );
}
