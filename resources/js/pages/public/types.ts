import type { TranslatedField } from '@/lib/i18n';

export interface PublicLevel {
    slug: string;
    position: number;
    name: TranslatedField;
}

export interface PublicProgram {
    slug: string;
    name: TranslatedField;
    short_name: TranslatedField;
    has_levels: boolean;
    level_label: TranslatedField;
    has_exam_stages: boolean;
    has_sessions: boolean;
    subjects_count?: number;
    levels?: PublicLevel[];
}

export interface FilterOption {
    value: string;
    label: TranslatedField;
}

export interface FilterDef {
    key: string;
    label: TranslatedField;
    options: FilterOption[];
}

export interface PublicSubjectProgramRef {
    slug: string;
    name: TranslatedField;
    has_levels?: boolean;
    has_exam_stages?: boolean;
    has_sessions?: boolean;
    level_label?: TranslatedField;
}

export interface PublicSubject {
    slug: string;
    code: string | null;
    name: TranslatedField;
    description: TranslatedField;
    marks: number | null;
    suggestions_count?: number;
    books_count?: number;
    notes_count?: number;
    program?: PublicSubjectProgramRef;
    level?: { slug: string; name: TranslatedField } | null;
}

export interface PublicMaterialFile {
    id: number;
    label: TranslatedField;
    size: number;
    page_count: number | null;
    download_count: number;
    preview_url: string;
    download_url: string;
}

export interface PublicMaterial {
    id: number;
    type: 'suggestion' | 'book' | 'note';
    slug: string;
    title: TranslatedField;
    description: TranslatedField;
    exam_stage: string | null;
    exam_year: number | null;
    content_language: string;
    author: string | null;
    publisher: string | null;
    edition: string | null;
    page_count: number | null;
    cover_url: string | null;
    cover_thumbnail_url: string | null;
    is_featured: boolean;
    view_count: number;
    download_count: number;
    files_count?: number;
    published_at: string | null;
    files?: PublicMaterialFile[];
    subject?: {
        slug: string;
        name: TranslatedField;
        program: { slug: string; name: TranslatedField; short_name: TranslatedField } | null;
        level: { slug: string; name: TranslatedField } | null;
    };
    session?: { slug: string; label: string } | null;
}

export interface PageMeta {
    title_bn: string;
    title_en: string | null;
    description_bn?: string | null;
    description_en?: string | null;
}
