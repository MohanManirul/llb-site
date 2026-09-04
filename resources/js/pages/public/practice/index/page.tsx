import { FormEvent, useEffect, useMemo, useState, type ReactNode } from 'react';
import { router } from '@inertiajs/react';
import { PlayIcon } from '@heroicons/react/24/outline';
import PublicLayout from '@/components/public/PublicLayout';
import PublicPageHeader from '@/components/public/PublicPageHeader';
import AppLink from '@/components/public/AppLink';
import { ErrorCard, LoadingBlock } from '@/components/public/helpers';
import { Button, SelectInput, TextInput } from '@/components/ui';
import api from '@/lib/api-client';
import type { ApiEnvelope, ResourcePaginator } from '@/lib/api-types';
import { errorMessage } from '@/lib/flash';
import usePublicList from '@/hooks/usePublicList';
import useTranslation from '@/hooks/useTranslation';
import { EXAM_STAGE_LABELS, type PracticeSession, type PracticeSubject } from '../../types';

const COUNTS = [10, 20, 30, 50];
const HISTORY_PARAMS = { per_page: 5 };

export default function PracticeIndex() {
    const { t, tx, n, d, localeHref } = useTranslation();

    const [subjects, setSubjects] = useState<PracticeSubject[] | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [subjectId, setSubjectId] = useState('');
    const [count, setCount] = useState('10');
    const [stage, setStage] = useState('');
    const [year, setYear] = useState('');

    const load = () => {
        setError(null);
        setSubjects(null);

        api.get<ApiEnvelope<ResourcePaginator<PracticeSubject> | PracticeSubject[]>>('/student/practice/subjects')
            .then(({ data }) => {
                const result = data.result;
                setSubjects(Array.isArray(result) ? result : (result.data ?? []));
            })
            .catch((err) => setError(errorMessage(err, t('browse.load_error'))));
    };

    useEffect(load, []); // eslint-disable-line react-hooks/exhaustive-deps

    const history = usePublicList<PracticeSession>({
        url: '/student/practice/sessions',
        params: HISTORY_PARAMS,
        errorMessage: t('browse.load_error'),
    });

    const grouped = useMemo(() => {
        const map = new Map<string, { label: string; items: PracticeSubject[] }>();

        (subjects ?? []).forEach((subject) => {
            const key = subject.program?.slug ?? '';
            const label = subject.program ? tx(subject.program.name) : '';

            if (!map.has(key)) map.set(key, { label, items: [] });
            map.get(key)!.items.push(subject);
        });

        return Array.from(map.values());
    }, [subjects, tx]);

    const selected = subjects?.find((subject) => String(subject.id) === subjectId) ?? null;

    const start = (event: FormEvent) => {
        event.preventDefault();
        if (!subjectId) return;

        const query = new URLSearchParams({ subject: subjectId, count });
        if (stage) query.set('exam_stage', stage);
        if (year) query.set('exam_year', year);

        router.visit(localeHref(`/practice/run?${query.toString()}`));
    };

    return (
        <>
            <PublicPageHeader
                title={t('practice.title')}
                description={t('practice.subtitle')}
                crumbs={[{ label: t('nav.exam_prep'), href: '/exam-prep' }, { label: t('nav.practice') }]}
            >
                <p className="mt-2 max-w-2xl text-sm text-ink-muted">{t('practice.subtitle')}</p>
            </PublicPageHeader>

            <div className="grid gap-6 lg:grid-cols-[1fr_minmax(0,22rem)]">
                <section className="rounded-card border border-hairline bg-white p-5 shadow-sm">
                    {error ? (
                        <ErrorCard message={error} onRetry={load} />
                    ) : subjects === null ? (
                        <LoadingBlock />
                    ) : subjects.length === 0 ? (
                        <p className="py-8 text-center text-sm text-ink-muted">{t('practice.no_subjects')}</p>
                    ) : (
                        <form onSubmit={start} className="space-y-5">
                            <div>
                                <h2 className="font-semibold text-ink">{t('practice.pick_subject')}</h2>

                                <div className="mt-3 space-y-4">
                                    {grouped.map((group) => (
                                        <div key={group.label}>
                                            {group.label && (
                                                <p className="mb-1.5 text-xs font-semibold uppercase tracking-wide text-ink-muted">
                                                    {group.label}
                                                </p>
                                            )}
                                            <div className="grid gap-2 sm:grid-cols-2">
                                                {group.items.map((subject) => {
                                                    const active = String(subject.id) === subjectId;

                                                    return (
                                                        <label
                                                            key={subject.id}
                                                            className={
                                                                'flex cursor-pointer items-center gap-3 rounded-control border px-3 py-2.5 text-sm transition ' +
                                                                (active
                                                                    ? 'border-brand bg-brand/5'
                                                                    : 'border-hairline hover:border-brand-muted hover:bg-gray-50')
                                                            }
                                                        >
                                                            <input
                                                                type="radio"
                                                                name="subject"
                                                                value={subject.id}
                                                                checked={active}
                                                                onChange={() => setSubjectId(String(subject.id))}
                                                                className="h-4 w-4 accent-brand"
                                                            />
                                                            <span className="min-w-0 flex-1">
                                                                <span className="block font-medium text-ink">{tx(subject.name)}</span>
                                                                {subject.level && (
                                                                    <span className="block text-xs text-ink-muted">{tx(subject.level.name)}</span>
                                                                )}
                                                            </span>
                                                            <span className="shrink-0 rounded-chip bg-gray-100 px-2 py-0.5 text-xs text-ink-muted">
                                                                {t('practice.available', { count: n(subject.question_count) })}
                                                            </span>
                                                        </label>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <div className="grid gap-4 border-t border-hairline pt-5 sm:grid-cols-3">
                                <SelectInput label={t('practice.count')} value={count} onChange={(e) => setCount(e.target.value)}>
                                    {COUNTS.map((value) => (
                                        <option key={value} value={value}>
                                            {n(value)}
                                        </option>
                                    ))}
                                </SelectInput>

                                <SelectInput label={t('practice.stage')} value={stage} onChange={(e) => setStage(e.target.value)}>
                                    <option value="">{t('practice.any')}</option>
                                    {Object.entries(EXAM_STAGE_LABELS).map(([value, label]) => (
                                        <option key={value} value={value}>
                                            {tx(label)}
                                        </option>
                                    ))}
                                </SelectInput>

                                <TextInput
                                    label={t('practice.year')}
                                    type="number"
                                    inputMode="numeric"
                                    min={1972}
                                    max={2100}
                                    value={year}
                                    onChange={(e) => setYear(e.target.value)}
                                    placeholder={t('practice.any')}
                                />
                            </div>

                            <Button type="submit" disabled={!selected} fullWidth>
                                <PlayIcon className="h-4 w-4" />
                                {t('practice.start')}
                            </Button>
                        </form>
                    )}
                </section>

                <aside>
                    <div className="flex items-center justify-between">
                        <h2 className="font-semibold text-ink">{t('account.practice_history')}</h2>
                        <AppLink href="/account/profile" className="text-sm font-medium text-brand-accent hover:underline">
                            {t('home.view_all')}
                        </AppLink>
                    </div>

                    <div className="mt-3">
                        {history.loading ? (
                            <LoadingBlock />
                        ) : history.rows.length === 0 ? (
                            <p className="rounded-card border border-dashed border-hairline bg-white px-4 py-8 text-center text-sm text-ink-muted">
                                {t('account.no_practice')}
                            </p>
                        ) : (
                            <ul className="divide-y divide-hairline rounded-card border border-hairline bg-white">
                                {history.rows.map((session) => (
                                    <li key={session.id} className="px-4 py-3 text-sm">
                                        <p className="flex items-center gap-2">
                                            <span className="min-w-0 flex-1 truncate font-medium text-ink">
                                                {session.subject ? tx(session.subject.name) : '—'}
                                            </span>
                                            <span className="shrink-0 rounded-chip bg-brass-soft px-2 py-0.5 text-xs font-semibold text-brass-deep">
                                                {t('account.practice_score', {
                                                    correct: n(session.correct_count),
                                                    total: n(session.question_count),
                                                })}
                                            </span>
                                        </p>
                                        <p className="mt-0.5 text-xs text-ink-muted">{d(session.created_at)}</p>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </aside>
            </div>
        </>
    );
}

PracticeIndex.layout = (page: ReactNode) => <PublicLayout>{page}</PublicLayout>;
