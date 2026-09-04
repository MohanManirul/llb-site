import { Head, Link } from '@inertiajs/react';
import { LockClosedIcon } from '@heroicons/react/24/outline';

export default function PublicForbidden() {
    return (
        <>
            <Head title="প্রবেশাধিকার নেই" />

            <div className="flex min-h-dvh flex-col items-center justify-center bg-canvas px-4 text-center font-bangla">
                <div className="flex h-20 w-20 items-center justify-center rounded-full bg-brand/5">
                    <LockClosedIcon className="h-10 w-10 text-brand" />
                </div>

                <p className="mt-8 text-6xl font-extrabold tracking-tight text-brand">
                    ৪০৩
                </p>

                <h1 className="mt-4 text-xl font-semibold text-ink">
                    প্রবেশাধিকার নেই
                </h1>

                <p className="mt-2 max-w-md text-sm text-ink-muted">
                    এই পাতাটি দেখার অনুমতি আপনার নেই।
                </p>

                <Link
                    href="/"
                    className="mt-8 inline-flex items-center justify-center rounded-control bg-brand px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-accent focus:outline-none focus:ring focus:ring-brand-muted"
                >
                    হোমে ফিরে যান
                </Link>
            </div>
        </>
    );
}
