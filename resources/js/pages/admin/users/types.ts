export interface User {
    id: number;
    name: string;
    email: string;
    phone?: string | null;
    image_url?: string | null;
    thumbnail_url?: string | null;
    roles: string[];
    can_impersonate: boolean;
    created_at: string;
}
