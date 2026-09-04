import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import {
    CheckCircleIcon,
    XCircleIcon,
    XMarkIcon,
} from '@heroicons/react/24/outline';
import { flash as flashBus, type FlashToast, type FlashType } from '../../lib/flash';

const typeStyles: Record<FlashType, { Icon: typeof CheckCircleIcon; color: string }> = {
    success: { Icon: CheckCircleIcon, color: 'text-green-500' },
    error: { Icon: XCircleIcon, color: 'text-red-500' },
};

export default function FlashMessages() {
    const { flash } = usePage().props;

    const [toast, setToast] = useState<FlashToast | null>(null);

    const serverType: FlashType | null = flash?.success ? 'success' : flash?.error ? 'error' : null;
    const serverMessage = serverType ? flash[serverType] : null;

    useEffect(() => {
        if (serverMessage && serverType) {
            setToast({ type: serverType, message: serverMessage });
        }
    }, [serverMessage, serverType]);

    useEffect(() => flashBus.subscribe(setToast), []);

    useEffect(() => {
        if (!toast) return;
        const timer = setTimeout(() => setToast(null), 5000);
        return () => clearTimeout(timer);
    }, [toast]);

    if (!toast) {
        return null;
    }

    const { message } = toast;
    const { Icon, color } = typeStyles[toast.type];

    return (
        <div className="fixed bottom-10 right-10 z-50">
            <div className="group relative flex animate-bounce items-center gap-3 rounded-xl border-2 border-gray-100 bg-white px-6 py-4 shadow-xl transition hover:shadow-2xl">
                <Icon className={'h-7 w-7 ' + color} />
                <p className="text-sm font-medium text-gray-800">{message}</p>
                <button
                    onClick={() => setToast(null)}
                    className="absolute right-1 top-1 opacity-0 transition group-hover:opacity-100"
                    aria-label="Dismiss"
                >
                    <XMarkIcon className="h-6 w-6 rounded-full border bg-gray-200 p-1" />
                </button>
            </div>
        </div>
    );
}
