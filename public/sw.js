const CACHE_NAME = 'tontine-cache-v14'; // On passe en v13
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

// Installation des assets publics
self.addEventListener('install', event => {
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

// ÉCOUTEUR DE MESSAGES : C'est ici qu'on déclenche l'aspiration des pages privées !
self.addEventListener('message', event => {
    if (event.data && event.data.action === 'cachePrivatePages') {
        console.log("Session validée ! Aspiration des pages privées en cours...");
        event.waitUntil(
            caches.open(CACHE_NAME).then(cache => {
                // On force fetch à envoyer les cookies de session Laravel avec 'include'
                return Promise.all(
                    PRIVATE_PAGES.map(url => {
                        return fetch(url, { credentials: 'include' }).then(response => {
                            if (response.ok && response.status === 200) {
                                return cache.put(url, response);
                            }
                        }).catch(err => console.error("Erreur mise en cache privée : " + url, err));
                    })
                ).then(() => console.log("Toutes les pages privées sont sécurisées dans le cache !"));
            })
        );
    }
});

// Stratégie de Fetch : Réseau en priorité, Cache si hors-ligne
// Stratégie de Fetch modifiée pour accepter les paramètres d'URL (comme pour le carnet)

self.addEventListener('fetch', event => {
    if (event.request.method !== 'GET') return;
    const url = new URL(event.request.url);
    if (url.pathname.includes('/api/')) return;

    event.respondWith(
        fetch(event.request)
            .then(networkResponse => {
                if (networkResponse && networkResponse.status === 200) {
                    const cacheCopy = networkResponse.clone();
                    caches.open(CACHE_NAME).then(cache => cache.put(event.request, cacheCopy));
                }
                return networkResponse;
            })
            .catch(() => {
                // MODE HORS-LIGNE COMPLET
                
                // ÉTAPE CRUCIALE : On cherche d'abord une correspondance exacte
                return caches.match(event.request).then(cachedResponse => {
                    if (cachedResponse) return cachedResponse;

                    // Si pas de correspondance exacte (ex: /pwa/carnet?id=X), 
                    // on cherche en ignorant les paramètres après le '?'
                    if (url.pathname.startsWith('/pwa/')) {
                        return caches.match(url.pathname, { ignoreSearch: true }).then(shellResponse => {
                            if (shellResponse) return shellResponse;
                            
                            // Si vraiment rien, redirection de secours vers le dashboard ou login
                            return caches.match('/pwa/dashboard');
                        });
                    }

                    // Fallback pour les images
                    if (url.pathname.includes('/storage/') || event.request.destination === 'image') {
                        return caches.match('/images/default-avatar.png');
                    }
                    
                    if (event.request.mode === 'navigate') {
                        return caches.match('/agent/login');
                    }
                    
                    return new Response('', { status: 408, statusText: 'Network Error' });
                });
            })
    );
});