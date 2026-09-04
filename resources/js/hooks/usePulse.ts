import { useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import api from '@/lib/api-client';

const PULSE_INTERVAL_MS = 60_000;

export default function usePulse() {
    const pageUrl = usePage().url;

    useEffect(() => {
        let timer: ReturnType<typeof setInterval> | null = null;

        const beat = (withPath: boolean) => {
            if (document.visibilityState !== 'visible') return;

            api.post('/public/pulse', withPath ? { path: pageUrl } : {}).catch(() => {
                // Analytics must never surface an error to a student.
            });
        };

        beat(true);

        timer = setInterval(() => beat(false), PULSE_INTERVAL_MS);

        const onVisible = () => {
            if (document.visibilityState === 'visible') beat(false);
        };

        document.addEventListener('visibilitychange', onVisible);

        return () => {
            if (timer) clearInterval(timer);
            document.removeEventListener('visibilitychange', onVisible);
        };
    }, [pageUrl]);
}
