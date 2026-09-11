export interface TeacherCollegeRef {
    id: number;
    slug: string;
    name_bn: string;
    name_en: string | null;
}

export interface Teacher {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    designation_bn: string | null;
    designation_en: string | null;
    college?: TeacherCollegeRef | null;
    is_active: boolean;
    approved_at: string | null;
    approved_by?: string | null;
    notices_count?: number;
    class_routines_count?: number;
    class_notes_count?: number;
    last_login_at: string | null;
    created_at: string;
}
