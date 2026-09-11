import { useEffect, useState, type ReactNode } from 'react';
import PortalLayout from '@/components/public/PortalLayout';
import PublicPageHeader from '@/components/public/PublicPageHeader';
import AppLink from '@/components/public/AppLink';
import { ErrorCard, LoadingBlock } from '@/components/public/helpers';
import { Pagination, SelectInput } from '@/components/ui';
import api from '@/lib/api-client';
import type { ApiEnvelope } from '@/lib/api-types';
import usePublicList from '@/hooks/usePublicList';
import useQueryParams from '@/hooks/useQueryParams';
import useStudent from '@/hooks/useStudent';
import useTranslation from '@/hooks/useTranslation';
import { studentPortalNav } from '@/config/portalNav';
import type { CollegeNoteItem, SubjectFilterOption } from '../../../../types';

interface NoteFilters {
    subjects: SubjectFilterOption[];
}

export default function CollegeNotesIndex() {
    const { t, tx, d } = useTranslation();
    const { student } = useStudent();

    const [params, setParams] = useQueryParams({ page: '1', subject: '' });
    const [subjects, setSubjects] = useState<SubjectFilterOption[]>([]);

    const hasCollege = Boolean(student?.college);

    useEffect(() => {
        if (!hasCollege) return undefined;

        let cancelled = false;

        api.get<ApiEnvelope<NoteFilters>>('/student/college/notes/filters')
            .then(({ data }) => {
                if (!cancelled) setSubjects(data.result.subjects ?? []);
            })
            .catch(() => undefined);

        return () => {
            cancelled = true;
        };
    }, [hasCollege]);

    const list = usePublicList<CollegeNoteItem>({
        url: '/student/college/notes',
        params: {
            page: params.page !== '1' ? params.page : undefined,
            subject_id: params.subject || undefined,
        },
        enabled: hasCollege,
        errorMessage: t('browse.load_error'),
    });

    return (
        <>
            <PublicPageHeader title={t('college.notes_title')} />

            {!hasCollege ? (
                <div className="rounded-card border border-hairline bg-white p-6">
                    <p className="font-medium text-ink">{t('college.no_college_title')}</p>
                    <p className="mt-1 text-sm text-ink-muted">
                        {t('college.no_college_message')}
                    </p>
                    <AppLink
                        href="/account/profile"
                        className="mt-3 inline-block font-medium text-brand-accent hover:underline"
                    >
                        {t('college.go_to_profile')}
                    </AppLink>
                </div>
            ) : (
                <>
                    {subjects.length > 0 && (
                        <div className="mb-4 max-w-xs">
                            <SelectInput
                                label={t('college.filter_subject')}
                                value={params.subject}
                                onChange={(e) =>
                                    setParams({ subject: e.target.value, page: '1' })
                                }
                            >
                                <option value="">{t('college.all_subjects')}</option>
                                {subjects.map((subject) => (
                                    <option key={subject.value} value={subject.value}>
                                        {subject.label}
                                    </option>
                                ))}
                            </SelectInput>
                        </div>
                    )}

                    {list.error ? (
                        <ErrorCard message={list.error} onRetry={list.refetch} />
                    ) : list.loading ? (
                        <LoadingBlock />
                    ) : list.rows.length === 0 ? (
                        <p className="rounded-card border border-hairline bg-white p-6 text-sm text-ink-muted">
                            {t('college.notes_empty')}
                        </p>
                    ) : (
                        <>
                            <ul className="space-y-3">
                                {list.rows.map((note) => (
                                    <li
                                        key={note.id}
                                        className="rounded-card border border-hairline bg-white p-4 shadow-sm"
                                    >
                                        <p className="font-medium text-ink">{tx(note.title)}</p>

                                        <p className="mt-0.5 text-xs text-ink-muted">
                                            {note.subject ? tx(note.subject.name) : ''}
                                            {note.published_at ? ` · ${d(note.published_at)}` : ''}
                                        </p>

                                        {tx(note.description) && (
                                            <p className="mt-2 text-sm whitespace-pre-line text-ink-muted">
                                                {tx(note.description)}
                                            </p>
                                        )}

                                        {note.has_attachment && (
                                            <a
                                                href={`/v1/student/college/notes/${note.id}/attachment`}
                                                className="mt-3 inline-block text-sm font-medium text-brand-accent hover:underline"
                                            >
                                                {t('college.download')}
                                            </a>
                                        )}
                                    </li>
                                ))}
                            </ul>

                            {list.pagination && (
                                <div className="mt-4">
                                    <Pagination
                                        {...list.pagination}
                                        onPageChange={(page) => setParams({ page: String(page) })}
                                    />
                                </div>
                            )}
                        </>
                    )}
                </>
            )}
        </>
    );
}

CollegeNotesIndex.layout = (page: ReactNode) => (
    <PortalLayout items={studentPortalNav}>{page}</PortalLayout>
);
