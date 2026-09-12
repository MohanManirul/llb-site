import { FormEvent, useMemo, useState, type ReactNode } from 'react';
import PublicLayout from '@/components/public/PublicLayout';
import AuthCard from '@/components/public/AuthCard';
import AppLink from '@/components/public/AppLink';
import { Button, SearchableSelect, TextInput } from '@/components/ui';
import api from '@/lib/api-client';
import { errorMessage, flash, validationErrors } from '@/lib/flash';
import useStudent from '@/hooks/useStudent';
import useTranslation from '@/hooks/useTranslation';

interface RegisterForm {
    name: string;
    phone: string;
    college_id: string;
    password: string;
    password_confirmation: string;
}

export default function StudentRegister() {
    const { t, locale } = useTranslation();
    const { redirectAfterAuth } = useStudent();

    const collegeFetchUrl = useMemo(() => `/v1/public/colleges?locale=${locale}`, [locale]);

    const [data, setDataState] = useState<RegisterForm>({
        name: '',
        phone: '',
        college_id: '',
        password: '',
        password_confirmation: '',
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [submitting, setSubmitting] = useState(false);

    const setData = <K extends keyof RegisterForm>(field: K, value: RegisterForm[K]) => {
        setDataState((current) => ({ ...current, [field]: value }));
    };

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        setSubmitting(true);
        setErrors({});

        try {
            await api.post('/student/auth/register', {
                ...data,
                college_id: data.college_id ? Number(data.college_id) : null,
            });
            redirectAfterAuth();
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
            title={t('account.register_title')}
            subtitle={t('prep.login_hint')}
            footer={
                <>
                    {t('account.have_account')}{' '}
                    <AppLink href="/account/login" className="font-medium text-brand-accent hover:underline">
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
                    label={t('account.mobile')}
                    type="tel"
                    inputMode="numeric"
                    autoComplete="tel"
                    placeholder="01712345678"
                    hint={t('account.mobile_hint')}
                    value={data.phone}
                    onChange={(e) => setData('phone', e.target.value)}
                    error={errors.phone}
                    required
                />

                <SearchableSelect
                    label={t('account.college')}
                    value={data.college_id}
                    onChange={(value) => setData('college_id', value == null ? '' : String(value))}
                    fetchUrl={collegeFetchUrl}
                    placeholder={t('account.pick_college')}
                    searchPlaceholder={t('account.college_search')}
                    error={errors.college_id}
                    required
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

StudentRegister.layout = (page: ReactNode) => <PublicLayout>{page}</PublicLayout>;
