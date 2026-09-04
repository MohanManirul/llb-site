
const BLANK = '—';
const LOCALE = 'en-GB';
const BD_TIMEZONE = 'Asia/Dhaka';
const DATE_ONLY = /^\d{4}-\d{2}-\d{2}$/;
const BD_COUNTRY_CODE = '880';

export const CURRENCY_SYMBOL = '৳';
export const CURRENCY_PRECISION = 2;
export const CURRENCY_GAP = '\u00A0';

export function withCurrency(amount: string): string {
    return CURRENCY_SYMBOL + CURRENCY_GAP + amount;
}

export type FormattableValue = string | number | Date | null | undefined;

function parse(value: FormattableValue): { date: Date; timeZone: string } | null {
    if (value === null || value === undefined || value === '') return null;

    const dateOnly = typeof value === 'string' && DATE_ONLY.test(value);
    const date = new Date(dateOnly ? `${value}T00:00:00Z` : value);

    if (Number.isNaN(date.getTime())) return null;

    return { date, timeZone: dateOnly ? 'UTC' : BD_TIMEZONE };
}

function toNumber(value: FormattableValue): number | null {
    if (value === null || value === undefined || value === '') return null;

    const n = Number(value);

    return Number.isFinite(n) ? n : null;
}

export function formatDate(value: FormattableValue): string {
    const parsed = parse(value);
    if (!parsed) return BLANK;

    return parsed.date.toLocaleDateString(LOCALE, {
        timeZone: parsed.timeZone,
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

export function formatDateTime(value: FormattableValue): string {
    const parsed = parse(value);
    if (!parsed) return BLANK;

    return parsed.date.toLocaleString(LOCALE, {
        timeZone: parsed.timeZone,
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
    });
}

export function formatTime(value: FormattableValue): string {
    const parsed = parse(value);
    if (!parsed) return BLANK;

    return parsed.date.toLocaleTimeString(LOCALE, {
        timeZone: parsed.timeZone,
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
    });
}

export function formatTimeAgo(value: FormattableValue): string {
    const parsed = parse(value);
    if (!parsed) return BLANK;

    const seconds = Math.max(0, Math.round((Date.now() - parsed.date.getTime()) / 1000));

    if (seconds < 60) return 'just now';

    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `${minutes}m ago`;

    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours}h ago`;

    const days = Math.floor(hours / 24);
    if (days < 30) return `${days}d ago`;

    const months = Math.floor(days / 30);
    if (months < 12) return `${months}mo ago`;

    return `${Math.floor(months / 12)}y ago`;
}

export function formatMoney(value: FormattableValue): string {
    const n = toNumber(value);
    if (n === null) return BLANK;

    return withCurrency(
        n.toLocaleString(LOCALE, {
            minimumFractionDigits: CURRENCY_PRECISION,
            maximumFractionDigits: CURRENCY_PRECISION,
        }),
    );
}

export function formatPercent(value: FormattableValue): string {
    const n = toNumber(value);
    if (n === null) return BLANK;

    return n.toLocaleString(LOCALE, { maximumFractionDigits: 2 });
}

export function formatNumber(value: FormattableValue): string {
    const n = toNumber(value);
    if (n === null) return BLANK;

    return n.toLocaleString(LOCALE);
}

export function formatQuantity(value: FormattableValue): string {
    const n = toNumber(value);
    if (n === null) return BLANK;

    return `${n.toLocaleString(LOCALE)} Orders`;
}

export function displayPhone(phone: string | null | undefined): string {
    const href = dialHref(phone);

    if (href === null) return phone?.trim() ?? BLANK;

    const dialled = href.slice('tel:'.length);

    return dialled.startsWith('+') ? dialled : (phone?.trim() ?? dialled);
}

export function dialHref(phone: string | null | undefined): string | null {
    if (!phone) return null;

    const trimmed = phone.trim();
    const hadPlus = trimmed.startsWith('+');
    const digits = trimmed.replace(/\D/g, '');

    if (digits === '') return null;

    if (hadPlus) return `tel:+${digits}`;

    if (digits.startsWith('0')) return `tel:+${BD_COUNTRY_CODE}${digits.slice(1)}`;

    if (digits.startsWith(BD_COUNTRY_CODE)) return `tel:+${digits}`;

    return `tel:${digits}`;
}

export function toDateInput(value: FormattableValue): string {
    if (!value) return '';
    if (value instanceof Date) return value.toISOString().slice(0, 10);

    return String(value).slice(0, 10);
}
