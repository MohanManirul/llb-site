import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import type { ComponentType } from 'react';
import { documentTitleBase, setDocumentTitleName } from '@/config/site';

createInertiaApp({
    title: (title) => (title ? `${title} | ${documentTitleBase()}` : documentTitleBase()),

    resolve: (name) => {
        const pages = import.meta.glob<{ default: ComponentType }>('./pages/**/page.tsx', { eager: true });
        const page = pages[`./pages/${name}.tsx`];

        if (!page) {
            throw new Error(`No page found for Inertia::render('${name}')`);
        }

        return page;
    },

    setup({ el, App, props }) {
        const { locale, site } = props.initialPage.props;

        setDocumentTitleName(locale === 'en' ? (site?.name?.en ?? site?.name?.bn) : (site?.name?.bn ?? site?.name?.en));

        createRoot(el).render(<App {...props} />);
    },
});
