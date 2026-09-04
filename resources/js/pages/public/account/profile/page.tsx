import { FormEvent, useEffect, useState, type ReactNode } from 'react';
import { router, usePage } from '@inertiajs/react';
import PublicLayout from '@/components/public/PublicLayout';
import PublicPageHeader from '@/components/public/PublicPageHeader';
import AppLink from '@/components/public/AppLink';
import { ErrorCard, LoadingBlock } from '@/components/public/helpers';
import { Button, Pagination, SelectInput, TextInput } from '@/components/ui';
import api from '@/lib/api-client';
import type { ApiEnvelope } from '@/lib/api-types';
import { errorMessage, flash, validationErrors } from '@/lib/flash';
import usePublicList from '@/hooks/usePublicList';
import usePublicResource from '@/hooks/usePublicResource';
import useQueryParams from '@/hooks/useQueryParams';
import useStudent from '@/hooks/useStudent';
import useTranslation from '@/hooks/useTranslation';
import type { PracticeSession, StudentProfile } from '../../types';

interface ProfileForm {
    name: string;
    phone: string;
    program_id: string;
    password: string;
    password_confirmation: string;
}

export default function StudentProfilePage() {
    const { t, tx, d, n } = useTranslation();
    const { logout } = useStudent();
    const programs = usePage().props.programs ?? [];

    const profile = usePublicResource<StudentProfile>('/student/auth/me', {
        errorMessage: t('common.error'),
    });

    const [data, setDataState] = useState<ProfileForm | null>(null);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [saving, setSaving] = useState(false);
    const [loggingOut, setLoggingOut] = useState(false);

    useEffect(() => {
        if (profile.data && data === null) {
            setDataState({
                name: profile.data.name,
                phone: profile.data.phone ?? '',
                program_id: profile.data.program_id ? String(profile.data.program_id) : '',
                password: '',
                password_confirmation: '',
            });
        }
    }, [profile.data, data]);

    const [params, setParams] = useQueryParams({ page: '1' });

    const history = usePublicList<PracticeSession>({
        url: '/student/practice/sessions',
        params: { page: params.page !== '1' ? params.page : undefined },
        errorMessage: t('browse.load_error'),
    });

    const setData = <K extends keyof ProfileForm>(field: K, value: ProfileForm[K]) => {
        setDataState((current) => (current ? { ...current, [field]: value } : current));
    };

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        if (!data) return;

        setSaving(true);
        setErrors({});

        try {
            await api.patch<ApiEnvelope<StudentProfile>>('/student/auth/profile', {
                name: data.name,
                phone: data.phone || null,
                program_id: data.program_id || null,
                password: data.password || null,
                password_confirmation: data.password_confirmation || null,
            });
            flash.success(t('account.saved'));
            setData('password', '');
            setData('password_confirmation', '');
            router.reload({ only: ['student'] });
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
                title={t('account.profile_title')}
                crumbs={[{ label: t('nav.exam_prep'), href: '/exam-prep' }, { label: t('nav.profile') }]}
                aside={
                    <Button variant="secondary" size="sm" onClick={signOut} loading={loggingOut}>
                        {t('nav.logout')}
                    </Button>
                }
            >
                {profile.data && (
                    <p className="mt-1 text-sm text-ink-muted">
                        {profile.data.email} · {t('account.member_since', { date: d(profile.data.created_at) })}
                    </p>
                )}
            </PublicPageHeader>

            <div className="grid gap-6 lg:grid-cols-[minmax(0,26rem)_1fr]">
                <section className="rounded-card border border-hairline bg-white p-5 shadow-sm">
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

                            <Button type="submit" loading={saving} fullWidth>
                                {t('account.save')}
                            </Button>
                        </form>
                    )}
                </section>

                <section>
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold text-ink">{t('account.practice_history')}</h2>
                        <AppLink href="/account/attempts" className="text-sm font-medium text-brand-accent hover:underline">
                            {t('nav.my_attempts')}
                        </AppLink>
                    </div>

                    <div className="mt-3">
                        {history.error ? (
                            <ErrorCard message={history.error} onRetry={history.refetch} />
                        ) : history.loading ? (
                            <LoadingBlock />
                        ) : history.rows.length === 0 ? (
                            <p className="rounded-card border border-dashed border-hairline bg-white px-4 py-8 text-center text-sm text-ink-muted">
                                {t('account.no_practice')}
                            </p>
                        ) : (
                            <>
                                <ul className="divide-y divide-hairline rounded-card border border-hairline bg-white">
                                    {history.rows.map((session) => (
                                        <li key={session.id} className="flex items-center gap-3 px-4 py-3 text-sm">
                                            <span className="min-w-0 flex-1 truncate font-medium text-ink">
                                                {session.subject ? tx(session.subject.name) : '—'}
                                            </span>
                                            <span className="shrink-0 rounded-chip bg-brass-soft px-2 py-0.5 text-xs font-semibold text-brass-deep">
                                                {t('account.practice_score', {
                                                    correct: n(session.correct_count),
                                                    total: n(session.question_count),
                                                })}
                                            </span>
                                            <span className="shrink-0 text-xs text-ink-muted">{d(session.created_at)}</span>
                                        </li>
                                    ))}
                                </ul>

                                <div className="mt-4 flex justify-center">
                                    <Pagination
                                        links={history.pagination?.links}
                                        onPageChange={(page) => {
                                            if (page == null) return;
                                            setParams({ page: String(page) });
                                        }}
                                    />
                                </div>
                            </>
                        )}
                    </div>
                </section>
            </div>
        </>
    );
}

StudentProfilePage.layout = (page: ReactNode) => <PublicLayout>{page}</PublicLayout>;
