export interface Company {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    website: string | null;
    address: string | null;
    logo_url?: string | null;
    thumbnail_url?: string | null;
    is_active: boolean;
}
