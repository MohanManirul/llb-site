import { useCallback } from 'react';
import { usePage } from '@inertiajs/react';
import {
    pickTranslated,
    translate,
    type Locale,
    type TranslatedField,
    type TranslationKey,
} from '@/lib/i18n';
import {
    formatDateIn,
    formatNumberIn,
    type FormattableValue,
} from '@/lib/format';

export default function useTranslation() {
    const locale = (usePage().props.locale ?? 'bn') as Locale;

    const t = useCallback(
        (key: TranslationKey, vars?: Record<string, string | number>) =>
            translate(locale, key, vars),
        [locale],
    );

    const tx = useCallback(
        (field: TranslatedField | null | undefined) => pickTranslated(locale, field),
        [locale],
    );

    const n = useCallback(
        (value: FormattableValue) => formatNumberIn(locale, value),
        [locale],
    );

    const d = useCallback(
        (value: FormattableValue) => formatDateIn(locale, value),
        [locale],
    );

    const localeHref = useCallback(
        (path: string) => `/${locale}${path === '/' ? '' : path}`,
        [locale],
    );

    return { locale, isBn: locale === 'bn', t, tx, n, d, localeHref };
}
