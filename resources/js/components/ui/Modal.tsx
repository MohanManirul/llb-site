import { ReactNode, useCallback, useEffect, useRef, useState } from 'react';

/*
 * Modal — reusable dialog, converted from the inventory Jetstream-style
 * <x-modal>: a dimmed backdrop with a white rounded panel centred over it.
 * Closes on backdrop click or the Escape key, and locks page scroll while open.
 *
 * Opens and closes with a fade + scale transition — the panel stays mounted for
 * the length of the leave transition so closing doesn't snap shut.
 *
 * Focus moves into the panel when it opens, stays trapped inside while it is
 * open, and returns to whatever opened the modal once it closes.
 *
 * Props:
 *   show        — whether the modal is visible
 *   onClose     — called on backdrop click / Escape
 *   maxWidth    — sm | md | lg | xl | 2xl | 3xl | 4xl (default 2xl)
 *   label       — accessible name, when the panel has no visible heading to point at
 *   labelledBy  — id of the heading that names the dialog
 *   closeOnBackdrop — set false for flows that must not be dismissed by a stray click
 *   children    — the panel content
 */
const TRANSITION_MS = 250;

const FOCUSABLE_SELECTOR =
    'a[href], button:not([disabled]), textarea:not([disabled]), ' +
    'input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';

let scrollLockCount = 0;

function lockBodyScroll() {
    scrollLockCount += 1;
    if (scrollLockCount === 1) {
        document.body.classList.add('overflow-y-hidden');
    }
}

function releaseBodyScroll() {
    scrollLockCount = Math.max(0, scrollLockCount - 1);
    if (scrollLockCount === 0) {
        document.body.classList.remove('overflow-y-hidden');
    }
}

const maxWidthClasses = {
    sm: 'sm:max-w-sm',
    md: 'sm:max-w-md',
    lg: 'sm:max-w-lg',
    xl: 'sm:max-w-xl',
    '2xl': 'sm:max-w-2xl',
    '3xl': 'sm:max-w-3xl',
    '4xl': 'sm:max-w-4xl',
};

export interface ModalProps {
    show?: boolean;
    onClose?: () => void;
    maxWidth?: keyof typeof maxWidthClasses;
    label?: string;
    labelledBy?: string;
    closeOnBackdrop?: boolean;
    children?: ReactNode;
}

export default function Modal({
    show = false,
    onClose,
    maxWidth = '2xl',
    label,
    labelledBy,
    closeOnBackdrop = true,
    children,
}: ModalProps) {
    const [mounted, setMounted] = useState(show);
    const [entered, setEntered] = useState(false);
    const panelRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (show) {
            setMounted(true);
            const frame = requestAnimationFrame(() => setEntered(true));
            return () => cancelAnimationFrame(frame);
        }

        setEntered(false);
        const timer = setTimeout(() => setMounted(false), TRANSITION_MS);
        return () => clearTimeout(timer);
    }, [show]);

    const focusableNodes = useCallback((): HTMLElement[] => {
        if (!panelRef.current) return [];

        return Array.from(
            panelRef.current.querySelectorAll<HTMLElement>(FOCUSABLE_SELECTOR),
        ).filter((node) => node.offsetParent !== null || node === document.activeElement);
    }, []);

    useEffect(() => {
        if (!mounted) return;

        const previouslyFocused = document.activeElement as HTMLElement | null;

        lockBodyScroll();

        const frame = requestAnimationFrame(() => {
            const nodes = focusableNodes();
            (nodes[0] ?? panelRef.current)?.focus();
        });

        return () => {
            cancelAnimationFrame(frame);
            releaseBodyScroll();
            previouslyFocused?.focus?.();
        };
    }, [mounted, focusableNodes]);

    useEffect(() => {
        if (!mounted) return;

        const onKeydown = (e: KeyboardEvent) => {
            if (e.key === 'Escape') {
                onClose?.();
                return;
            }

            if (e.key !== 'Tab' || !panelRef.current) return;

            const nodes = focusableNodes();

            if (nodes.length === 0) {
                e.preventDefault();
                panelRef.current.focus();
                return;
            }

            const first = nodes[0];
            const last = nodes[nodes.length - 1];
            const active = document.activeElement;

            if (!panelRef.current.contains(active)) {
                e.preventDefault();
                first.focus();
                return;
            }

            if (e.shiftKey && active === first) {
                e.preventDefault();
                last.focus();
            } else if (!e.shiftKey && active === last) {
                e.preventDefault();
                first.focus();
            }
        };

        document.addEventListener('keydown', onKeydown);

        return () => document.removeEventListener('keydown', onKeydown);
    }, [mounted, onClose, focusableNodes]);

    if (!mounted) return null;

    return (
        <div className="fixed inset-0 z-50 overflow-y-auto">
            <div
                aria-hidden="true"
                className={
                    'fixed inset-0 bg-gray-500/75 transition-opacity duration-250 ease-out motion-reduce:transition-none ' +
                    (entered ? 'opacity-100' : 'opacity-0')
                }
                onClick={closeOnBackdrop ? onClose : undefined}
            />

            <div className="flex min-h-full items-center justify-center px-4 py-6 sm:px-0">
                <div
                    ref={panelRef}
                    role="dialog"
                    aria-modal="true"
                    aria-label={labelledBy ? undefined : label}
                    aria-labelledby={labelledBy}
                    tabIndex={-1}
                    className={
                        'relative w-full transform overflow-hidden rounded-lg bg-white shadow-xl transition-all duration-250 ease-out focus:outline-none motion-reduce:transition-none sm:mx-auto ' +
                        (entered
                            ? 'translate-y-0 scale-100 opacity-100'
                            : 'translate-y-4 scale-95 opacity-0') +
                        ' ' +
                        (maxWidthClasses[maxWidth] ?? maxWidthClasses['2xl'])
                    }
                >
                    {children}
                </div>
            </div>
        </div>
    );
}
