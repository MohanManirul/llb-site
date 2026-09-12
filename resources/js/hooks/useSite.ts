import { usePage } from '@inertiajs/react';
import useTranslation from '@/hooks/useTranslation';
import { SITE_NAME, SITE_NAME_BN, SITE_DESCRIPTION } from '@/config/site';
import type { SharedSite } from '@/types/inertia';

export interface SiteBranding extends Omit<SharedSite, 'name' | 'slogan'> {
    siteName: string;
    slogan: string;
}

export default function useSite(): SiteBranding {
    const { tx, isBn } = useTranslation();
    const site = usePage().props.site;

    return {
        logo_url: site?.logo_url ?? null,
        favicon_url: site?.favicon_url ?? null,
        whatsapp_url: site?.whatsapp_url ?? null,
        facebook_url: site?.facebook_url ?? null,
        email: site?.email ?? null,
        phone: site?.phone ?? null,
        siteName: tx(site?.name) || (isBn ? SITE_NAME_BN : SITE_NAME),
        slogan: tx(site?.slogan) || SITE_DESCRIPTION,
    };
}
