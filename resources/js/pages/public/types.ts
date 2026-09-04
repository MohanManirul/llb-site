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

export type QuestionType = 'mcq' | 'written';

export type AttemptStatus = 'in_progress' | 'submitted' | 'expired';

export interface ArchiveOption {
    id: number;
    option: TranslatedField;
    is_correct: boolean;
}

export interface ArchiveQuestion {
    id: number;
    type: QuestionType;
    question: TranslatedField;
    explanation: TranslatedField | null;
    reference: string | null;
    exam_stage: string | null;
    exam_year: number | null;
    subject?: {
        id: number;
        slug: string;
        name: TranslatedField;
        program: { slug: string; name: TranslatedField } | null;
    };
    options?: ArchiveOption[];
}

export interface ArchiveFilterOption extends FilterOption {
    program?: string;
}

export interface ArchiveFilterDef {
    key: 'program' | 'subject' | 'exam_stage' | 'exam_year';
    label: TranslatedField;
    options: ArchiveFilterOption[];
}

export interface ProgramRef {
    id: number;
    slug: string;
    name: TranslatedField;
}

export interface PublicModelTest {
    id: number;
    slug: string;
    title: TranslatedField;
    description: TranslatedField | null;
    exam_stage: string | null;
    duration_minutes: number;
    negative_mark: number | string;
    question_count?: number;
    published_at: string | null;
    program?: ProgramRef;
}

export interface AttemptSummary {
    id: number;
    status: AttemptStatus;
    score: number | string | null;
    correct_count: number | null;
    wrong_count: number | null;
    skipped_count: number | null;
    started_at: string | null;
    submitted_at: string | null;
}

export interface StudentModelTest extends PublicModelTest {
    my_attempts?: AttemptSummary[];
}

export interface AttemptQuestionOption {
    id: number;
    option: TranslatedField;
}

export interface AttemptQuestion {
    id: number;
    question: TranslatedField;
    marks: number | string;
    options: AttemptQuestionOption[];
}

export interface AttemptModelTestRef {
    id: number;
    slug: string;
    title: TranslatedField;
    duration_minutes: number;
    negative_mark: number | string;
}

export interface StudentAttempt extends AttemptSummary {
    expires_at: string | null;
    remaining_seconds?: number;
    model_test?: AttemptModelTestRef;
    questions?: AttemptQuestion[];
    answers?: Record<string, number | null>;
}

export interface ResultOption {
    id: number;
    option: TranslatedField;
    is_correct: boolean;
}

export interface ResultBreakdownItem {
    id: number;
    question: TranslatedField;
    explanation: TranslatedField | null;
    reference: string | null;
    marks: number | string;
    options: ResultOption[];
    chosen_option_id: number | null;
    is_correct: boolean | null;
}

export interface AttemptResult extends AttemptSummary {
    model_test: AttemptModelTestRef & {
        total_marks: number | string;
        program: ProgramRef | null;
    };
    breakdown: ResultBreakdownItem[];
}

export interface PracticeSubject {
    id: number;
    slug: string;
    name: TranslatedField;
    question_count: number;
    program?: ProgramRef;
    level?: { id: number; name: TranslatedField } | null;
}

export interface PracticeOption {
    id: number;
    option: TranslatedField;
    is_correct: boolean;
}

export interface PracticeQuestion {
    id: number;
    question: TranslatedField;
    explanation: TranslatedField | null;
    exam_stage: string | null;
    exam_year: number | null;
    reference: string | null;
    options?: PracticeOption[];
}

export interface PracticeSession {
    id: number;
    question_count: number;
    correct_count: number;
    subject?: { id: number; slug: string; name: TranslatedField };
    created_at: string | null;
}

export interface StudentProfile {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    program_id: number | null;
    program?: ProgramRef | null;
    last_login_at: string | null;
    created_at: string | null;
}

export const EXAM_STAGE_LABELS: Record<string, TranslatedField> = {
    preliminary: { bn: 'প্রিলিমিনারি', en: 'Preliminary' },
    mcq: { bn: 'এমসিকিউ', en: 'MCQ' },
    written: { bn: 'লিখিত', en: 'Written' },
    viva: { bn: 'ভাইভা', en: 'Viva' },
};
