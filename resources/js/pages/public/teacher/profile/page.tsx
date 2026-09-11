import { FormEvent, useEffect, useState, type ReactNode } from 'react';
import PortalLayout from '@/components/public/PortalLayout';
import PublicPageHeader from '@/components/public/PublicPageHeader';
import { ErrorCard, LoadingBlock } from '@/components/public/helpers';
import { Button, TextInput } from '@/components/ui';
import api from '@/lib/api-client';
import type { ApiEnvelope } from '@/lib/api-types';
import { errorMessage, flash, validationErrors } from '@/lib/flash';
import usePublicResource from '@/hooks/usePublicResource';
import useTeacher from '@/hooks/useTeacher';
import useTranslation from '@/hooks/useTranslation';
import { teacherPortalNav } from '@/config/portalNav';
import type { TeacherProfile } from '../../types';

interface TeacherProfileForm {
    name: string;
    phone: string;
    designation_en: string;
    password: string;
    password_confirmation: string;
}

export default function TeacherProfilePage() {
    const { t, tx } = useTranslation();
    const { logout } = useTeacher();

    const profile = usePublicResource<TeacherProfile>('/teacher/auth/me', {
        errorMessage: t('common.error'),
    });

    const [data, setDataState] = useState<TeacherProfileForm | null>(null);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [saving, setSaving] = useState(false);
    const [loggingOut, setLoggingOut] = useState(false);

    useEffect(() => {
        if (profile.data && data === null) {
            setDataState({
                name: profile.data.name,
                phone: profile.data.phone ?? '',
                designation_en: profile.data.designation_en ?? '',
                password: '',
                password_confirmation: '',
            });
        }
    }, [profile.data, data]);

    const setData = <K extends keyof TeacherProfileForm>(
        field: K,
        value: TeacherProfileForm[K],
    ) => {
        setDataState((current) => (current ? { ...current, [field]: value } : current));
    };

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        if (!data) return;

        setSaving(true);
        setErrors({});

        try {
            await api.patch<ApiEnvelope<TeacherProfile>>('/teacher/auth/profile', {
                name: data.name,
                phone: data.phone || null,
                designation_en: data.designation_en || null,
                password: data.password || null,
                password_confirmation: data.password_confirmation || null,
            });

            flash.success(t('account.saved'));
            setData('password', '');
            setData('password_confirmation', '');
        } catch (error) {
            const status = (error as { response?: { status?: number } })?.response?.status;

            if (status === 422) {
                setErrors(validationErrors(error));
            } else {
                flash.error(errorMessage(error, t('common.error')));
            }
        } finally {
            setSaving(false);
        }
    };

    const signOut = async () => {
        setLoggingOut(true);

        try {
            await logout();
        } finally {
            setLoggingOut(false);
        }
    };

    return (
        <>
            <PublicPageHeader
                title={t('teacher.profile_title')}
                aside={
                    <Button variant="secondary" size="sm" onClick={signOut} loading={loggingOut}>
                        {t('nav.logout')}
                    </Button>
                }
            >
                {profile.data && (
                    <p className="mt-1 text-sm text-ink-muted">
                        {profile.data.email}
                        {profile.data.college ? ` · ${tx(profile.data.college.name)}` : ''}
                    </p>
                )}
            </PublicPageHeader>

            <div className="max-w-xl rounded-card border border-hairline bg-white p-5 shadow-sm">
                {profile.error ? (
                    <ErrorCard message={profile.error} onRetry={profile.refetch} />
                ) : !data ? (
                    <LoadingBlock />
                ) : (
                    <form onSubmit={submit} className="space-y-4">
                        <TextInput
                            label={t('account.name')}
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            error={errors.name}
                            required
                        />

                        <TextInput
                            label={t('account.phone')}
                            type="tel"
                            value={data.phone}
                            onChange={(e) => setData('phone', e.target.value)}
                            error={errors.phone}
                        />

                        <TextInput
                            label={t('teacher.designation')}
                            value={data.designation_en}
                            onChange={(e) => setData('designation_en', e.target.value)}
                            error={errors.designation_en}
                        />

                        <TextInput
                            label={t('account.new_password')}
                            type="password"
                            autoComplete="new-password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            error={errors.password}
                            hint={t('account.new_password_hint')}
                        />

                        <TextInput
                            label={t('account.password_confirm')}
                            type="password"
                            autoComplete="new-password"
                            value={data.password_confirmation}
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                            error={errors.password_confirmation}
                        />

                        <Button type="submit" loading={saving}>
                            {t('account.save')}
                        </Button>
                    </form>
                )}
            </div>
        </>
    );
}

TeacherProfilePage.layout = (page: ReactNode) => (
    <PortalLayout items={teacherPortalNav}>{page}</PortalLayout>
);
