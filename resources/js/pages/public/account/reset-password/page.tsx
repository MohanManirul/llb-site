import { FormEvent, useState, type ReactNode } from 'react';
import { router } from '@inertiajs/react';
import PublicLayout from '@/components/public/PublicLayout';
import AuthCard from '@/components/public/AuthCard';
import AppLink from '@/components/public/AppLink';
import { Button, TextInput } from '@/components/ui';
import api from '@/lib/api-client';
import { errorMessage, flash, validationErrors } from '@/lib/flash';
import useTranslation from '@/hooks/useTranslation';

interface ResetPasswordProps {
    token: string;
    email: string;
}

export default function StudentResetPassword({ token, email: initialEmail }: ResetPasswordProps) {
    const { t, localeHref } = useTranslation();

    const [email, setEmail] = useState(initialEmail ?? '');
    const [password, setPassword] = useState('');
    const [confirmation, setConfirmation] = useState('');
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [submitting, setSubmitting] = useState(false);

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        setSubmitting(true);
        setErrors({});

        try {
            await api.post('/student/auth/reset-password', {
                token,
                email,
                password,
                password_confirmation: confirmation,
            });
            flash.success(t('account.reset_done'));
            router.visit(localeHref('/account/login'));
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
            title={t('account.reset_title')}
            footer={
                <AppLink href="/account/login" className="font-medium text-brand-accent hover:underline">
                    {t('account.login_btn')}
                </AppLink>
            }
        >
            <form onSubmit={submit} className="space-y-4">
                <TextInput
                    label={t('account.email')}
                    type="email"
                    autoComplete="email"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    error={errors.email ?? errors.token}
                    required
                />

                <TextInput
                    label={t('account.new_password')}
                    type="password"
                    autoComplete="new-password"
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    error={errors.password}
                    required
                    autoFocus
                />

                <TextInput
                    label={t('account.password_confirm')}
                    type="password"
                    autoComplete="new-password"
                    value={confirmation}
                    onChange={(e) => setConfirmation(e.target.value)}
                    error={errors.password_confirmation}
                    required
                />

                <Button type="submit" loading={submitting} fullWidth>
                    {t('account.reset_btn')}
                </Button>
            </form>
        </AuthCard>
    );
}

StudentResetPassword.layout = (page: ReactNode) => <PublicLayout>{page}</PublicLayout>;
