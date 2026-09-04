import { router, usePage } from '@inertiajs/react';
import useTranslation from '@/hooks/useTranslation';
import type { Locale } from '@/lib/i18n';

export default function LanguageToggle() {
    const { locale } = useTranslation();
    const pageUrl = usePage().url;

    const switchTo = (next: Locale) => {
        if (next === locale) return;

        const [path, query] = pageUrl.split('?');
        const segments = path.split('/').filter(Boolean);

        if (segments[0] === 'bn' || segments[0] === 'en') {
            segments[0] = next;
        } else {
            segments.unshift(next);
        }

        router.visit(`/${segments.join('/')}${query ? `?${query}` : ''}`, {
            preserveScroll: true,
        });
    };

    return (
        <div className="flex shrink-0 items-center overflow-hidden rounded-chip border border-hairline text-xs font-semibold">
            <button
                type="button"
                onClick={() => switchTo('bn')}
                aria-pressed={locale === 'bn'}
                className={
                    'px-2.5 py-1.5 transition ' +
                    (locale === 'bn' ? 'bg-brand text-white' : 'bg-white text-ink hover:bg-gray-100')
                }
            >
                বাংলা
            </button>
            <button
                type="button"
                onClick={() => switchTo('en')}
                aria-pressed={locale === 'en'}
                className={
                    'px-2.5 py-1.5 transition ' +
                    (locale === 'en' ? 'bg-brand text-white' : 'bg-white text-ink hover:bg-gray-100')
                }
            >
                EN
            </button>
        </div>
    );
}
