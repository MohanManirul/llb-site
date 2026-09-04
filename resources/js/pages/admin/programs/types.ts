export interface TranslatedField {
    bn: string | null;
    en: string | null;
}

export interface ProgramLevel {
    id: number;
    program_id: number;
    position: number;
    slug: string;
    name: TranslatedField;
    name_bn: string;
    name_en: string;
    sort_order: number;
    is_active: boolean;
}

export interface Program {
    id: number;
    slug: string;
    name: TranslatedField;
    name_bn: string;
    name_en: string;
    short_name_bn: string | null;
    short_name_en: string | null;
    has_levels: boolean;
    level_label: TranslatedField;
    level_label_bn: string | null;
    level_label_en: string | null;
    has_exam_stages: boolean;
    has_sessions: boolean;
    sort_order: number;
    is_active: boolean;
    levels_count?: number;
    subjects_count?: number;
    levels?: ProgramLevel[];
    created_at: string;
}

export interface ProgramFormData {
    name_bn: string;
    name_en: string;
    short_name_bn: string;
    short_name_en: string;
    has_levels: boolean;
    level_label_bn: string;
    level_label_en: string;
    has_exam_stages: boolean;
    has_sessions: boolean;
    sort_order: number;
    is_active: boolean;
}

export interface LevelFormData {
    position: number;
    name_bn: string;
    name_en: string;
}
