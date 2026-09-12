import { FormEvent, ReactNode, useEffect, useState } from 'react';
import DashboardLayout from '@/components/common/DashboardLayout';
import PageHeader from '@/components/common/PageHeader';
import { Button, ImageUpload, TextInput } from '@/components/ui';
import api from '@/lib/api-client';
import type { ApiEnvelope } from '@/lib/api-types';
import { errorMessage, flash, validationErrors } from '@/lib/flash';
import usePermissions from '@/hooks/usePermissions';
import type { SiteSetting } from '../types';

interface SettingsForm {
    name_bn: string;
    name_en: string;
    slogan_bn: string;
    slogan_en: string;
    whatsapp_url: string;
    facebook_url: string;
    email: string;
    phone: string;
}

const EMPTY: SettingsForm = {
    name_bn: '',
    name_en: '',
    slogan_bn: '',
    slogan_en: '',
    whatsapp_url: '',
    facebook_url: '',
    email: '',
    phone: '',
};

export default function SiteSettingsEdit() {
    const { can } = usePermissions();
    const canEdit = can('edit site settings');

    const [data, setDataState] = useState<SettingsForm>(EMPTY);
    const [logoUrl, setLogoUrl] = useState<string | null>(null);
    const [faviconUrl, setFaviconUrl] = useState<string | null>(null);
    const [logo, setLogo] = useState<File | null>(null);
    const [favicon, setFavicon] = useState<File | null>(null);
    const [removeLogo, setRemoveLogo] = useState(false);
    const [removeFavicon, setRemoveFavicon] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);

    const setData = <K extends keyof SettingsForm>(field: K, value: SettingsForm[K]) => {
        setDataState((current) => ({ ...current, [field]: value }));
    };

    useEffect(() => {
        let cancelled = false;

        api.get<ApiEnvelope<SiteSetting>>('/admin/site-settings')
            .then(({ data: payload }) => {
                if (cancelled) return;

                const setting = payload.result;

                setDataState({
                    name_bn: setting.name_bn ?? '',
                    name_en: setting.name_en ?? '',
                    slogan_bn: setting.slogan_bn ?? '',
                    slogan_en: setting.slogan_en ?? '',
                    whatsapp_url: setting.whatsapp_url ?? '',
                    facebook_url: setting.facebook_url ?? '',
                    email: setting.email ?? '',
                    phone: setting.phone ?? '',
                });
                setLogoUrl(setting.logo_url);
                setFaviconUrl(setting.favicon_url);
            })
            .catch((error) => {
                if (!cancelled) flash.error(errorMessage(error, 'Could not load the site settings.'));
            })
            .finally(() => {
                if (!cancelled) setLoading(false);
            });

        return () => {
            cancelled = true;
        };
    }, []);

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        setSaving(true);
        setErrors({});

        const payload = new FormData();

        (Object.keys(data) as (keyof SettingsForm)[]).forEach((field) => {
            payload.append(field, data[field]);
        });

        if (logo) {
            payload.append('logo', logo);
        } else if (removeLogo) {
            payload.append('remove_logo', '1');
        }

        if (favicon) {
            payload.append('favicon', favicon);
        } else if (removeFavicon) {
            payload.append('remove_favicon', '1');
        }

        try {
            const { data: saved } = await api.post<ApiEnvelope<SiteSetting>>(
                '/admin/site-settings',
                payload,
            );

            setLogoUrl(saved.result.logo_url);
            setFaviconUrl(saved.result.favicon_url);
            setLogo(null);
            setFavicon(null);
            setRemoveLogo(false);
            setRemoveFavicon(false);

            flash.success('Site settings updated successfully.');
        } catch (error) {
            const status = (error as { response?: { status?: number } })?.response?.status;

            if (status === 422) {
                setErrors(validationErrors(error));
            } else {
                flash.error(errorMessage(error, 'Could not save the site settings.'));
            }
        } finally {
            setSaving(false);
        }
    };

    return (
        <>
            <PageHeader
                title="Site Settings"
                subtitle="Branding and contact details used across the public site."
            />

            {loading ? (
                <div className="rounded-card border border-hairline bg-white p-5 text-sm text-gray-500 shadow-sm">
                    Loading…
                </div>
            ) : (
                <form onSubmit={submit} className="max-w-3xl">
                    <div className="rounded-card border border-hairline bg-white shadow-sm">
                        <div className="border-b border-hairline px-5 py-4">
                            <h2 className="font-semibold text-ink">Identity</h2>
                        </div>

                        <div className="grid gap-4 p-5 sm:grid-cols-2">
                            <TextInput
                                label="Site name (Bangla)"
                                value={data.name_bn}
                                onChange={(e) => setData('name_bn', e.target.value)}
                                error={errors.name_bn}
                                disabled={!canEdit}
                                required
                            />
                            <TextInput
                                label="Site name (English)"
                                value={data.name_en}
                                onChange={(e) => setData('name_en', e.target.value)}
                                error={errors.name_en}
                                disabled={!canEdit}
                            />
                            <TextInput
                                label="Slogan (Bangla)"
                                value={data.slogan_bn}
                                onChange={(e) => setData('slogan_bn', e.target.value)}
                                error={errors.slogan_bn}
                                disabled={!canEdit}
                                hint="Shown in the footer and as the site description."
                            />
                            <TextInput
                                label="Slogan (English)"
                                value={data.slogan_en}
                                onChange={(e) => setData('slogan_en', e.target.value)}
                                error={errors.slogan_en}
                                disabled={!canEdit}
                            />

                            <ImageUpload
                                label="Logo"
                                value={logo ?? (removeLogo ? null : logoUrl)}
                                onChange={(file) => {
                                    setLogo(file);
                                    setRemoveLogo(false);
                                }}
                                onRemove={() => {
                                    setLogo(null);
                                    setRemoveLogo(true);
                                }}
                                removeLabel="Remove logo"
                                helperText="Shown in the header and footer. PNG, JPG, WebP or SVG up to 2 MB."
                                error={errors.logo}
                                disabled={!canEdit}
                            />

                            <ImageUpload
                                label="Favicon"
                                value={favicon ?? (removeFavicon ? null : faviconUrl)}
                                onChange={(file) => {
                                    setFavicon(file);
                                    setRemoveFavicon(false);
                                }}
                                onRemove={() => {
                                    setFavicon(null);
                                    setRemoveFavicon(true);
                                }}
                                removeLabel="Remove favicon"
                                helperText="Browser tab icon. Square PNG, ICO or SVG up to 512 KB."
                                error={errors.favicon}
                                disabled={!canEdit}
                            />
                        </div>
                    </div>

                    <div className="mt-5 rounded-card border border-hairline bg-white shadow-sm">
                        <div className="border-b border-hairline px-5 py-4">
                            <h2 className="font-semibold text-ink">Contact & groups</h2>
                        </div>

                        <div className="grid gap-4 p-5 sm:grid-cols-2">
                            <TextInput
                                label="WhatsApp group link"
                                type="url"
                                placeholder="https://chat.whatsapp.com/…"
                                value={data.whatsapp_url}
                                onChange={(e) => setData('whatsapp_url', e.target.value)}
                                error={errors.whatsapp_url}
                                disabled={!canEdit}
                                hint="Leave empty to hide the WhatsApp button."
                            />
                            <TextInput
                                label="Facebook group link"
                                type="url"
                                placeholder="https://facebook.com/groups/…"
                                value={data.facebook_url}
                                onChange={(e) => setData('facebook_url', e.target.value)}
                                error={errors.facebook_url}
                                disabled={!canEdit}
                                hint="Leave empty to hide the Facebook button."
                            />
                            <TextInput
                                label="Contact email"
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                error={errors.email}
                                disabled={!canEdit}
                            />
                            <TextInput
                                label="Contact phone"
                                type="tel"
                                value={data.phone}
                                onChange={(e) => setData('phone', e.target.value)}
                                error={errors.phone}
                                disabled={!canEdit}
                            />
                        </div>
                    </div>

                    {canEdit && (
                        <div className="mt-5 flex justify-end">
                            <Button type="submit" loading={saving}>
                                Save settings
                            </Button>
                        </div>
                    )}
                </form>
            )}
        </>
    );
}

SiteSettingsEdit.layout = (page: ReactNode) => <DashboardLayout>{page}</DashboardLayout>;
