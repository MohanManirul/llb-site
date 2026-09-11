import { FormEvent, useState, type ReactNode } from 'react';
import PublicLayout from '@/components/public/PublicLayout';
import AuthCard from '@/components/public/AuthCard';
import AppLink from '@/components/public/AppLink';
import { Button, TextInput } from '@/components/ui';
import api from '@/lib/api-client';
import { errorMessage, flash, validationErrors } from '@/lib/flash';
import useTranslation from '@/hooks/useTranslation';

export default function TeacherForgotPassword() {
    const { t } = useTranslation();

    const [email, setEmail] = useState('');
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [submitting, setSubmitting] = useState(false);

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        setSubmitting(true);
        setErrors({});

        try {
            await api.post('/teacher/auth/forgot-password', { email });
            flash.success(t('account.forgot_sent'));
        } catch (error) {
            const status = (error as { response?: { status?: number } })?.response?.status;

            if (status === 422) {
                setErrors(validationErrors(error));
            } else {
                flash.error(errorMessage(error, t('common.error')));
            }
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <AuthCard
            title={t('teacher.forgot_title')}
            subtitle={t('account.forgot_hint')}
            footer={
                <AppLink
                    href="/teacher/login"
                    className="font-medium text-brand-accent hover:underline"
                >
                    {t('teacher.back_to_login')}
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
                    error={errors.email}
                    required
                    autoFocus
                />

                <Button type="submit" loading={submitting} fullWidth>
                    {t('account.send_link')}
                </Button>
            </form>
        </AuthCard>
    );
}

TeacherForgotPassword.layout = (page: ReactNode) => <PublicLayout>{page}</PublicLayout>;
