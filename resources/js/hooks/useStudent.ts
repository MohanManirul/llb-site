import { useCallback } from 'react';
import { router, usePage } from '@inertiajs/react';
import api from '@/lib/api-client';
import useTranslation from './useTranslation';

function safeRedirect(target: string | null | undefined, fallback: string): string {
    if (!target || !target.startsWith('/') || target.startsWith('//')) return fallback;

    return target;
}

export default function useStudent() {
    const student = usePage().props.student ?? null;
    const { localeHref } = useTranslation();

    const loginHref = useCallback(
        (redirect?: string) =>
            localeHref(`/account/login${redirect ? `?redirect=${encodeURIComponent(redirect)}` : ''}`),
        [localeHref],
    );

    const currentHref = useCallback(() => window.location.pathname + window.location.search, []);

    const redirectAfterAuth = useCallback(() => {
        const requested = new URLSearchParams(window.location.search).get('redirect');

        router.visit(safeRedirect(requested, localeHref('/exam-prep')));
    }, [localeHref]);

    const logout = useCallback(async () => {
        await api.post('/student/auth/logout');
        router.visit(localeHref('/'));
    }, [localeHref]);

    const isUnauthorized = useCallback(
        (error: unknown) => (error as { response?: { status?: number } })?.response?.status === 401,
        [],
    );

    return { student, loginHref, currentHref, redirectAfterAuth, logout, isUnauthorized };
}
