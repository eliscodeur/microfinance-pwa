const CACHE_NAME = "tontine-cache-v18";
self.skipWaiting();

// 1. Uniquement le strict minimum public au démarrage
const PUBLIC_ASSETS = [
    "/agent/login",
    "/js/dexie.js",
    "/js/db-manager.js",
    "/js/bootstrap.bundle.min.js",
    "/js/sweetalert2.all.min.js",
    "/js/crypto-js.min.js",
    "/js/apexcharts.min.js",
    "/css/bootstrap.min.css",
    "/css/bootstrap-icons.css",
    "/css/animate.min.css",
    "/fonts/bootstrap-icons.woff",
    "/fonts/bootstrap-icons.woff2",
    "/images/default-avatar.png",
];

// Liste des pages privées à aspirer uniquement APRÈS connexion
const PRIVATE_PAGES = [
    "/pwa/dashboard",
    "/pwa/clients",
    "/pwa/carnet",
    "/pwa/pointage-shell",
    "/pwa/cycles-liste",
    "/pwa/collectes-liste",
    "/pwa/sync",
    "/pwa/security-pin",
    "/pwa/gains",
    "/pwa/stats",
];

const FALLBACK_PAGES = ["/pwa/dashboard", "/agent/login"];

// Fonction ultra-sécurisée pour écrire dans le cache sans faire planter le SW
async function safeCachePut(cache, request, response) {
    try {
        await cache.put(request, response);
    } catch (err) {
        console.warn(
            "Cache.put ignoré proprement pour",
            request.url || request,
            err.message,
        );
    }
}

self.addEventListener("install", (event) => {
    self.skipWaiting();
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(PUBLIC_ASSETS)),
    );
});

self.addEventListener("activate", (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) => {
                return Promise.all(
                    keys
                        .filter((key) => key !== CACHE_NAME)
                        .map((key) => caches.delete(key)),
                );
            })
            .then(() => self.clients.claim()),
    );
});

let isCachingInProgress = false;

self.addEventListener("message", (event) => {
    if (event.data && event.data.action === "cachePrivatePages") {
        if (isCachingInProgress) return;
        isCachingInProgress = true;

        event.waitUntil(
            (async () => {
                const cache = await caches.open(CACHE_NAME);
                for (const url of PRIVATE_PAGES) {
                    try {
                        const response = await fetch(url, {
                            credentials: "include",
                        });
                        if (
                            response.ok &&
                            !response.url.includes("/agent/login")
                        ) {
                            await safeCachePut(cache, url, response.clone());
                        }
                    } catch (err) {
                        console.warn(
                            "Aspiration privée échouée pour",
                            url,
                            err,
                        );
                    }
                }
                isCachingInProgress = false;
                console.log("🏁 Aspiration terminée !");
            })(),
        );
    }
});


self.addEventListener("fetch", (event) => {
    // 1. Ignorer les requêtes non-GET, l'espace Admin et les API de données
    if (event.request.method !== "GET") return;
    const url = new URL(event.request.url);

    if (
        url.pathname.startsWith("/admin") ||
        url.pathname.includes("/pwa/get-initial-data") ||
        url.pathname.includes("/api/")
    ) {
        return; // Laisse passer le trafic directement vers Alwaysdata sans interférence
    }

    // Fonction pour renvoyer une page de secours HTML
    const respondWithFallbackHTML = async () => {
        for (const fallback of FALLBACK_PAGES) {
            const fallbackResponse = await caches.match(fallback);
            if (fallbackResponse) {
                return fallbackResponse;
            }
        }
        return new Response(
            "<h1>Hors ligne</h1><p>Veuillez vous connecter à Internet.</p>",
            {
                status: 503,
                headers: { "Content-Type": "text/html" },
            },
        );
    };

    // 2. GESTION DE LA NAVIGATION : Stratégie Réseau d'abord (Network First)
    if (event.request.mode === "navigate") {
        event.respondWith(
            (async () => {
                const cache = await caches.open(CACHE_NAME);

                try {
                    // On essaie TOUJOURS d'aller chercher la vraie page fraîche sur le serveur
                    const networkResponse = await fetch(event.request);
                    if (networkResponse && networkResponse.ok) {
                        await safeCachePut(
                            cache,
                            event.request,
                            networkResponse.clone(),
                        );
                        return networkResponse;
                    }
                } catch (err) {
                    // En cas de panne réseau (hors-ligne), on bascule sur le cache exact
                    const exactCache = await caches.match(event.request);
                    if (exactCache) {
                        return exactCache;
                    }

                    // Sinon on cherche l'App Shell générique
                    const basePath = PRIVATE_PAGES.includes(url.pathname)
                        ? url.pathname
                        : null;
                    if (basePath) {
                        const baseCache = await caches.match(basePath);
                        if (baseCache) {
                            return baseCache;
                        }
                    }
                }

                return respondWithFallbackHTML();
            })(),
        );
        return;
    }

    // 3. GESTION DES ASSETS STATIQUES (JS, CSS, Polices, Images)
    event.respondWith(
        (async () => {
            const cachedResponse = await caches.match(event.request);
            if (cachedResponse) {
                return cachedResponse;
            }

            try {
                const networkResponse = await fetch(event.request);
                if (networkResponse && networkResponse.status === 200) {
                    const cache = await caches.open(CACHE_NAME);
                    await safeCachePut(
                        cache,
                        event.request,
                        networkResponse.clone(),
                    );
                }
                return networkResponse;
            } catch (err) {
                return new Response("", {
                    status: 404,
                    statusText: "Offline Asset Not Found",
                });
            }
        })(),
    );
});





