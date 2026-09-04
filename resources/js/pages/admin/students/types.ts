import type { ProgramOption } from '../subjects/types';

export interface Student {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    program?: { id: number; slug: string; name_bn: string; name_en: string } | null;
    is_active: boolean;
    attempts_count?: number;
    practice_sessions_count?: number;
    last_login_at: string | null;
    created_at: string;
}

export type { ProgramOption };
