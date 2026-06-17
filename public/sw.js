const CACHE_NAME = 'tontine-cache-v18';
self.skipWaiting();

// 1. Uniquement le strict minimum public au démarrage
const PUBLIC_ASSETS = [
    '/agent/login',
    '/js/dexie.js',
    '/js/db-manager.js',
    '/js/bootstrap.bundle.min.js',
    '/js/sweetalert2.all.min.js',
    '/js/crypto-js.min.js',
    '/js/apexcharts.min.js',
    '/css/bootstrap.min.css',
    '/css/bootstrap-icons.css',
    '/css/animate.min.css',
    '/fonts/bootstrap-icons.woff',
    '/fonts/bootstrap-icons.woff2',
    '/images/default-avatar.png'
];

// Liste des pages privées à aspirer uniquement APRÈS connexion
const PRIVATE_PAGES = [
    '/pwa/dashboard',
    '/pwa/clients',
    '/pwa/carnet',
    '/pwa/pointage-shell',
    '/pwa/cycles-liste',
    '/pwa/collectes-liste',
    '/pwa/sync',
    '/pwa/security-pin',
    '/pwa/gains',
    '/pwa/stats'
];

const FALLBACK_PAGES = ['/pwa/dashboard', '/agent/login'];

// Fonction ultra-sécurisée pour écrire dans le cache sans faire planter le SW
async function safeCachePut(cache, request, response) {
    try {
        await cache.put(request, response);
    } catch (err) {
        console.warn('Cache.put ignoré proprement pour', request.url || request, err.message);
    }
}

self.addEventListener('install', event => {
    self.skipWaiting();
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => cache.addAll(PUBLIC_ASSETS))
    );
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys => {
            return Promise.all(keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key)));
        }).then(() => self.clients.claim())
    );
});

let isCachingInProgress = false;

self.addEventListener('message', event => {
    if (event.data && event.data.action === 'cachePrivatePages') {
        if (isCachingInProgress) return;
        isCachingInProgress = true;

        event.waitUntil((async () => {
            const cache = await caches.open(CACHE_NAME);
            for (const url of PRIVATE_PAGES) {
                try {
                    const response = await fetch(url, { credentials: 'include' });
                    if (response.ok && !response.url.includes('/agent/login')) {
                        await safeCachePut(cache, url, response.clone());
                    }
                } catch (err) {
                    console.warn('Aspiration privée échouée pour', url, err);
                }
            }
            isCachingInProgress = false;
            console.log('🏁 Aspiration terminée !');
        })());
    }
});

self.addEventListener('fetch', event => {
    // 1. Ignorer les requêtes non-GET et les appels API/Data
    if (event.request.method !== 'GET') return;
    const url = new URL(event.request.url);
    if (url.pathname.includes('/pwa/get-initial-data') || url.pathname.includes('/api/')) {
        return;
    }

    // Fonction pour renvoyer une page de secours HTML
    const respondWithFallbackHTML = async () => {
        for (const fallback of FALLBACK_PAGES) {
            const fallbackResponse = await caches.match(fallback);
            if (fallbackResponse) {
                return fallbackResponse;
            }
        }
        return new Response('<h1>Hors ligne</h1><p>Veuillez vous connecter à Internet.</p>', {
            status: 503,
            headers: { 'Content-Type': 'text/html' }
        });
    };

    // 2. GESTION DE LA NAVIGATION (Pages HTML, Vues Blade)
    if (event.request.mode === 'navigate') {
        event.respondWith((async () => {
            const cache = await caches.open(CACHE_NAME);
            const exactCache = await caches.match(event.request);

            const networkPromise = fetch(event.request).then(async networkResponse => {
                if (networkResponse && networkResponse.ok) {
                    await safeCachePut(cache, event.request, networkResponse.clone());
                }
                return networkResponse;
            }).catch(() => null);

            // Si on a l'URL exacte en cache, on la sert tout de suite (ultra rapide)
            if (exactCache) {
                networkPromise.catch(() => {}); // Laisse le réseau mettre à jour en fond
                return exactCache;
            }

            // Sinon on attend le réseau
            const networkResponse = await networkPromise;
            if (networkResponse) {
                return networkResponse;
            }

            // Si hors-ligne et pas de match exact (ex: url avec paramètres ?id=12)
            // On cherche l'App Shell générique dans le cache
            const basePath = PRIVATE_PAGES.includes(url.pathname) ? url.pathname : null;
            if (basePath) {
                const baseCache = await caches.match(basePath);
                if (baseCache) {
                    return baseCache;
                }
            }

            // Ultime secours HTML si rien ne marche
            return respondWithFallbackHTML();
        })());
        return;
    }

    // 3. GESTION DES ASSETS STATIQUES (JS, CSS, Polices, Images)
    // Stratégie : Cache First (Le cache d'abord, réseau ensuite, SANS TIMEOUT)
    event.respondWith((async () => {
        // On cherche d'abord dans le cache
        const cachedResponse = await caches.match(event.request);
        if (cachedResponse) {
            return cachedResponse;
        }

        try {
            // Si l'asset n'est pas en cache, on va sur le réseau (sans timeout brutal)
            const networkResponse = await fetch(event.request);
            
            if (networkResponse && networkResponse.status === 200) {
                const cache = await caches.open(CACHE_NAME);
                await safeCachePut(cache, event.request, networkResponse.clone());
            }
            return networkResponse;
            
        } catch (err) {
            // CRUCIAL : Ne JAMAIS renvoyer une page HTML de fallback pour un fichier CSS ou WOFF2 !
            console.warn(`Réseau indisponible pour l'asset : ${event.request.url}`);
            
            // On renvoie une réponse vide propre pour ne pas corrompre le navigateur
            return new Response('', { status: 404, statusText: 'Offline Asset Not Found' });
        }
    })());
});