const CACHE_NAME = 'tontine-cache-v10'; // On monte la version

const ASSETS_TO_CACHE = [
    '/agent/login',
    '/pwa/dashboard',
    '/pwa/clients',
    '/pwa/carnet',
    '/pwa/pointage-shell',
    '/pwa/sync',
    // Bibliothèques  
    '/js/dexie.js',
    '/js/db-manager.js',
    '/js/bootstrap.bundle.min.js',
    '/css/bootstrap.min.css',
    '/css/bootstrap-icons.css', 
    '/fonts/bootstrap-icons.woff',
    '/fonts/bootstrap-icons.woff2',
    '/images/default-avatar.png'
];

// 1. INSTALLATION
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            return Promise.all(
                ASSETS_TO_CACHE.map(url => {
                    return fetch(url).then(response => {
                        if (response.ok) return cache.put(url, response);
                    }).catch(() => console.warn("Échec pré-mise en cache : " + url));
                })
            );
        })
    );
});

// 2. ACTIVATION (Nettoyage des anciens caches)
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys => {
            return Promise.all(
                keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key))
            );
        }).then(() => self.clients.claim())
    );
});

// 3. STRATÉGIE DE FETCH AMÉLIORÉE


self.addEventListener('fetch', event => {
    if (event.request.method !== 'GET') return;

    const url = new URL(event.request.url);
    
    // Ignorer les routes critiques/API de Laravel
    if (url.pathname.includes('/api/')) return;

    event.respondWith(
        (async () => {
            try {
                // 1. Chercher dans le cache d'abord (CSS, JS, Dexie, Images déjà vues)
                const cachedResponse = await caches.match(event.request);
                
                // On sert le cache immédiatement pour les fichiers statiques (pas pour la navigation)
                if (cachedResponse && event.request.mode !== 'navigate') {
                    return cachedResponse;
                }

                // 2. Tenter le réseau
                const networkResponse = await fetch(event.request);

                // --- GESTION DES REDIRECTIONS (Auth Laravel) ---
                if (networkResponse.redirected) {
                    return networkResponse;
                }

                // 3. MISE EN CACHE DYNAMIQUE (C'est ici qu'on gère le /storage/)
                if (networkResponse && networkResponse.ok && networkResponse.status === 200) {
                    const cache = await caches.open(CACHE_NAME);
                    // On clone la réponse pour la mettre en cache
                    cache.put(event.request, networkResponse.clone());
                }

                return networkResponse;

            } catch (error) {
                // 4. FALLBACK OFFLINE (Si le réseau échoue totalement)
                const cachedFallback = await caches.match(event.request);
                if (cachedFallback) return cachedFallback;

                // --- GESTION SPÉCIFIQUE DES PHOTOS EN OFFLINE ---
                // Si l'URL contient /storage/ ou si c'est une image, on donne l'avatar par défaut
                if (url.pathname.includes('/storage/') || event.request.destination === 'image') {
                    return caches.match('/images/default-avatar.png');
                }

                // Si c'est la page elle-même qui échoue
                if (event.request.mode === 'navigate') {
                    return caches.match('/pwa/dashboard');
                }

                // Pour le reste (CSS/JS manquants), réponse vide propre pour éviter de casser le JS
                return new Response('', { status: 408, statusText: 'Network Error' });
            }
        })()
    );
});