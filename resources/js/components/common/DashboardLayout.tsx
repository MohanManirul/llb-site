import { useState, type ReactNode } from 'react';
import Sidebar from './Sidebar';
import SiteHeader from './SiteHeader';
import ImpersonationBanner from './ImpersonationBanner';
import { PageTitleProvider } from './PageTitle';
import FlashMessages from './FlashMessages';

interface DashboardLayoutProps {
    children?: ReactNode;
    wide?: boolean;
}

export default function DashboardLayout({ children, wide = false }: DashboardLayoutProps) {
    const [sidebarOpen, setSidebarOpen] = useState(false);

    return (
        <PageTitleProvider>
            <div className="flex h-screen flex-col bg-canvas">
                <ImpersonationBanner />

                <SiteHeader
                    onToggleDrawer={() => setSidebarOpen((v) => !v)}
                />

                <div className="flex min-h-0 flex-1 overflow-hidden bg-canvas">
                    <div className="flex min-h-0 flex-1 overflow-hidden rounded-t-2xl">
                        <Sidebar
                            open={sidebarOpen}
                            onClose={() => setSidebarOpen(false)}
                        />

                        <main className="min-w-0 flex-1 overflow-y-auto bg-canvas p-3 md:px-6 md:py-4">
                            <div
                                className={
                                    'mx-auto w-full ' + (wide ? '' : 'max-w-300')
                                }
                            >
                                {children}
                            </div>
                        </main>
                    </div>
                </div>
            </div>

            <FlashMessages />
        </PageTitleProvider>
    );
}
