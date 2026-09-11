import type { ReactNode } from 'react';
import PublicLayout from './PublicLayout';
import PortalNav from './PortalNav';
import type { PortalNavItem } from '@/config/portalNav';

interface PortalLayoutProps {
    items: PortalNavItem[];
    heading?: string | null;
    subheading?: string | null;
    children?: ReactNode;
}

export default function PortalLayout({
    items,
    heading,
    subheading,
    children,
}: PortalLayoutProps) {
    return (
        <PublicLayout wide>
            <div className="mx-auto w-full max-w-300">
                {(heading || subheading) && (
                    <div className="mb-5">
                        {subheading && (
                            <p className="text-xs font-medium tracking-wide text-ink-muted uppercase">
                                {subheading}
                            </p>
                        )}
                        {heading && (
                            <h1 className="mt-0.5 text-xl font-semibold text-ink">{heading}</h1>
                        )}
                    </div>
                )}

                <div className="grid gap-6 lg:grid-cols-[15rem_1fr]">
                    <PortalNav items={items} />
                    <div className="min-w-0">{children}</div>
                </div>
            </div>
        </PublicLayout>
    );
}
