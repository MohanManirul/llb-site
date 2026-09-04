import { FormEvent, useState, type ReactNode } from 'react';
import PublicLayout from '@/components/public/PublicLayout';
import AuthCard from '@/components/public/AuthCard';
import AppLink from '@/components/public/AppLink';
import { Button, Checkbox, TextInput } from '@/components/ui';
import api from '@/lib/api-client';
import { errorMessage, flash, validationErrors } from '@/lib/flash';
import useStudent from '@/hooks/useStudent';
import useTranslation from '@/hooks/useTranslation';

export default function StudentLogin() {
    const { t } = useTranslation();
    const { redirectAfterAuth } = useStudent();

    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [remember, setRemember] = useState(true);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [submitting, setSubmitting] = useState(false);

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        setSubmitting(true);
        setErrors({});

        try {
            await api.post('/student/auth/login', { email, password, remember });
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
            title={t('account.login_title')}
            footer={
                <>
                    {t('account.no_account')}{' '}
                    <AppLink href="/account/register" className="font-medium text-brand-accent hover:underline">
                        {t('account.register_btn')}
                    </AppLink>
                </>
            }
        >
            <form onSubmit={submit} className="space-y-4">
                <TextInput
                    label={t('account.email')}
                    type="email"
                    autoComplete="email"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    error={errors.email}
                    required
                    autoFocus
                />

                <TextInput
                    label={t('account.password')}
                    type="password"
                    autoComplete="current-password"
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    error={errors.password}
                    required
                />

                <div className="flex items-center justify-between">
                    <Checkbox
                        label={t('account.remember')}
                        checked={remember}
                        onChange={(e) => setRemember(e.target.checked)}
                    />
                    <AppLink
                        href="/account/forgot-password"
                        className="text-sm font-medium text-brand-accent hover:underline"
                    >
                        {t('account.forgot_link')}
                    </AppLink>
                </div>

                <Button type="submit" loading={submitting} fullWidth>
                    {t('account.login_btn')}
                </Button>
            </form>
        </AuthCard>
    );
}

StudentLogin.layout = (page: ReactNode) => <PublicLayout>{page}</PublicLayout>;
