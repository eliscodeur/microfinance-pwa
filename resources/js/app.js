// import './bootstrap';
// import React from 'react';
// import { createRoot } from 'react-dom/client';
// import { createInertiaApp } from '@inertiajs/inertia-react';
// import { InertiaProgress } from '@inertiajs/progress';

// createInertiaApp({
//     resolve: async name => {
//         const page = await import(`./Pages/${name}.jsx`);
//         return page.default;
//     },
//     setup({ el, App, props, plugin }) {
//         const root = createRoot(el);
//         root.render(
//             <React.StrictMode>
//                 <App {...props} />
//             </React.StrictMode>,
//         );
//     },
// });

// InertiaProgress.init();
import './bootstrap';
import React from 'react';
import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/inertia-react';
import { InertiaProgress } from '@inertiajs/progress';

const el = document.getElementById('app');

const parseInertiaPage = () => {
    const pageData = el?.dataset?.page;

    // Vérifier que l'élément existe et que data-page est défini
    if (!el || typeof pageData !== 'string' || pageData.length === 0) {
        return null;
    }

    // Vérifier que data-page n'est pas "undefined" ou null
    if (pageData === 'undefined' || pageData === 'null') {
        return null;
    }

    try {
        const parsed = JSON.parse(pageData);
        // Vérifier que le JSON parsé a au moins une propriété component
        if (!parsed || typeof parsed !== 'object' || !parsed.component) {
            console.warn('Inertia page data is missing component property:', parsed);
            return null;
        }
        return parsed;
    } catch (error) {
        console.warn('Inertia page payload is invalid JSON:', pageData, error);
        return null;
    }
};

const initialPage = parseInertiaPage();

if (el && initialPage) {
    createInertiaApp({
        resolve: async name => {
            const page = await import(`./Pages/${name}.jsx`);
            return page.default;
        },
        page: initialPage,
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
} else {
    // Si on est sur une page Blade classique sans élément #app
    console.log("Mode Blade : Inertia ignoré sur cette page.");
}