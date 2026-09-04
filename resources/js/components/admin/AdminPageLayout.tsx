import type { ReactNode } from 'react';
import { usePage } from '@inertiajs/react';
import PageHeader from '../common/PageHeader';
import { settingsTitle } from '@/config/sidebarNav';

interface AdminPageLayoutProps {
    title?: string;
    action?: ReactNode;
    children?: ReactNode;
}

export default function AdminPageLayout({ title, action, children }: AdminPageLayoutProps) {
    const page = usePage();
    const base = page.props.portal?.base ?? '/admin';
    const path = page.url.split('?')[0];

    return (
        <>
            <PageHeader
                title={title ?? settingsTitle(path, base) ?? 'Settings'}
                action={action}
            />

            <div className="rounded-xl border border-gray-200 bg-white shadow-sm">
                {children}
            </div>
        </>
    );
}
