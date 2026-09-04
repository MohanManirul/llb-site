import { router, usePage } from '@inertiajs/react';
import { ExclamationTriangleIcon } from '@heroicons/react/24/outline';
import { Button } from '@/components/ui';

export default function ImpersonationBanner() {
    const { impersonation, portal } = usePage().props;
    const base = portal?.base ?? '/admin';

    if (!impersonation) {
        return null;
    }

    return (
        <div className="flex w-full shrink-0 flex-wrap items-center gap-3 border-b border-amber-300 bg-amber-100 px-4 py-2">
            <ExclamationTriangleIcon className="h-5 w-5 shrink-0 text-amber-600" />

            <p className="min-w-0 text-sm text-amber-900">
                Signed in as{' '}
                <span className="font-semibold">{impersonation.name}</span>
            </p>

            <div className="ml-auto">
                <Button
                    variant="secondary"
                    size="sm"
                    onClick={() => router.post(`${base}/impersonate/stop`)}
                >
                    Return to my account
                </Button>
            </div>
        </div>
    );
}
