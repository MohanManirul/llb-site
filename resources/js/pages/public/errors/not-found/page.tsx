import { Head, Link } from '@inertiajs/react';
import { MagnifyingGlassIcon } from '@heroicons/react/24/outline';

export default function PublicNotFound() {
    return (
        <>
            <Head title="পাতাটি পাওয়া যায়নি" />

            <div className="flex min-h-dvh flex-col items-center justify-center bg-canvas px-4 text-center font-bangla">
                <div className="flex h-20 w-20 items-center justify-center rounded-full bg-brand/5">
                    <MagnifyingGlassIcon className="h-10 w-10 text-brand" />
                </div>

                <p className="mt-8 text-6xl font-extrabold tracking-tight text-brand">
                    ৪০৪
                </p>

                <h1 className="mt-4 text-xl font-semibold text-ink">
                    পাতাটি পাওয়া যায়নি
                </h1>

                <p className="mt-2 max-w-md text-sm text-ink-muted">
                    আপনি যে পাতাটি খুঁজছেন সেটি নেই, অথবা সরিয়ে ফেলা হয়েছে।
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
