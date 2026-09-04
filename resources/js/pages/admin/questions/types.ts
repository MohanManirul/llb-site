import type { TranslatedField } from '../programs/types';
import type { ContentStatus, EnumOption } from '../materials/types';
import type { ProgramOption } from '../subjects/types';

export type QuestionType = 'mcq' | 'written';

export interface QuestionOption {
    id: number;
    option_bn: string;
    option_en: string | null;
    is_correct: boolean;
    sort_order: number;
}

export interface QuestionSubjectRef {
    id: number;
    name_bn: string;
    name_en: string;
    program: { id: number; slug: string; name_bn: string; name_en: string } | null;
}

export interface Question {
    id: number;
    type: QuestionType;
    subject_id: number;
    subject?: QuestionSubjectRef;
    exam_stage: string | null;
    exam_year: number | null;
    question: TranslatedField;
    question_bn: string;
    question_en: string | null;
    explanation_bn?: string | null;
    explanation_en?: string | null;
    reference: string | null;
    status: ContentStatus;
    options_count?: number;
    options?: QuestionOption[];
    created_at: string;
}

export interface QuestionFilterOptions {
    types: EnumOption[];
    statuses: EnumOption[];
    exam_stages: EnumOption[];
}

export interface OptionRow {
    key: string;
    id?: number;
    option_bn: string;
    option_en: string;
    is_correct: boolean;
}

export interface QuestionFormData {
    type: QuestionType;
    program_id: string;
    program_level_id: string;
    subject_id: string;
    exam_stage: string;
    exam_year: string;
    question_bn: string;
    question_en: string;
    explanation_bn: string;
    explanation_en: string;
    reference: string;
}

export interface ImportReport {
    imported: number;
    failed: number;
    errors: Array<{ row: number; messages: string[] }>;
}

export type { ContentStatus, EnumOption, ProgramOption };
