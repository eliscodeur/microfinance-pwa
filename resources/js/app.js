import './bootstrap';
import React from 'react';
import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/inertia-react';
import { InertiaProgress } from '@inertiajs/progress';

createInertiaApp({
    resolve: async name => {
        const page = await import(`./Pages/${name}.jsx`);
        return page.default;
    },
    setup({ el, App, props, plugin }) {
        const root = createRoot(el);
        root.render(
            <React.StrictMode>
                <App {...props} />
            </React.StrictMode>,
        );
    },
});

InertiaProgress.init();
