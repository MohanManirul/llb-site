import { FormEvent, useState, type ReactNode } from 'react';
import { CheckCircleIcon } from '@heroicons/react/24/outline';
import PublicLayout from '@/components/public/PublicLayout';
import AuthCard from '@/components/public/AuthCard';
import AppLink from '@/components/public/AppLink';
import { Button, TextInput } from '@/components/ui';
import api from '@/lib/api-client';
import { errorMessage, flash, validationErrors } from '@/lib/flash';
import useTranslation from '@/hooks/useTranslation';

export default function StudentForgotPassword() {
    const { t } = useTranslation();

    const [email, setEmail] = useState('');
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [submitting, setSubmitting] = useState(false);
    const [sent, setSent] = useState(false);

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        setSubmitting(true);
        setErrors({});

        try {
            await api.post('/student/auth/forgot-password', { email });
            setSent(true);
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
            title={t('account.forgot_title')}
            subtitle={t('account.forgot_hint')}
            footer={
                <AppLink href="/account/login" className="font-medium text-brand-accent hover:underline">
                    {t('account.login_btn')}
                </AppLink>
            }
        >
            {sent ? (
                <div className="flex items-start gap-3 rounded-control bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    <CheckCircleIcon className="h-5 w-5 shrink-0" />
                    {t('account.forgot_sent')}
                </div>
            ) : (
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
            )}
        </AuthCard>
    );
}

StudentForgotPassword.layout = (page: ReactNode) => <PublicLayout>{page}</PublicLayout>;
