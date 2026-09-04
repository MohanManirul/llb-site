import type { TranslatedField } from '../programs/types';

export interface SubjectProgramRef {
    id: number;
    name_bn: string;
    name_en: string;
    slug: string;
}

export interface SubjectLevelRef {
    id: number;
    name_bn: string;
    name_en: string;
}

export interface Subject {
    id: number;
    program_id: number;
    program_level_id: number | null;
    code: string | null;
    slug: string;
    name: TranslatedField;
    name_bn: string;
    name_en: string;
    description_bn: string | null;
    description_en: string | null;
    marks: number | null;
    sort_order: number;
    is_active: boolean;
    program?: SubjectProgramRef;
    level?: SubjectLevelRef | null;
    created_at: string;
}

export interface LevelOption {
    value: number;
    label: string;
    label_bn: string;
}

export interface ProgramOption {
    value: number;
    label: string;
    label_bn: string;
    slug: string;
    has_levels: boolean;
    has_exam_stages: boolean;
    has_sessions: boolean;
    level_label: TranslatedField;
    levels: LevelOption[];
}

export interface SubjectFormData {
    program_id: string;
    program_level_id: string;
    code: string;
    name_bn: string;
    name_en: string;
    description_bn: string;
    description_en: string;
    marks: string;
    sort_order: number;
    is_active: boolean;
}
