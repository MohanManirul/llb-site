import { BookmarkIcon } from '@heroicons/react/24/outline';
import useTranslation from '@/hooks/useTranslation';
import type { PublicSubject } from '@/pages/public/types';
import AppLink from './AppLink';

interface SubjectCardProps {
    subject: PublicSubject;
}

export default function SubjectCard({ subject }: SubjectCardProps) {
    const { t, tx, n } = useTranslation();

    const counts = [
        { label: t('type.suggestion'), count: subject.suggestions_count ?? 0 },
        { label: t('type.book'), count: subject.books_count ?? 0 },
        { label: t('type.note'), count: subject.notes_count ?? 0 },
    ].filter((entry) => entry.count > 0);

    return (
        <AppLink
            href={`/subjects/${subject.slug}`}
            className="group flex flex-col rounded-card border border-hairline bg-white p-4 shadow-sm transition hover:border-brand-muted hover:shadow"
        >
            <div className="flex items-start gap-3">
                <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-chip bg-brand/5">
                    <BookmarkIcon className="h-5 w-5 text-brand" />
                </span>

                <div className="min-w-0">
                    <h3 className="line-clamp-2 font-semibold text-ink group-hover:text-brand">
                        {tx(subject.name)}
                    </h3>
                    <p className="mt-0.5 text-xs text-ink-muted">
                        {subject.code ? t('subject.code', { code: subject.code }) : ''}
                        {subject.code && subject.marks ? ' · ' : ''}
                        {subject.marks ? t('subject.marks', { marks: n(subject.marks) }) : ''}
                    </p>
                </div>
            </div>

            {counts.length > 0 && (
                <p className="mt-3 text-xs text-ink-muted">
                    {counts.map((entry) => `${entry.label} ${n(entry.count)}`).join(' · ')}
                </p>
            )}
        </AppLink>
    );
}
