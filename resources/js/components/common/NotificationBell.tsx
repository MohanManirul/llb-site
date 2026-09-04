import { useCallback, useEffect, useRef, useState } from 'react';
import { router } from '@inertiajs/react';
import { BellIcon, CheckIcon } from '@heroicons/react/24/outline';
import api from '../../lib/api-client';
import { flash } from '../../lib/flash';

const POLL_INTERVAL_MS = 20000;

interface NotificationItem {
    id: string | number;
    title: string;
    message: string;
    summary?: string;
    status?: string;
    kind?: string;
    link?: string;
    read: boolean;
    created_for_humans?: string;
}

export default function NotificationBell() {
    const [open, setOpen] = useState(false);
    const [items, setItems] = useState<NotificationItem[]>([]);
    const [unread, setUnread] = useState(0);
    const mounted = useRef(true);
    const seen = useRef<Set<NotificationItem['id']> | null>(null);

    const announce = useCallback((incoming: NotificationItem[]) => {
        if (seen.current === null) {
            seen.current = new Set(incoming.map((item) => item.id));
            return;
        }

        const fresh = incoming.filter((item) => !seen.current!.has(item.id) && !item.read);

        fresh.forEach((item) => {
            if (item.status === 'failed') {
                flash.error(item.message);
            } else {
                flash.success(item.message);
            }
        });

        incoming.forEach((item) => seen.current!.add(item.id));
    }, []);

    const load = useCallback(async () => {
        try {
            const { data } = await api.get('/notifications', { params: { limit: 15 } });
            if (!mounted.current) return;

            const incoming: NotificationItem[] = data.result.notifications ?? [];

            setItems(incoming);
            setUnread(data.result.unread_count ?? 0);
            announce(incoming);
        } catch (e) {
        }
    }, [announce]);

    useEffect(() => {
        mounted.current = true;
        load();

        const timer = setInterval(load, POLL_INTERVAL_MS);

        return () => {
            mounted.current = false;
            clearInterval(timer);
        };
    }, [load]);

    function toggle() {
        const next = !open;
        setOpen(next);
        if (next) load();
    }

    async function openNotification(item: NotificationItem) {
        setOpen(false);

        if (!item.read) {
            setItems((current) =>
                current.map((row) => (row.id === item.id ? { ...row, read: true } : row))
            );
            setUnread((count) => Math.max(0, count - 1));

            try {
                await api.patch(`/notifications/${item.id}/read`);
            } catch (e) {
                load();
            }
        }

        if (item.link) {
            router.visit(item.link);
        }
    }

    async function markAllAsRead() {
        setItems((current) => current.map((row) => ({ ...row, read: true })));
        setUnread(0);

        try {
            await api.patch('/notifications/read-all');
        } catch (e) {
            load();
        }
    }

    return (
        <div className="relative">
            <button
                type="button"
                onClick={toggle}
                className="relative flex h-10 w-10 items-center justify-center rounded-chip border border-white/15 hover:bg-white/10"
            >
                <BellIcon className="h-5 w-5 text-white/85" />

                {unread > 0 && (
                    <span className="absolute -right-0.5 -top-0.5 flex h-5 min-w-5 items-center justify-center rounded-full border-2 border-shell bg-red-500 px-1 text-[10px] font-semibold leading-none text-white">
                        {unread > 9 ? '9+' : unread}
                    </span>
                )}
            </button>

            {open && (
                <>
                    <div className="fixed inset-0 z-10" onClick={() => setOpen(false)} />

                    <div className="absolute right-0 z-20 mt-2 w-80 rounded-md border border-gray-100 bg-white shadow-lg">
                        <div className="flex items-center justify-between border-b border-gray-100 px-4 py-2">
                            <p className="text-sm font-medium text-gray-800">Notifications</p>
                            {unread > 0 && (
                                <button
                                    type="button"
                                    onClick={markAllAsRead}
                                    className="flex items-center gap-1 text-xs font-medium text-brand-accent hover:text-brand"
                                >
                                    <CheckIcon className="h-3.5 w-3.5" />
                                    Mark all read
                                </button>
                            )}
                        </div>

                        <div className="max-h-96 overflow-y-auto">
                            {items.length === 0 && (
                                <p className="px-4 py-6 text-center text-sm text-gray-500">
                                    No notifications yet.
                                </p>
                            )}

                            {items.map((item) => (
                                <button
                                    key={item.id}
                                    type="button"
                                    onClick={() => openNotification(item)}
                                    className={
                                        'flex w-full flex-col items-start gap-0.5 border-b border-gray-50 px-4 py-3 text-left hover:bg-gray-50 ' +
                                        (item.read ? 'bg-white' : 'bg-brand-accent/5/60')
                                    }
                                >
                                    <div className="flex w-full items-center justify-between gap-2">
                                        <span className="text-sm font-medium text-gray-800">
                                            {item.title}
                                        </span>
                                        {!item.read && (
                                            <span className="h-2 w-2 shrink-0 rounded-full bg-brand-accent" />
                                        )}
                                    </div>
                                    <span
                                        className={
                                            'text-xs ' +
                                            (item.status === 'failed'
                                                ? 'text-red-600'
                                                : 'text-gray-600')
                                        }
                                    >
                                        {item.message}
                                    </span>
                                    {item.summary && (
                                        <span className="text-[11px] text-gray-500">
                                            {item.summary}
                                        </span>
                                    )}
                                    <span className="text-[11px] text-gray-400">
                                        {item.created_for_humans}
                                    </span>
                                </button>
                            ))}
                        </div>
                    </div>
                </>
            )}
        </div>
    );
}
