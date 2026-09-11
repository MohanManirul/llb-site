import { FormEvent, useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import AppLink from '@/components/public/AppLink';
import {
    Button,
    CalendarInput,
    FileUpload,
    SelectInput,
    Textarea,
    TextInput,
    Toggle,
} from '@/components/ui';
import api from '@/lib/api-client';
import type { ApiEnvelope } from '@/lib/api-types';
import { errorMessage, flash, validationErrors } from '@/lib/flash';
import useTranslation from '@/hooks/useTranslation';
import type { CategoryOption, NoticeFormData, TeacherNotice } from '../../types';

interface CollegeNoticeFormProps {
    notice?: TeacherNotice;
}

interface NoticeFilters {
    categories: CategoryOption[];
}

export default function CollegeNoticeForm({ notice }: CollegeNoticeFormProps) {
    const { t, localeHref } = useTranslation();
    const isEdit = Boolean(notice);

    const [categories, setCategories] = useState<CategoryOption[]>([]);
    const [data, setDataState] = useState<NoticeFormData>({
        title_bn: notice?.title_bn ?? '',
        title_en: notice?.title_en ?? '',
        excerpt_bn: notice?.excerpt_bn ?? '',
        body_bn: notice?.body_bn ?? '',
        body_en: notice?.body_en ?? '',
        category: notice?.category ?? 'general',
        is_pinned: notice?.is_pinned ?? false,
        expires_at: notice?.expires_at ? notice.expires_at.slice(0, 10) : '',
    });
    const [attachment, setAttachment] = useState<File | null>(null);
    const [removeAttachment, setRemoveAttachment] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        let cancelled = false;

        api.get<ApiEnvelope<NoticeFilters>>('/teacher/college/notices/filters')
            .then(({ data: response }) => {
                if (!cancelled) setCategories(response.result.categories ?? []);
            })
            .catch(() => undefined);

        return () => {
            cancelled = true;
        };
    }, []);

    const setData = <K extends keyof NoticeFormData>(field: K, value: NoticeFormData[K]) => {
        setDataState((current) => ({ ...current, [field]: value }));
    };

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        setSaving(true);
        setErrors({});

        const form = new FormData();
        form.append('title_bn', data.title_bn);
        if (data.title_en) form.append('title_en', data.title_en);
        if (data.excerpt_bn) form.append('excerpt_bn', data.excerpt_bn);
        form.append('body_bn', data.body_bn);
        if (data.body_en) form.append('body_en', data.body_en);
        form.append('category', data.category);
        form.append('is_pinned', data.is_pinned ? '1' : '0');
        if (data.expires_at) form.append('expires_at', data.expires_at);
        if (attachment) form.append('attachment', attachment);
        if (removeAttachment) form.append('remove_attachment', '1');

        try {
            if (isEdit && notice) {
                form.append('_method', 'put');
                await api.post(`/teacher/college/notices/${notice.id}`, form);
            } else {
                await api.post('/teacher/college/notices', form);
            }

            flash.success(t('teacher.saved'));
            router.visit(localeHref('/teacher/notices'));
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

            <SelectInput
                label={t('teacher.f_category')}
                value={data.category}
                onChange={(e) => setData('category', e.target.value)}
                error={errors.category}
                required
            >
                {categories.map((category) => (
                    <option key={category.value} value={category.value}>
                        {category.label}
                    </option>
                ))}
            </SelectInput>

            <Textarea
                label={t('teacher.f_body_bn')}
                rows={5}
                value={data.body_bn}
                onChange={(e) => setData('body_bn', e.target.value)}
                error={errors.body_bn}
                required
            />

            <Textarea
                label={t('teacher.f_body_en')}
                rows={5}
                value={data.body_en}
                onChange={(e) => setData('body_en', e.target.value)}
                error={errors.body_en}
            />

            <CalendarInput
                label={t('teacher.f_expires_at')}
                value={data.expires_at}
                onChange={(e) => setData('expires_at', e.target.value)}
                error={errors.expires_at}
            />

            <FileUpload
                label={t('teacher.f_attachment')}
                hint={t('teacher.f_attachment_hint')}
                accept="application/pdf,.pdf,image/jpeg,image/png"
                value={attachment}
                onChange={(file) => {
                    setAttachment(file);
                    if (file) setRemoveAttachment(false);
                }}
                currentName={removeAttachment ? null : (notice?.attachment_name ?? null)}
                currentSize={removeAttachment ? null : (notice?.attachment_size ?? null)}
                onRemove={() => setRemoveAttachment(true)}
                error={errors.attachment}
            />

            <Toggle
                checked={data.is_pinned}
                onChange={(checked) => setData('is_pinned', checked)}
                label={t('teacher.f_pin')}
            />

            <div className="flex items-center justify-end gap-3 pt-2">
                <AppLink href="/teacher/notices">
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
