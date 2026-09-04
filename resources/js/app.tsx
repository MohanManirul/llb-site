import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import type { ComponentType } from 'react';
import { SITE_NAME } from '@/config/site';

createInertiaApp({
    title: (title) => (title ? `${title} | ${SITE_NAME}` : SITE_NAME),

    resolve: (name) => {
        const pages = import.meta.glob<{ default: ComponentType }>('./pages/**/page.tsx', { eager: true });
        const page = pages[`./pages/${name}.tsx`];

        if (!page) {
            throw new Error(`No page found for Inertia::render('${name}')`);
        }

        return page;
    },

    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
});
