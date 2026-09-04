import type { ReactNode } from 'react';
import FlashMessages from '@/components/common/FlashMessages';
import usePulse from '@/hooks/usePulse';
import PublicHeader from './PublicHeader';
import PublicFooter from './PublicFooter';

interface PublicLayoutProps {
    children?: ReactNode;
    wide?: boolean;
}

export default function PublicLayout({ children, wide = false }: PublicLayoutProps) {
    usePulse();

    return (
        <div className="flex min-h-dvh flex-col bg-canvas font-bangla">
            <PublicHeader />

            <main className="flex-1">
                <div
                    className={
                        'mx-auto w-full px-4 py-6 md:px-6 md:py-8 ' + (wide ? '' : 'max-w-300')
                    }
                >
                    {children}
                </div>
            </main>

            <PublicFooter />
            <FlashMessages />
        </div>
    );
}
