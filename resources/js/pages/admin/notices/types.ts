import type { TranslatedField } from '../programs/types';
import type { ContentStatus, EnumOption, SessionOption } from '../materials/types';
import type { ProgramOption } from '../subjects/types';

export interface Notice {
    id: number;
    slug: string;
    title: TranslatedField;
    title_bn: string;
    title_en: string | null;
    excerpt_bn: string | null;
    excerpt_en: string | null;
    body_bn: string;
    body_en: string | null;
    category: string;
    program_id: number | null;
    program_level_id: number | null;
    subject_id: number | null;
    academic_session_id: number | null;
    is_pinned: boolean;
    status: ContentStatus;
    published_at: string | null;
    expires_at: string | null;
    attachment_name: string | null;
    attachment_size: number | null;
    attachment_download_count: number;
    program?: { id: number; name_en: string } | null;
    session?: { id: number; label: string } | null;
    subject?: { id: number; name_en: string } | null;
    created_at: string;
}

export interface NoticeFilterOptions {
    categories: EnumOption[];
    statuses: EnumOption[];
}

export interface NoticeFormData {
    title_bn: string;
    title_en: string;
    excerpt_bn: string;
    body_bn: string;
    body_en: string;
    category: string;
    program_id: string;
    program_level_id: string;
    academic_session_id: string;
    is_pinned: boolean;
    expires_at: string;
}

export type { EnumOption, ProgramOption, SessionOption };
