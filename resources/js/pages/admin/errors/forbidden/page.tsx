import { Head, Link, router, usePage } from '@inertiajs/react';
import { LockClosedIcon } from '@heroicons/react/24/outline';

export default function Forbidden() {
    const { portal, impersonation } = usePage().props;
    const base = portal?.base ?? '/admin';

    return (
        <>
            <Head title="Access denied" />

            <div className="flex min-h-screen flex-col items-center justify-center bg-gray-100 px-4 text-center">
                <div className="flex h-20 w-20 items-center justify-center rounded-full bg-red-50">
                    <LockClosedIcon className="h-10 w-10 text-red-600" />
                </div>

                <p className="mt-8 text-6xl font-extrabold tracking-tight text-brand">
                    403
                </p>
                <h1 className="mt-4 text-xl font-semibold text-gray-800">
                    Access denied
                </h1>
                <p className="mt-2 max-w-md text-sm text-gray-500">
                    You don’t have permission to open this page. Ask an
                    administrator if you think you should.
                </p>

                <Link
                    href={`${base}/dashboard`}
                    className="mt-8 inline-flex items-center justify-center rounded-md bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-500 focus:outline-none focus:ring focus:ring-blue-200"
                >
                    Back to dashboard
                </Link>

                {impersonation && (
                    <button
                        type="button"
                        onClick={() => router.post(`${base}/impersonate/stop`)}
                        className="mt-3 inline-flex items-center justify-center rounded-md border border-amber-300 bg-white px-5 py-2.5 text-sm font-semibold text-amber-700 transition hover:bg-amber-50 focus:outline-none focus:ring focus:ring-amber-200"
                    >
                        Return to my account
                    </button>
                )}
            </div>
        </>
    );
}
