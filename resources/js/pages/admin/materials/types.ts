import type { TranslatedField } from '../programs/types';
import type { ProgramOption } from '../subjects/types';

export type MaterialType = 'suggestion' | 'book' | 'note';

export type ContentStatus = 'draft' | 'published' | 'archived';

export interface MaterialFileRow {
    id: number;
    study_material_id: number;
    original_name: string;
    label: TranslatedField;
    label_bn: string | null;
    label_en: string | null;
    size: number;
    page_count: number | null;
    sort_order: number;
    download_count: number;
    preview_url: string;
    created_at: string;
}

export interface StudyMaterial {
    id: number;
    type: MaterialType;
    slug: string;
    title: TranslatedField;
    title_bn: string;
    title_en: string | null;
    description_bn: string | null;
    description_en: string | null;
    subject_id: number;
    academic_session_id: number | null;
    exam_stage: string | null;
    exam_year: number | null;
    content_language: string;
    author: string | null;
    publisher: string | null;
    edition: string | null;
    page_count: number | null;
    cover_url: string | null;
    cover_thumbnail_url: string | null;
    status: ContentStatus;
    published_at: string | null;
    is_featured: boolean;
    sort_order: number;
    view_count: number;
    download_count: number;
    files_count?: number;
    files?: MaterialFileRow[];
    subject?: {
        id: number;
        name_bn: string;
        name_en: string;
        program: { id: number; name_en: string; slug: string } | null;
    };
    session?: { id: number; label: string } | null;
    created_at: string;
}

export interface EnumOption {
    value: string;
    label_bn: string;
    label_en: string;
}

export interface MaterialFilterOptions {
    types: EnumOption[];
    statuses: EnumOption[];
    exam_stages: EnumOption[];
    content_languages: EnumOption[];
}

export interface SessionOption {
    value: number;
    label: string;
    is_current: boolean;
}

export interface NewUpload {
    key: string;
    file: File;
    label_bn: string;
}

export interface MaterialFormData {
    type: MaterialType;
    title_bn: string;
    title_en: string;
    description_bn: string;
    description_en: string;
    program_id: string;
    program_level_id: string;
    subject_id: string;
    academic_session_id: string;
    exam_stage: string;
    exam_year: string;
    content_language: string;
    author: string;
    publisher: string;
    edition: string;
    page_count: string;
    is_featured: boolean;
    sort_order: number;
}

export type { ProgramOption };
