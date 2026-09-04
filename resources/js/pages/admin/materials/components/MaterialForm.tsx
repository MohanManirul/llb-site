import { FormEvent, useEffect, useMemo, useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { PlusIcon, XMarkIcon } from '@heroicons/react/24/outline';
import {
    Button,
    FileUpload,
    ImageUpload,
    SearchableSelect,
    SelectInput,
    Textarea,
    TextInput,
    Toggle,
} from '@/components/ui';
import api from '@/lib/api-client';
import type { ApiEnvelope } from '@/lib/api-types';
import { errorMessage, flash, validationErrors } from '@/lib/flash';
import {
    EnumOption,
    MaterialFormData,
    MaterialType,
    NewUpload,
    ProgramOption,
    SessionOption,
    StudyMaterial,
} from '../types';

interface MaterialFormProps {
    material?: StudyMaterial;
}

let uploadKey = 0;

const nextKey = () => `upload-${++uploadKey}`;

export default function MaterialForm({ material }: MaterialFormProps) {
    const isEdit = Boolean(material);

    const [programs, setPrograms] = useState<ProgramOption[]>([]);
    const [sessions, setSessions] = useState<SessionOption[]>([]);
    const [enums, setEnums] = useState<{
        types: EnumOption[];
        exam_stages: EnumOption[];
        content_languages: EnumOption[];
    }>({ types: [], exam_stages: [], content_languages: [] });

    const [data, setDataState] = useState<MaterialFormData>({
        type: material?.type ?? 'suggestion',
        title_bn: material?.title_bn ?? '',
        title_en: material?.title_en ?? '',
        description_bn: material?.description_bn ?? '',
        description_en: material?.description_en ?? '',
        program_id: material?.subject?.program ? String(material.subject.program.id) : '',
        program_level_id: '',
        subject_id: material ? String(material.subject_id) : '',
        academic_session_id: material?.academic_session_id
            ? String(material.academic_session_id)
            : '',
        exam_stage: material?.exam_stage ?? '',
        exam_year: material?.exam_year ? String(material.exam_year) : '',
        content_language: material?.content_language ?? 'bn',
        author: material?.author ?? '',
        publisher: material?.publisher ?? '',
        edition: material?.edition ?? '',
        page_count: material?.page_count ? String(material.page_count) : '',
        is_featured: material?.is_featured ?? false,
        sort_order: material?.sort_order ?? 0,
    });

    const [uploads, setUploads] = useState<NewUpload[]>([]);
    const [cover, setCover] = useState<File | null>(null);
    const [removeCover, setRemoveCover] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [saving, setSaving] = useState(false);
    const [uploadProgress, setUploadProgress] = useState<number | null>(null);

    useEffect(() => {
        let cancelled = false;

        Promise.all([
            api.get<ApiEnvelope<ProgramOption[]>>('/admin/programs/options'),
            api.get<ApiEnvelope<SessionOption[]>>('/admin/academic-sessions/options'),
            api.get<ApiEnvelope<typeof enums & { statuses: EnumOption[] }>>(
                '/admin/study-materials/filters',
            ),
        ])
            .then(([programsRes, sessionsRes, enumsRes]) => {
                if (cancelled) return;

                setPrograms(programsRes.data.result);
                setSessions(sessionsRes.data.result);
                setEnums({
                    types: enumsRes.data.result.types,
                    exam_stages: enumsRes.data.result.exam_stages,
                    content_languages: enumsRes.data.result.content_languages,
                });
            })
            .catch((error) => {
                if (!cancelled) flash.error(errorMessage(error, 'Could not load form options.'));
            });

        return () => {
            cancelled = true;
        };
    }, []);

    const setData = <K extends keyof MaterialFormData>(field: K, value: MaterialFormData[K]) => {
        setDataState((current) => ({ ...current, [field]: value }));
    };

    const selectedProgram = useMemo(
        () => programs.find((program) => String(program.value) === data.program_id) ?? null,
        [programs, data.program_id],
    );

    const subjectFetchUrl = useMemo(() => {
        const params = new URLSearchParams();

        if (data.program_id) params.set('program_id', data.program_id);
        if (data.program_level_id) params.set('program_level_id', data.program_level_id);

        const query = params.toString();

        return `/v1/admin/subjects/options${query ? `?${query}` : ''}`;
    }, [data.program_id, data.program_level_id]);

    const addUpload = () => {
        setUploads((current) => [...current, { key: nextKey(), file: null as unknown as File, label_bn: '' }]);
    };

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        setSaving(true);
        setErrors({});

        const payload = new FormData();
        payload.append('type', data.type);
        payload.append('title_bn', data.title_bn);
        if (data.title_en) payload.append('title_en', data.title_en);
        if (data.description_bn) payload.append('description_bn', data.description_bn);
        if (data.description_en) payload.append('description_en', data.description_en);
        if (data.subject_id) payload.append('subject_id', data.subject_id);
        if (data.academic_session_id && selectedProgram?.has_sessions !== false) {
            payload.append('academic_session_id', data.academic_session_id);
        }
        if (data.exam_stage && selectedProgram?.has_exam_stages) {
            payload.append('exam_stage', data.exam_stage);
        }
        if (data.exam_year) payload.append('exam_year', data.exam_year);
        payload.append('content_language', data.content_language);
        if (data.author) payload.append('author', data.author);
        if (data.publisher) payload.append('publisher', data.publisher);
        if (data.edition) payload.append('edition', data.edition);
        if (data.page_count) payload.append('page_count', data.page_count);
        payload.append('is_featured', data.is_featured ? '1' : '0');
        payload.append('sort_order', String(data.sort_order));

        if (cover) {
            payload.append('cover_image', cover);
        } else if (isEdit && removeCover) {
            payload.append('remove_cover_image', '1');
        }

        if (!isEdit) {
            const usable = uploads.filter((upload) => upload.file);

            usable.forEach((upload, index) => {
                payload.append(`files[${index}][file]`, upload.file);
                if (upload.label_bn) payload.append(`files[${index}][label_bn]`, upload.label_bn);
            });
        }

        const onUploadProgress = (event: { loaded: number; total?: number }) => {
            if (event.total) {
                setUploadProgress(Math.round((event.loaded / event.total) * 100));
            }
        };

        try {
            if (isEdit && material) {
                payload.append('_method', 'put');
                await api.post(`/admin/study-materials/${material.id}`, payload, { onUploadProgress });
                flash.success('Material updated successfully.');
            } else {
                await api.post('/admin/study-materials', payload, { onUploadProgress });
                flash.success('Material created as a draft. Publish it when it is ready.');
            }

            router.visit('/admin/study-materials');
        } catch (error) {
            const status = (error as { response?: { status?: number } })?.response?.status;

            if (status === 422) {
                setErrors(validationErrors(error));
            } else {
                flash.error(errorMessage(error, 'Could not save the material.'));
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
                    <h2 className="font-semibold text-ink">Material</h2>
                </div>

                <div className="grid gap-4 p-5 sm:grid-cols-2">
                    <SelectInput
                        label="Type"
                        value={data.type}
                        onChange={(e) => setData('type', e.target.value as MaterialType)}
                        error={errors.type}
                        required
                    >
                        {enums.types.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label_en}
                            </option>
                        ))}
                    </SelectInput>

                    <SelectInput
                        label="Language of the PDF"
                        value={data.content_language}
                        onChange={(e) => setData('content_language', e.target.value)}
                        error={errors.content_language}
                        required
                    >
                        {enums.content_languages.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label_en}
                            </option>
                        ))}
                    </SelectInput>

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
                        hint="Optional — the Bangla title is shown when empty."
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
                </div>
            </div>

            <div className="mt-5 rounded-card border border-hairline bg-white shadow-sm">
                <div className="border-b border-hairline px-5 py-4">
                    <h2 className="font-semibold text-ink">Placement</h2>
                    <p className="mt-0.5 text-sm text-ink-muted">
                        The program filters the subject list; year/part, session and exam-stage
                        fields appear only where the program uses them.
                    </p>
                </div>

                <div className="grid gap-4 p-5 sm:grid-cols-2">
                    <SelectInput
                        label="Program"
                        value={data.program_id}
                        onChange={(e) => {
                            setData('program_id', e.target.value);
                            setData('program_level_id', '');
                            setData('subject_id', '');
                            setData('exam_stage', '');
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
                            onChange={(e) => {
                                setData('program_level_id', e.target.value);
                                setData('subject_id', '');
                            }}
                        >
                            <option value="">All</option>
                            {selectedProgram.levels.map((level) => (
                                <option key={level.value} value={level.value}>
                                    {level.label}
                                </option>
                            ))}
                        </SelectInput>
                    )}

                    <div className="sm:col-span-2">
                        <SearchableSelect
                            key={`${data.program_id}:${data.program_level_id}`}
                            label="Subject"
                            value={data.subject_id}
                            onChange={(value) => setData('subject_id', value == null ? '' : String(value))}
                            fetchUrl={subjectFetchUrl}
                            selectedOption={
                                material?.subject
                                    ? {
                                          value: material.subject_id,
                                          label: material.subject.name_en,
                                      }
                                    : undefined
                            }
                            error={errors.subject_id}
                            required
                        />
                    </div>

                    {selectedProgram?.has_sessions !== false && (
                        <SelectInput
                            label="Academic session"
                            value={data.academic_session_id}
                            onChange={(e) => setData('academic_session_id', e.target.value)}
                            error={errors.academic_session_id}
                        >
                            <option value="">No specific session</option>
                            {sessions.map((session) => (
                                <option key={session.value} value={session.value}>
                                    {session.label}
                                    {session.is_current ? ' (current)' : ''}
                                </option>
                            ))}
                        </SelectInput>
                    )}

                    {selectedProgram?.has_exam_stages && (
                        <SelectInput
                            label="Exam stage"
                            value={data.exam_stage}
                            onChange={(e) => setData('exam_stage', e.target.value)}
                            error={errors.exam_stage}
                        >
                            <option value="">Any stage</option>
                            {enums.exam_stages.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label_en}
                                </option>
                            ))}
                        </SelectInput>
                    )}

                    <TextInput
                        label="Exam year"
                        type="number"
                        value={data.exam_year}
                        onChange={(e) => setData('exam_year', e.target.value)}
                        error={errors.exam_year}
                        hint="e.g. 2026"
                    />
                </div>
            </div>

            <div className="mt-5 rounded-card border border-hairline bg-white shadow-sm">
                <div className="border-b border-hairline px-5 py-4">
                    <h2 className="font-semibold text-ink">Details</h2>
                </div>

                <div className="grid gap-4 p-5 sm:grid-cols-2">
                    <TextInput
                        label="Author / writer"
                        value={data.author}
                        onChange={(e) => setData('author', e.target.value)}
                        error={errors.author}
                    />
                    <TextInput
                        label="Publisher"
                        value={data.publisher}
                        onChange={(e) => setData('publisher', e.target.value)}
                        error={errors.publisher}
                    />
                    <TextInput
                        label="Edition"
                        value={data.edition}
                        onChange={(e) => setData('edition', e.target.value)}
                        error={errors.edition}
                    />
                    <TextInput
                        label="Total pages"
                        type="number"
                        value={data.page_count}
                        onChange={(e) => setData('page_count', e.target.value)}
                        error={errors.page_count}
                    />

                    <div className="sm:col-span-2">
                        <ImageUpload
                            value={cover ?? (removeCover ? null : (material?.cover_url ?? null))}
                            onChange={(file) => {
                                setCover(file);
                                setRemoveCover(false);
                            }}
                            onRemove={() => {
                                setCover(null);
                                setRemoveCover(true);
                            }}
                            helperText="Optional cover image (JPG/PNG/WebP, up to 2 MB)."
                            error={errors.cover_image}
                        />
                    </div>

                    <Toggle
                        checked={data.is_featured}
                        onChange={(checked) => setData('is_featured', checked)}
                        label="Feature on the home page"
                    />
                </div>
            </div>

            {!isEdit && (
                <div className="mt-5 rounded-card border border-hairline bg-white shadow-sm">
                    <div className="flex items-center justify-between border-b border-hairline px-5 py-4">
                        <div>
                            <h2 className="font-semibold text-ink">PDF files</h2>
                            <p className="mt-0.5 text-sm text-ink-muted">
                                At least one PDF. Split books go in as part 1, part 2, …
                            </p>
                        </div>
                        <Button type="button" variant="secondary" size="sm" onClick={addUpload}>
                            <PlusIcon className="h-4 w-4" />
                            Add file
                        </Button>
                    </div>

                    <div className="space-y-4 p-5">
                        {errors.files && <p className="text-xs text-red-600">{errors.files}</p>}

                        {uploads.map((upload, index) => (
                            <div
                                key={upload.key}
                                className="rounded-control border border-hairline p-3"
                            >
                                <div className="flex items-start gap-3">
                                    <div className="flex-1 space-y-3">
                                        <FileUpload
                                            value={upload.file ?? null}
                                            progress={upload.file ? uploadProgress : null}
                                            error={errors[`files.${index}.file`]}
                                            onChange={(file) =>
                                                setUploads((current) =>
                                                    current.map((item) =>
                                                        item.key === upload.key
                                                            ? { ...item, file: file as File }
                                                            : item,
                                                    ),
                                                )
                                            }
                                        />
                                        <TextInput
                                            label="Label (Bangla)"
                                            placeholder="১ম খণ্ড"
                                            value={upload.label_bn}
                                            onChange={(e) =>
                                                setUploads((current) =>
                                                    current.map((item) =>
                                                        item.key === upload.key
                                                            ? { ...item, label_bn: e.target.value }
                                                            : item,
                                                    ),
                                                )
                                            }
                                            hint="Optional — useful when there are several files."
                                        />
                                    </div>

                                    <button
                                        type="button"
                                        onClick={() =>
                                            setUploads((current) =>
                                                current.filter((item) => item.key !== upload.key),
                                            )
                                        }
                                        aria-label="Remove this file row"
                                        className="mt-1 rounded-full p-1 text-gray-400 hover:bg-gray-100 hover:text-red-600"
                                    >
                                        <XMarkIcon className="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        ))}

                        {uploads.length === 0 && (
                            <button
                                type="button"
                                onClick={addUpload}
                                className="w-full rounded-control border-2 border-dashed border-hairline bg-field px-4 py-6 text-sm font-medium text-ink-muted hover:border-brand-muted"
                            >
                                Add the first PDF
                            </button>
                        )}
                    </div>
                </div>
            )}

            <div className="mt-5 flex items-center justify-end gap-3">
                <Link href="/admin/study-materials">
                    <Button type="button" variant="secondary">
                        Cancel
                    </Button>
                </Link>
                <Button type="submit" loading={saving}>
                    {isEdit ? 'Save changes' : 'Create draft'}
                </Button>
            </div>
        </form>
    );
}
