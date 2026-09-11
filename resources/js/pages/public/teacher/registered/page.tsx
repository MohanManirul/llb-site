import { type ReactNode } from 'react';
import PublicLayout from '@/components/public/PublicLayout';
import AuthCard from '@/components/public/AuthCard';
import AppLink from '@/components/public/AppLink';
import useTranslation from '@/hooks/useTranslation';

export default function TeacherRegistered() {
    const { t } = useTranslation();

    return (
        <AuthCard title={t('teacher.pending_title')}>
            <p className="text-sm text-ink-muted">{t('teacher.pending_message')}</p>

            <AppLink
                href="/teacher/login"
                className="mt-4 inline-block font-medium text-brand-accent hover:underline"
            >
                {t('teacher.back_to_login')}
            </AppLink>
        </AuthCard>
    );
}

TeacherRegistered.layout = (page: ReactNode) => <PublicLayout>{page}</PublicLayout>;
