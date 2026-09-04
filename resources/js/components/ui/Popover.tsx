import { ReactNode, useEffect, useState } from 'react';
import Button from './Button';
import { overlayClasses, popoverPanelClasses } from './tokens';

export interface PopoverProps {
    label: ReactNode;
    icon?: ReactNode;
    badge?: ReactNode;
    align?: 'left' | 'right';
    panelClassName?: string;
    disabled?: boolean;
    children?: ReactNode | ((close: () => void) => ReactNode);
}

export default function Popover({
    label,
    icon,
    badge,
    align = 'left',
    panelClassName = '',
    disabled = false,
    children,
}: PopoverProps) {
    const [open, setOpen] = useState(false);

    useEffect(() => {
        if (!open) return;

        const onKeydown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') setOpen(false);
        };

        document.addEventListener('keydown', onKeydown);
        return () => document.removeEventListener('keydown', onKeydown);
    }, [open]);

    return (
        <div className="relative inline-block text-left">
            <Button
                variant="secondary"
                onClick={() => setOpen((current) => !current)}
                disabled={disabled}
                aria-haspopup="true"
                aria-expanded={open}
            >
                {label}
                {badge}
                {icon}
            </Button>

            {open && (
                <>
                    <div
                        className={overlayClasses}
                        onClick={() => setOpen(false)}
                    />

                    <div
                        className={`${popoverPanelClasses} ${
                            align === 'right'
                                ? 'right-0 origin-top-right'
                                : 'left-0 origin-top-left'
                        } ${panelClassName}`}
                    >
                        {typeof children === 'function'
                            ? children(() => setOpen(false))
                            : children}
                    </div>
                </>
            )}
        </div>
    );
}
