import { Head, Link, usePage } from '@inertiajs/react';
import { MagnifyingGlassIcon } from '@heroicons/react/24/outline';

export default function NotFound() {

    const { auth } = usePage().props;

    const homeHref = '/dashboard' ;

    return (
        <>
            <Head title="Page not found" />

            <div className="flex min-h-screen flex-col items-center justify-center bg-gray-100 px-4 text-center">
                <div className="flex h-20 w-20 items-center justify-center rounded-full bg-blue-50">
                    <MagnifyingGlassIcon className="h-10 w-10 text-blue-600" />
                </div>

                <p className="mt-8 text-6xl font-extrabold tracking-tight text-brand">
                    404
                </p>
                <h1 className="mt-4 text-xl font-semibold text-gray-800">
                    Page not found
                </h1>
                <p className="mt-2 max-w-md text-sm text-gray-500">
                    The page you’re looking for doesn’t exist or may have been moved.
                </p>

                <Link
                    href={homeHref}
                    className="mt-8 inline-flex items-center justify-center rounded-md bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-500 focus:outline-none focus:ring focus:ring-blue-200"
                >
                    Back to {auth?.user ? 'dashboard' : 'home'}
                </Link>
            </div>
        </>
    );
}
