import { FormEvent, useState, type ReactNode } from 'react';
import { usePage } from '@inertiajs/react';
import PublicLayout from '@/components/public/PublicLayout';
import AuthCard from '@/components/public/AuthCard';
import AppLink from '@/components/public/AppLink';
import { Button, SelectInput, TextInput } from '@/components/ui';
import api from '@/lib/api-client';
import { errorMessage, flash, validationErrors } from '@/lib/flash';
import useStudent from '@/hooks/useStudent';
import useTranslation from '@/hooks/useTranslation';

interface RegisterForm {
    name: string;
    email: string;
    phone: string;
    program_id: string;
    password: string;
    password_confirmation: string;
}

export default function StudentRegister() {
    const { t, tx } = useTranslation();
    const { redirectAfterAuth } = useStudent();
    const programs = usePage().props.programs ?? [];

    const [data, setDataState] = useState<RegisterForm>({
        name: '',
        email: '',
        phone: '',
        program_id: '',
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
                phone: data.phone || null,
                program_id: data.program_id || null,
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

                <SelectInput
                    label={t('account.program')}
                    value={data.program_id}
                    onChange={(e) => setData('program_id', e.target.value)}
                    error={errors.program_id}
                >
                    <option value="">{t('account.no_program')}</option>
                    {programs.map((program) => (
                        <option key={program.slug} value={program.id}>
                            {tx(program.name)}
                        </option>
                    ))}
                </SelectInput>

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
