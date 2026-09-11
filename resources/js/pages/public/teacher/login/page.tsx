import { FormEvent, useState, type ReactNode } from 'react';
import PublicLayout from '@/components/public/PublicLayout';
import AuthCard from '@/components/public/AuthCard';
import AppLink from '@/components/public/AppLink';
import { Button, Checkbox, TextInput } from '@/components/ui';
import api from '@/lib/api-client';
import { errorMessage, flash, validationErrors } from '@/lib/flash';
import useTeacher from '@/hooks/useTeacher';
import useTranslation from '@/hooks/useTranslation';

export default function TeacherLogin() {
    const { t } = useTranslation();
    const { redirectAfterAuth, isPendingError } = useTeacher();

    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [remember, setRemember] = useState(true);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [pending, setPending] = useState(false);
    const [submitting, setSubmitting] = useState(false);

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        setSubmitting(true);
        setErrors({});
        setPending(false);

        try {
            await api.post('/teacher/auth/login', { email, password, remember });
            redirectAfterAuth();
        } catch (error) {
            const status = (error as { response?: { status?: number } })?.response?.status;

            if (isPendingError(error)) {
                setPending(true);
                flash.error(t('teacher.pending_login_error'));
            } else if (status === 422) {
                setErrors(validationErrors(error));
            } else {
                flash.error(errorMessage(error, t('common.error')));
            }

            setSubmitting(false);
        }
    };

    return (
        <AuthCard
            title={t('teacher.login_title')}
            footer={
                <>
                    {t('teacher.no_account')}{' '}
                    <AppLink
                        href="/teacher/register"
                        className="font-medium text-brand-accent hover:underline"
                    >
                        {t('teacher.register_title')}
                    </AppLink>
                </>
            }
        >
            {pending && (
                <div className="mb-4 rounded-card border border-hairline bg-amber-50 p-3 text-sm text-ink">
                    <p>{t('teacher.pending_login_error')}</p>
                    <AppLink
                        href="/teacher/registered"
                        className="mt-1 inline-block font-medium text-brand-accent hover:underline"
                    >
                        {t('teacher.pending_title')}
                    </AppLink>
                </div>
            )}

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
                        href="/teacher/forgot-password"
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

TeacherLogin.layout = (page: ReactNode) => <PublicLayout>{page}</PublicLayout>;
