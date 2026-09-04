import type { TranslatedField } from '../programs/types';
import type { ContentStatus, EnumOption } from '../materials/types';
import type { ProgramOption } from '../subjects/types';
import type { Question } from '../questions/types';

export interface ModelTestQuestionOption {
    id: number;
    option_bn: string;
    option_en: string | null;
    is_correct: boolean;
}

export interface ModelTestQuestion {
    id: number;
    type: 'mcq' | 'written';
    question_bn: string;
    question_en: string | null;
    exam_year: number | null;
    status: ContentStatus;
    sort_order: number;
    marks: number | string;
    options: ModelTestQuestionOption[];
}

export interface ModelTest {
    id: number;
    slug: string;
    title: TranslatedField;
    title_bn: string;
    title_en: string | null;
    description_bn?: string | null;
    description_en?: string | null;
    program_id: number;
    program?: { id: number; slug: string; name_bn: string; name_en: string };
    exam_stage: string | null;
    duration_minutes: number;
    negative_mark: number | string;
    status: ContentStatus;
    published_at: string | null;
    questions_count?: number;
    attempts_count?: number;
    total_marks?: number;
    questions?: ModelTestQuestion[];
    created_at: string;
}

export interface ModelTestFilterOptions {
    statuses: EnumOption[];
    exam_stages: EnumOption[];
}

export interface ModelTestFormData {
    title_bn: string;
    title_en: string;
    description_bn: string;
    description_en: string;
    program_id: string;
    exam_stage: string;
    duration_minutes: string;
    negative_mark: string;
}

export type { ContentStatus, EnumOption, ProgramOption, Question };
