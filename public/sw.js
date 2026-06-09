const CACHE_NAME = 'tontine-cache-v18'; // On passe en v13
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

// ÉCOUTEUR DE MESSAGES : C'est ici qu'on déclenche l'aspiration des pages privées !
// Variable globale dans votre sw.js (en haut du fichier)
let isCachingInProgress = false;

self.addEventListener('message', event => {
    if (event.data && event.data.action === 'cachePrivatePages') {
        if (isCachingInProgress) return; // Empêche les lancements multiples
        isCachingInProgress = true;
        
        event.waitUntil(
            (async () => {
                const cache = await caches.open(CACHE_NAME);
                for (const url of PRIVATE_PAGES) {
                    try {
                        const response = await fetch(url, { credentials: 'include' });
                        if (response.ok && !response.url.includes('/agent/login')) {
                            // On attend bien la fin de l'écriture
                            await cache.put(url, response.clone());
                        }
                    } catch (err) {
                        console.error("Échec : " + url, err);
                    }
                }
                isCachingInProgress = false;
                console.log("🏁 Aspiration terminée !");
            })()
        );
    }
});

// Stratégie de Fetch : Réseau en priorité, Cache si hors-ligne
// Stratégie de Fetch modifiée pour accepter les paramètres d'URL (comme pour le carnet)


self.addEventListener('fetch', event => {
    if (event.request.method !== 'GET') return;
    const url = new URL(event.request.url);
    if (url.pathname.includes('/pwa/get-initial-data') || url.pathname.includes('/api/')) {
        return; // Le Service Worker ne touche pas à ça, il laisse passer
    }

    // SI C'EST UNE NAVIGATION (chargement de page HTML)
    if (event.request.mode === 'navigate') {
        event.respondWith(
            caches.match(event.request).then(cachedResponse => {
                // On retourne le cache IMMÉDIATEMENT (zéro délai)
                // On laisse le réseau mettre à jour le cache en arrière-plan
                const networkFetch = fetch(event.request).then(networkResponse => {
                    // On vérifie si la réponse est valide avant de la cloner
                    if (networkResponse && networkResponse.ok) {
                        // On clone ici : le .clone() permet d'avoir deux flux identiques
                        const responseToCache = networkResponse.clone();
                        
                        caches.open(CACHE_NAME).then(cache => {
                            cache.put(event.request, responseToCache);
                        });
                    }
                    
                    // On renvoie l'originale au navigateur
                    return networkResponse;
                }).catch(() => {
                    // Si le fetch échoue, on renvoie null ou une réponse vide
                    return null; 
                });

                return cachedResponse || networkFetch;
            })
        );
        return;
    }

    // POUR LE RESTE (JS, CSS, Images, etc.) : Votre logique de course (Race) reste valable
    event.respondWith(
        Promise.race([
            fetch(event.request, { signal: AbortSignal.timeout(1500) }),
            new Promise((_, reject) => setTimeout(() => reject(new Error('Timeout')), 1500))
        ])
        .then(networkResponse => {
            if (networkResponse && networkResponse.status === 200) {
                const cacheCopy = networkResponse.clone();
                caches.open(CACHE_NAME).then(cache => cache.put(event.request, cacheCopy));
            }
            return networkResponse;
        })
        .catch(() => caches.match(event.request) || caches.match('/agent/login'))
    );
});