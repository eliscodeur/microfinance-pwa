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

// 1. On récupère l'élément cible
const el = document.getElementById('app');

// 2. On ne lance Inertia que si l'élément existe sur la page actuelle
if (el) {
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
} else {
    // Optionnel : un petit message pour confirmer que le JS tourne sans erreur
    console.log("Mode Blade classique détecté : Inertia est en attente.");
}