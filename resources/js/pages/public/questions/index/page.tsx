import { FormEvent, useEffect, useMemo, useState, type ReactNode } from 'react';
import { ChevronDownIcon, ChevronUpIcon, MagnifyingGlassIcon } from '@heroicons/react/24/outline';
import PublicLayout from '@/components/public/PublicLayout';
import PublicPageHeader from '@/components/public/PublicPageHeader';
import { ErrorCard, LoadingBlock, PublicEmptyState } from '@/components/public/helpers';
import { Pagination } from '@/components/ui';
import api from '@/lib/api-client';
import type { ApiEnvelope } from '@/lib/api-types';
import usePublicList from '@/hooks/usePublicList';
import useQueryParams from '@/hooks/useQueryParams';
import useTranslation from '@/hooks/useTranslation';
import { EXAM_STAGE_LABELS, type ArchiveFilterDef, type ArchiveQuestion } from '../../types';

const DEFAULTS = {
    type: 'mcq',
    program: '',
    subject: '',
    exam_stage: '',
    exam_year: '',
    search: '',
    page: '1',
};

export default function QuestionArchiveIndex() {
    const { t, tx, n } = useTranslation();
    const [params, setParams] = useQueryParams(DEFAULTS);
    const [filters, setFilters] = useState<ArchiveFilterDef[]>([]);
    const [searchDraft, setSearchDraft] = useState(params.search);

    useEffect(() => {
        let cancelled = false;

        api.get<ApiEnvelope<ArchiveFilterDef[]>>('/public/question-archive/filters')
            .then(({ data }) => {
                if (!cancelled) setFilters(data.result);
            })
            .catch(() => undefined);

        return () => {
            cancelled = true;
        };
    }, []);

    useEffect(() => {
        setSearchDraft(params.search);
    }, [params.search]);

    const type = params.type === 'written' ? 'written' : 'mcq';

    const listParams = useMemo(
        () => ({
            program: params.program || undefined,
            subject: params.subject || undefined,
            exam_stage: params.exam_stage || undefined,
            exam_year: params.exam_year || undefined,
            search: params.search || undefined,
            page: params.page !== '1' ? params.page : undefined,
        }),
        [params.program, params.subject, params.exam_stage, params.exam_year, params.search, params.page],
    );

    const list = usePublicList<ArchiveQuestion>({
        url: `/public/question-archive/${type}`,
        params: listParams,
        errorMessage: t('browse.load_error'),
    });

    const filterFor = (key: ArchiveFilterDef['key']) => filters.find((filter) => filter.key === key);

    const subjectOptions = useMemo(() => {
        const subject = filterFor('subject');
        if (!subject) return [];

        return params.program
            ? subject.options.filter((option) => option.program === params.program)
            : subject.options;
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [filters, params.program]);

    const hasFilters = Boolean(params.program || params.subject || params.exam_stage || params.exam_year || params.search);

    const reset = () => setParams({ ...DEFAULTS, type });

    const submitSearch = (event: FormEvent) => {
        event.preventDefault();
        setParams({ search: searchDraft.trim(), page: '1' });
    };

    const tabClass = (active: boolean) =>
        'rounded-chip border px-4 py-1.5 text-sm font-medium transition ' +
        (active ? 'border-brand bg-brand text-white' : 'border-hairline bg-white text-ink hover:border-brand-muted');

    const selectClass =
        'h-9 rounded-control border border-hairline bg-white px-3 text-sm text-ink focus:border-brand-accent focus:outline-none';

    return (
        <>
            <PublicPageHeader
                title={t('prep.archive_title')}
                description={t('prep.archive_desc')}
                crumbs={[{ label: t('nav.exam_prep'), href: '/exam-prep' }, { label: t('nav.question_archive') }]}
            >
                <p className="mt-2 max-w-2xl text-sm text-ink-muted">{t('prep.archive_desc')}</p>
            </PublicPageHeader>

            <div className="flex flex-wrap items-center gap-2">
                <button type="button" onClick={() => setParams({ type: 'mcq', page: '1' })} className={tabClass(type === 'mcq')}>
                    {t('archive.mcq')}
                </button>
                <button type="button" onClick={() => setParams({ type: 'written', page: '1' })} className={tabClass(type === 'written')}>
                    {t('archive.written')}
                </button>

                {list.total !== null && (
                    <span className="ml-auto text-sm text-ink-muted">{t('browse.results', { count: n(list.total) })}</span>
                )}
            </div>

            <div className="mt-4 flex flex-wrap items-center gap-2 rounded-card border border-hairline bg-white p-3">
                <form onSubmit={submitSearch} className="relative min-w-0 flex-1 basis-56">
                    <MagnifyingGlassIcon className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <input
                        type="search"
                        value={searchDraft}
                        onChange={(e) => setSearchDraft(e.target.value)}
                        placeholder={t('archive.search_placeholder')}
                        className="h-9 w-full rounded-control border border-hairline bg-field pl-9 pr-3 text-sm text-ink placeholder:text-gray-400 focus:border-brand-accent focus:outline-none"
                    />
                </form>

                <select
                    value={params.program}
                    onChange={(e) => setParams({ program: e.target.value, subject: '', page: '1' })}
                    aria-label={t('filter.program')}
                    className={selectClass}
                >
                    <option value="">{t('filter.all_programs')}</option>
                    {filterFor('program')?.options.map((option) => (
                        <option key={option.value} value={option.value}>
                            {tx(option.label)}
                        </option>
                    ))}
                </select>

                <select
                    value={params.subject}
                    onChange={(e) => setParams({ subject: e.target.value, page: '1' })}
                    aria-label={t('material.subject')}
                    className={selectClass}
                >
                    <option value="">{t('material.subject')}: {t('browse.all')}</option>
                    {subjectOptions.map((option) => (
                        <option key={option.value} value={option.value}>
                            {tx(option.label)}
                        </option>
                    ))}
                </select>

                <select
                    value={params.exam_stage}
                    onChange={(e) => setParams({ exam_stage: e.target.value, page: '1' })}
                    aria-label={t('practice.stage')}
                    className={selectClass}
                >
                    <option value="">{t('practice.stage')}: {t('browse.all')}</option>
                    {filterFor('exam_stage')?.options.map((option) => (
                        <option key={option.value} value={option.value}>
                            {tx(option.label)}
                        </option>
                    ))}
                </select>

                <select
                    value={params.exam_year}
                    onChange={(e) => setParams({ exam_year: e.target.value, page: '1' })}
                    aria-label={t('archive.year')}
                    className={selectClass}
                >
                    <option value="">{t('archive.year')}: {t('browse.all')}</option>
                    {filterFor('exam_year')?.options.map((option) => (
                        <option key={option.value} value={option.value}>
                            {n(option.value)}
                        </option>
                    ))}
                </select>

                {hasFilters && (
                    <button type="button" onClick={reset} className="text-sm font-medium text-brand-accent hover:underline">
                        {t('browse.reset')}
                    </button>
                )}
            </div>

            <div className="mt-4">
                {list.error ? (
                    <ErrorCard message={list.error} onRetry={list.refetch} />
                ) : list.loading ? (
                    <LoadingBlock />
                ) : list.rows.length === 0 ? (
                    <PublicEmptyState onReset={hasFilters ? reset : undefined} />
                ) : (
                    <>
                        <ol className="space-y-3">
                            {list.rows.map((question, index) => (
                                <ArchiveQuestionCard
                                    key={question.id}
                                    question={question}
                                    serial={(Number(params.page) - 1) * 12 + index + 1}
                                />
                            ))}
                        </ol>

                        <div className="mt-6 flex justify-center">
                            <Pagination
                                links={list.pagination?.links}
                                onPageChange={(page) => {
                                    if (page == null) return;
                                    setParams({ page: String(page) });
                                    window.scrollTo({ top: 0 });
                                }}
                            />
                        </div>
                    </>
                )}
            </div>
        </>
    );
}

function ArchiveQuestionCard({ question, serial }: { question: ArchiveQuestion; serial: number }) {
    const { t, tx, n } = useTranslation();
    const [open, setOpen] = useState(false);

    const meta = [
        question.subject ? tx(question.subject.name) : null,
        question.exam_stage ? tx(EXAM_STAGE_LABELS[question.exam_stage] ?? null) || question.exam_stage : null,
        question.exam_year ? n(question.exam_year) : null,
    ].filter(Boolean);

    const hasAnswer = Boolean(question.options?.length || tx(question.explanation) || question.reference);

    return (
        <li className="rounded-card border border-hairline bg-white p-4 shadow-sm">
            <p className="flex gap-2 font-medium text-ink">
                <span className="shrink-0 text-ink-muted">{n(serial)}.</span>
                <span className="whitespace-pre-line">{tx(question.question)}</span>
            </p>

            {meta.length > 0 && <p className="mt-2 text-xs text-ink-muted">{meta.join(' · ')}</p>}

            {question.options && question.options.length > 0 && (
                <ul className="mt-3 grid gap-1.5 sm:grid-cols-2">
                    {question.options.map((option, index) => (
                        <li
                            key={option.id}
                            className={
                                'flex items-start gap-2 rounded-control border px-3 py-1.5 text-sm ' +
                                (open && option.is_correct
                                    ? 'border-emerald-300 bg-emerald-50 text-emerald-900'
                                    : 'border-hairline text-ink')
                            }
                        >
                            <span className="shrink-0 font-semibold text-ink-muted">{String.fromCharCode(65 + index)}.</span>
                            <span>{tx(option.option)}</span>
                        </li>
                    ))}
                </ul>
            )}

            {hasAnswer && (
                <button
                    type="button"
                    onClick={() => setOpen((value) => !value)}
                    className="mt-3 inline-flex items-center gap-1 text-sm font-medium text-brand-accent hover:underline"
                >
                    {open ? <ChevronUpIcon className="h-4 w-4" /> : <ChevronDownIcon className="h-4 w-4" />}
                    {open ? t('archive.hide_answer') : t('archive.show_answer')}
                </button>
            )}

            {open && (tx(question.explanation) || question.reference) && (
                <div className="mt-2 rounded-control bg-brass-soft/60 px-3 py-2 text-sm text-ink">
                    {tx(question.explanation) && (
                        <p className="whitespace-pre-line">
                            <span className="font-semibold">{t('archive.explanation')}: </span>
                            {tx(question.explanation)}
                        </p>
                    )}
                    {question.reference && (
                        <p className="mt-1 text-xs text-ink-muted">
                            {t('archive.reference')}: {question.reference}
                        </p>
                    )}
                </div>
            )}
        </li>
    );
}

QuestionArchiveIndex.layout = (page: ReactNode) => <PublicLayout>{page}</PublicLayout>;
