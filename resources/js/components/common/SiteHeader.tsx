import { useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import {
    Bars3BottomLeftIcon,
    UserCircleIcon,
    ArrowRightStartOnRectangleIcon,
    ArrowUturnLeftIcon,
} from '@heroicons/react/24/outline';
import NotificationBell from './NotificationBell';

interface SiteHeaderProps {
    onToggleDrawer?: () => void;
}

export default function SiteHeader({
    onToggleDrawer,
}: SiteHeaderProps) {
    const { auth, portal, impersonation } = usePage().props;
    const user = auth?.user;
    const base = portal?.base ?? '/admin';

    const [userMenuOpen, setUserMenuOpen] = useState(false);

    function logout() {
        router.post(`${base}/logout`);
    }

    function stopImpersonating() {
        router.post(`${base}/impersonate/stop`);
    }

    return (
        <header className="w-full shrink-0 bg-shell px-4">
            <div className="flex h-16 items-center gap-3 lg:gap-5">

                <button
                    onClick={onToggleDrawer}
                    aria-label="Toggle sidebar"
                    className="flex h-10 w-10 items-center justify-center rounded-md hover:bg-white/10 lg:hidden"
                >
                    <Bars3BottomLeftIcon className="h-6 w-6 text-white/80" />
                </button>

                <Link href={`${base}/dashboard`} className="flex shrink-0 items-center">
                    <img
                        src="/assets/logos/boneek.png"
                        alt="Boneek"
                        className="h-auto w-32 object-contain brightness-0 invert md:w-40"
                    />
                </Link>

                <div className="ml-auto flex items-center gap-2 md:gap-3">
                    <NotificationBell />

                    <div className="relative">
                        <button
                            onClick={() => setUserMenuOpen((v) => !v)}
                            className="flex h-10 w-10 items-center justify-center rounded-full border border-white/15 bg-white/10 hover:bg-white/15"
                        >
                            <UserCircleIcon className="h-6 w-6 text-white/85" />
                        </button>

                        {userMenuOpen && (
                            <>
                                <div
                                    className="fixed inset-0 z-10"
                                    onClick={() => setUserMenuOpen(false)}
                                />
                                <div className="absolute right-0 z-20 mt-2 w-48 rounded-md border border-gray-100 bg-white py-1 shadow-lg">
                                    <div className="border-b border-gray-100 px-4 py-2">
                                        <p className="truncate text-sm font-medium text-gray-800">
                                            {user?.name ?? 'Guest'}
                                        </p>
                                        <p className="truncate text-xs text-gray-500">
                                            {user?.email}
                                        </p>
                                    </div>
                                    <Link
                                        href={`${base}/profile`}
                                        onClick={() => setUserMenuOpen(false)}
                                        className="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50"
                                    >
                                        <UserCircleIcon className="h-4 w-4" />
                                        Profile
                                    </Link>
                                    {impersonation ? (
                                        <button
                                            onClick={stopImpersonating}
                                            className="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-amber-700 hover:bg-gray-50"
                                        >
                                            <ArrowUturnLeftIcon className="h-4 w-4" />
                                            Return to my account
                                        </button>
                                    ) : (
                                        <button
                                            onClick={logout}
                                            className="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-red-600 hover:bg-gray-50"
                                        >
                                            <ArrowRightStartOnRectangleIcon className="h-4 w-4" />
                                            Logout
                                        </button>
                                    )}
                                </div>
                            </>
                        )}
                    </div>
                </div>
            </div>
        </header>
    );
}
