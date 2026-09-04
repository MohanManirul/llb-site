import { FormEvent, useEffect, useMemo, useState } from 'react';
import { Link, router } from '@inertiajs/react';
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
import { toDateInput } from '@/lib/format';
import {
    Notice,
    NoticeFilterOptions,
    NoticeFormData,
    ProgramOption,
    SessionOption,
} from '../types';

interface NoticeFormProps {
    notice?: Notice;
}

export default function NoticeForm({ notice }: NoticeFormProps) {
    const isEdit = Boolean(notice);

    const [programs, setPrograms] = useState<ProgramOption[]>([]);
    const [sessions, setSessions] = useState<SessionOption[]>([]);
    const [categories, setCategories] = useState<NoticeFilterOptions['categories']>([]);

    const [data, setDataState] = useState<NoticeFormData>({
        title_bn: notice?.title_bn ?? '',
        title_en: notice?.title_en ?? '',
        excerpt_bn: notice?.excerpt_bn ?? '',
        body_bn: notice?.body_bn ?? '',
        body_en: notice?.body_en ?? '',
        category: notice?.category ?? 'general',
        program_id: notice?.program_id ? String(notice.program_id) : '',
        program_level_id: notice?.program_level_id ? String(notice.program_level_id) : '',
        academic_session_id: notice?.academic_session_id ? String(notice.academic_session_id) : '',
        is_pinned: notice?.is_pinned ?? false,
        expires_at: toDateInput(notice?.expires_at),
    });

    const [attachment, setAttachment] = useState<File | null>(null);
    const [removeAttachment, setRemoveAttachment] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [saving, setSaving] = useState(false);
    const [uploadProgress, setUploadProgress] = useState<number | null>(null);

    useEffect(() => {
        let cancelled = false;

        Promise.all([
            api.get<ApiEnvelope<ProgramOption[]>>('/admin/programs/options'),
            api.get<ApiEnvelope<SessionOption[]>>('/admin/academic-sessions/options'),
            api.get<ApiEnvelope<NoticeFilterOptions>>('/admin/notices/filters'),
        ])
            .then(([programsRes, sessionsRes, filtersRes]) => {
                if (cancelled) return;

                setPrograms(programsRes.data.result);
                setSessions(sessionsRes.data.result);
                setCategories(filtersRes.data.result.categories);
            })
            .catch((error) => {
                if (!cancelled) flash.error(errorMessage(error, 'Could not load form options.'));
            });

        return () => {
            cancelled = true;
        };
    }, []);

    const setData = <K extends keyof NoticeFormData>(field: K, value: NoticeFormData[K]) => {
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

        const payload = new FormData();
        payload.append('title_bn', data.title_bn);
        if (data.title_en) payload.append('title_en', data.title_en);
        if (data.excerpt_bn) payload.append('excerpt_bn', data.excerpt_bn);
        payload.append('body_bn', data.body_bn);
        if (data.body_en) payload.append('body_en', data.body_en);
        payload.append('category', data.category);
        if (data.program_id) payload.append('program_id', data.program_id);
        if (data.program_level_id && selectedProgram?.has_levels) {
            payload.append('program_level_id', data.program_level_id);
        }
        if (data.academic_session_id) payload.append('academic_session_id', data.academic_session_id);
        payload.append('is_pinned', data.is_pinned ? '1' : '0');
        if (data.expires_at) payload.append('expires_at', data.expires_at);

        if (attachment) {
            payload.append('attachment', attachment);
        } else if (isEdit && removeAttachment) {
            payload.append('remove_attachment', '1');
        }

        const onUploadProgress = (event: { loaded: number; total?: number }) => {
            if (event.total && attachment) {
                setUploadProgress(Math.round((event.loaded / event.total) * 100));
            }
        };

        try {
            if (isEdit && notice) {
                payload.append('_method', 'put');
                await api.post(`/admin/notices/${notice.id}`, payload, { onUploadProgress });
                flash.success('Notice updated successfully.');
            } else {
                await api.post('/admin/notices', payload, { onUploadProgress });
                flash.success('Notice created as a draft. Publish it when it is ready.');
            }

            router.visit('/admin/notices');
        } catch (error) {
            const status = (error as { response?: { status?: number } })?.response?.status;

            if (status === 422) {
                setErrors(validationErrors(error));
            } else {
                flash.error(errorMessage(error, 'Could not save the notice.'));
            }
        } finally {
            setSaving(false);
            setUploadProgress(null);
        }
    };

    return (
        <form onSubmit={submit} className="max-w-3xl">
            <div className="rounded-card border border-hairline bg-white shadow-sm">
                <div className="border-b border-hairline px-5 py-4">
                    <h2 className="font-semibold text-ink">Notice</h2>
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

                    <TextInput
                        label="Excerpt (Bangla)"
                        value={data.excerpt_bn}
                        onChange={(e) => setData('excerpt_bn', e.target.value)}
                        error={errors.excerpt_bn}
                        hint="One line shown in the notice list. Optional."
                    />

                    <Textarea
                        label="Body (Bangla)"
                        rows={8}
                        value={data.body_bn}
                        onChange={(e) => setData('body_bn', e.target.value)}
                        error={errors.body_bn}
                        required
                    />

                    <Textarea
                        label="Body (English)"
                        rows={5}
                        value={data.body_en}
                        onChange={(e) => setData('body_en', e.target.value)}
                        error={errors.body_en}
                        hint="Optional — the Bangla body is shown when empty."
                    />
                </div>
            </div>

            <div className="mt-5 rounded-card border border-hairline bg-white shadow-sm">
                <div className="border-b border-hairline px-5 py-4">
                    <h2 className="font-semibold text-ink">Audience & timing</h2>
                    <p className="mt-0.5 text-sm text-ink-muted">
                        Leave the program and session empty for a notice everyone should see.
                    </p>
                </div>

                <div className="grid gap-4 p-5 sm:grid-cols-2">
                    <SelectInput
                        label="Category"
                        value={data.category}
                        onChange={(e) => setData('category', e.target.value)}
                        error={errors.category}
                        required
                    >
                        {categories.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label_en}
                            </option>
                        ))}
                    </SelectInput>

                    <SelectInput
                        label="Program"
                        value={data.program_id}
                        onChange={(e) => {
                            setData('program_id', e.target.value);
                            setData('program_level_id', '');
                        }}
                        error={errors.program_id}
                    >
                        <option value="">All programs</option>
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
                        >
                            <option value="">All</option>
                            {selectedProgram.levels.map((level) => (
                                <option key={level.value} value={level.value}>
                                    {level.label}
                                </option>
                            ))}
                        </SelectInput>
                    )}

                    <SelectInput
                        label="Academic session"
                        value={data.academic_session_id}
                        onChange={(e) => setData('academic_session_id', e.target.value)}
                        error={errors.academic_session_id}
                    >
                        <option value="">All sessions</option>
                        {sessions.map((session) => (
                            <option key={session.value} value={session.value}>
                                {session.label}
                            </option>
                        ))}
                    </SelectInput>

                    <CalendarInput
                        label="Expires on"
                        value={data.expires_at || null}
                        onChange={(e) => setData('expires_at', e.target.value)}
                        error={errors.expires_at}
                        hint="After this date the notice leaves the list. Optional."
                    />

                    <div className="flex items-end pb-2">
                        <Toggle
                            checked={data.is_pinned}
                            onChange={(checked) => setData('is_pinned', checked)}
                            label="Pin to the top"
                        />
                    </div>

                    <div className="sm:col-span-2">
                        <FileUpload
                            label="PDF attachment"
                            value={attachment}
                            currentName={removeAttachment ? null : (notice?.attachment_name ?? null)}
                            currentSize={removeAttachment ? null : (notice?.attachment_size ?? null)}
                            progress={uploadProgress}
                            error={errors.attachment}
                            hint="Optional — a routine, result sheet or circular."
                            onChange={(file) => {
                                setAttachment(file);
                                if (file) setRemoveAttachment(false);
                            }}
                            onRemove={() => {
                                setAttachment(null);
                                setRemoveAttachment(true);
                            }}
                        />
                    </div>
                </div>

                <div className="flex items-center justify-end gap-3 border-t border-hairline px-5 py-4">
                    <Link href="/admin/notices">
                        <Button type="button" variant="secondary">
                            Cancel
                        </Button>
                    </Link>
                    <Button type="submit" loading={saving}>
                        {isEdit ? 'Save changes' : 'Create draft'}
                    </Button>
                </div>
            </div>
        </form>
    );
}
