import { useEffect, useLayoutEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { ChevronDownIcon } from '@heroicons/react/20/solid';
import {
    businessStatusBadgeStyle,
    type BusinessStatusOption,
} from '@/config/businessStatus';

const VIEWPORT_MARGIN = 8;
const MENU_WIDTH = 176;

interface BusinessStatusSelectProps {
    value: number | string | null | undefined;
    label?: string | null;
    options?: BusinessStatusOption[];
    editable?: boolean;
    saving?: boolean;
    onChange?: (value: number | string, label: string) => void;
}

interface Coords {
    top: number;
    left: number;
}

export default function BusinessStatusSelect({
    value,
    label,
    options = [],
    editable = true,
    saving = false,
    onChange,
}: BusinessStatusSelectProps) {
    const [open, setOpen] = useState(false);
    const [coords, setCoords] = useState<Coords | null>(null);
    const buttonRef = useRef<HTMLButtonElement>(null);
    const menuRef = useRef<HTMLDivElement>(null);
    const badgeStyle = businessStatusBadgeStyle(value == null ? null : String(value));

    useLayoutEffect(() => {
        if (!open || !buttonRef.current || !menuRef.current) return;

        const button = buttonRef.current.getBoundingClientRect();
        const menu = menuRef.current.getBoundingClientRect();

        let top = button.bottom + 4;
        if (top + menu.height > window.innerHeight - VIEWPORT_MARGIN) {
            top = Math.max(VIEWPORT_MARGIN, button.top - menu.height - 4);
        }

        let left = Math.min(
            button.left,
            window.innerWidth - menu.width - VIEWPORT_MARGIN,
        );
        left = Math.max(VIEWPORT_MARGIN, left);

        setCoords({ top, left });
    }, [open]);

    useEffect(() => {
        if (!open) return undefined;

        function onMouseDown(event: MouseEvent) {
            if (
                buttonRef.current?.contains(event.target as Node) ||
                menuRef.current?.contains(event.target as Node)
            ) {
                return;
            }
            setOpen(false);
        }

        function onKeydown(event: KeyboardEvent) {
            if (event.key === 'Escape') {
                setOpen(false);
                buttonRef.current?.focus();
            }
        }

        function onScroll(event: Event) {
            if (menuRef.current?.contains(event.target as Node)) return;
            setOpen(false);
        }

        document.addEventListener('mousedown', onMouseDown);
        document.addEventListener('keydown', onKeydown);
        window.addEventListener('scroll', onScroll, true);
        window.addEventListener('resize', onScroll);

        return () => {
            document.removeEventListener('mousedown', onMouseDown);
            document.removeEventListener('keydown', onKeydown);
            window.removeEventListener('scroll', onScroll, true);
            window.removeEventListener('resize', onScroll);
        };
    }, [open]);

    if (!editable || options.length === 0) {
        return (
            <span
                style={badgeStyle}
                className="inline-flex rounded-full border px-2 py-1 text-xs font-medium"
            >
                {label ?? '—'}
            </span>
        );
    }

    function pick(event: React.MouseEvent, option: BusinessStatusOption) {
        event.stopPropagation();
        setOpen(false);

        if (String(option.value) !== String(value)) {
            onChange?.(option.value, option.label);
        }
    }

    return (
        <div
            className="inline-block text-left"
            onClick={(e) => e.stopPropagation()}
        >
            <button
                ref={buttonRef}
                type="button"
                disabled={saving}
                onClick={() => setOpen((v) => !v)}
                aria-haspopup="true"
                aria-expanded={open}
                style={badgeStyle}
                className="inline-flex items-center gap-1 rounded-full border px-2 py-1 text-xs font-medium transition hover:opacity-80 disabled:opacity-50"
            >
                {label ?? '—'}
                <ChevronDownIcon className="h-3.5 w-3.5" />
            </button>

            {open &&
                createPortal(
                    <div
                        ref={menuRef}
                        role="menu"
                        style={{
                            position: 'fixed',
                            top: coords?.top ?? -9999,
                            left: coords?.left ?? -9999,
                            width: MENU_WIDTH,
                            visibility: coords ? 'visible' : 'hidden',
                            zIndex: 60,
                        }}
                        className="max-h-64 overflow-y-auto rounded-md border border-gray-200 bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5"
                    >
                        {options.map((option) => (
                            <button
                                key={option.value}
                                type="button"
                                onClick={(e) => pick(e, option)}
                                className={
                                    'flex w-full items-center gap-2 px-4 py-2 text-left text-sm hover:bg-gray-100 ' +
                                    (String(option.value) === String(value)
                                        ? 'font-semibold text-gray-900'
                                        : 'text-gray-700')
                                }
                            >
                                <span
                                    aria-hidden="true"
                                    style={{ backgroundColor: option.color }}
                                    className="h-2 w-2 shrink-0 rounded-full"
                                />
                                {option.label}
                            </button>
                        ))}
                    </div>,
                    document.body,
                )}
        </div>
    );
}
