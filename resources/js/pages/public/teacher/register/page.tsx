import { FormEvent, useMemo, useState, type ReactNode } from 'react';
import { router } from '@inertiajs/react';
import PublicLayout from '@/components/public/PublicLayout';
import AuthCard from '@/components/public/AuthCard';
import AppLink from '@/components/public/AppLink';
import { Button, SearchableSelect, TextInput } from '@/components/ui';
import api from '@/lib/api-client';
import { errorMessage, flash, validationErrors } from '@/lib/flash';
import useTranslation from '@/hooks/useTranslation';

interface TeacherRegisterForm {
    name: string;
    email: string;
    phone: string;
    designation_en: string;
    college_id: string;
    password: string;
    password_confirmation: string;
}

export default function TeacherRegister() {
    const { t, locale, localeHref } = useTranslation();

    const collegeFetchUrl = useMemo(() => `/v1/public/colleges?locale=${locale}`, [locale]);

    const [data, setDataState] = useState<TeacherRegisterForm>({
        name: '',
        email: '',
        phone: '',
        designation_en: '',
        college_id: '',
        password: '',
        password_confirmation: '',
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [submitting, setSubmitting] = useState(false);

    const setData = <K extends keyof TeacherRegisterForm>(
        field: K,
        value: TeacherRegisterForm[K],
    ) => {
        setDataState((current) => ({ ...current, [field]: value }));
    };

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        setSubmitting(true);
        setErrors({});

        try {
            await api.post('/teacher/auth/register', {
                ...data,
                phone: data.phone || null,
                designation_en: data.designation_en || null,
                college_id: data.college_id ? Number(data.college_id) : null,
            });

            router.visit(localeHref('/teacher/registered'));
        } catch (error) {
            const status = (error as { response?: { status?: number } })?.response?.status;

            if (status === 422) {
                setErrors(validationErrors(error));
            } else {
                flash.error(errorMessage(error, t('common.error')));
            }

            setSubmitting(false);
        }
    };

    return (
        <AuthCard
            title={t('teacher.register_title')}
            subtitle={t('teacher.pending_message')}
            footer={
                <>
                    {t('teacher.have_account')}{' '}
                    <AppLink
                        href="/teacher/login"
                        className="font-medium text-brand-accent hover:underline"
                    >
                        {t('account.login_btn')}
                    </AppLink>
                </>
            }
        >
            <form onSubmit={submit} className="space-y-4">
                <TextInput
                    label={t('account.name')}
                    autoComplete="name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    error={errors.name}
                    required
                    autoFocus
                />

                <TextInput
                    label={t('account.email')}
                    type="email"
                    autoComplete="email"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    error={errors.email}
                    required
                />

                <TextInput
                    label={t('account.phone')}
                    type="tel"
                    autoComplete="tel"
                    value={data.phone}
                    onChange={(e) => setData('phone', e.target.value)}
                    error={errors.phone}
                />

                <SearchableSelect
                    label={t('teacher.college')}
                    value={data.college_id}
                    onChange={(value) => setData('college_id', value == null ? '' : String(value))}
                    fetchUrl={collegeFetchUrl}
                    placeholder={t('account.pick_college')}
                    searchPlaceholder={t('account.college_search')}
                    error={errors.college_id}
                    required
                />

                <TextInput
                    label={t('teacher.designation')}
                    value={data.designation_en}
                    onChange={(e) => setData('designation_en', e.target.value)}
                    error={errors.designation_en}
                />

                <TextInput
                    label={t('account.password')}
                    type="password"
                    autoComplete="new-password"
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                    error={errors.password}
                    required
                />

                <TextInput
                    label={t('account.password_confirm')}
                    type="password"
                    autoComplete="new-password"
                    value={data.password_confirmation}
                    onChange={(e) => setData('password_confirmation', e.target.value)}
                    error={errors.password_confirmation}
                    required
                />

                <Button type="submit" loading={submitting} fullWidth>
                    {t('account.register_btn')}
                </Button>
            </form>
        </AuthCard>
    );
}

TeacherRegister.layout = (page: ReactNode) => <PublicLayout>{page}</PublicLayout>;
