import type { TranslatedField } from '@/lib/i18n';

export interface TeacherContentSession {
    id: number;
    label: string;
}

export interface TeacherContentSubject {
    id: number;
    name_bn: string;
    name_en: string | null;
}

export interface TeacherRoutine {
    id: number;
    title: TranslatedField;
    title_bn: string;
    title_en: string | null;
    description_bn: string | null;
    description_en: string | null;
    college_id: number;
    program_id: number | null;
    program_level_id: number | null;
    academic_session_id: number | null;
    effective_from: string | null;
    status: string;
    published_at: string | null;
    expires_at: string | null;
    attachment_name: string | null;
    attachment_size: number | null;
    session?: TeacherContentSession | null;
    created_at: string | null;
}

export interface TeacherNote {
    id: number;
    title: TranslatedField;
    title_bn: string;
    title_en: string | null;
    description_bn: string | null;
    description_en: string | null;
    college_id: number;
    subject_id: number | null;
    program_id: number | null;
    program_level_id: number | null;
    academic_session_id: number | null;
    status: string;
    published_at: string | null;
    expires_at: string | null;
    attachment_name: string | null;
    attachment_size: number | null;
    subject?: TeacherContentSubject | null;
    session?: TeacherContentSession | null;
    created_at: string | null;
}

export interface TeacherNotice {
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
    college_id: number | null;
    subject_id: number | null;
    academic_session_id: number | null;
    is_pinned: boolean;
    status: string;
    published_at: string | null;
    expires_at: string | null;
    attachment_name: string | null;
    attachment_size: number | null;
    created_at: string | null;
}

export interface CategoryOption {
    value: string;
    label: string;
    label_bn?: string;
}

export interface RoutineFormData {
    title_bn: string;
    title_en: string;
    description_bn: string;
    description_en: string;
    effective_from: string;
    expires_at: string;
}

export interface NoteFormData {
    title_bn: string;
    title_en: string;
    description_bn: string;
    description_en: string;
    subject_id: string;
    expires_at: string;
}

export interface NoticeFormData {
    title_bn: string;
    title_en: string;
    excerpt_bn: string;
    body_bn: string;
    body_en: string;
    category: string;
    is_pinned: boolean;
    expires_at: string;
}
