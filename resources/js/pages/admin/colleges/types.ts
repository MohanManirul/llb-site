import type { TranslatedField } from '../programs/types';

export interface College {
    id: number;
    slug: string;
    name: TranslatedField;
    name_bn: string;
    name_en: string | null;
    short_name_bn: string | null;
    short_name_en: string | null;
    eiin_code: string | null;
    college_code: string | null;
    district_bn: string | null;
    district_en: string | null;
    address_bn: string | null;
    address_en: string | null;
    phone: string | null;
    email: string | null;
    website: string | null;
    is_active: boolean;
    sort_order: number;
    students_count?: number;
    teachers_count?: number;
    created_at: string;
}

export interface CollegeOption {
    value: number;
    label: string;
    label_bn: string;
}

export interface CollegeFormData {
    name_bn: string;
    name_en: string;
    short_name_bn: string;
    short_name_en: string;
    eiin_code: string;
    college_code: string;
    district_bn: string;
    district_en: string;
    address_bn: string;
    address_en: string;
    phone: string;
    email: string;
    website: string;
    sort_order: number;
    is_active: boolean;
}
