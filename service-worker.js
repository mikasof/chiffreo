/**
 * Chiffreo - Service Worker
 * Cache basique pour fonctionnement offline
 */

const CACHE_NAME = 'chiffreo-v6';
// Production: BASE_PATH vide, Local: '/chiffreo/public'
const BASE_PATH = '';

const STATIC_ASSETS = [
    BASE_PATH + '/',
    BASE_PATH + '/app',
    BASE_PATH + '/css/app.css',
    BASE_PATH + '/js/app.js',
    BASE_PATH + '/manifest.json'
];

// Installation - mise en cache des assets statiques
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('Cache ouvert');
                // Ajouter les assets un par un pour éviter l'échec total
                return Promise.allSettled(
                    STATIC_ASSETS.map(url => cache.add(url).catch(err => console.warn('Cache skip:', url)))
                );
            })
            .then(() => self.skipWaiting())
    );
});

// Activation - nettoyage des anciens caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter((name) => name !== CACHE_NAME)
                        .map((name) => caches.delete(name))
                );
            })
            .then(() => self.clients.claim())
    );
});

// Fetch - stratégie Network First pour l'API, Cache First pour les assets
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Ignorer les requêtes non-HTTP (chrome-extension://, etc.)
    if (!url.protocol.startsWith('http')) {
        return;
    }

    // API calls - toujours réseau
    if (url.pathname.includes('/api/') || url.pathname.includes('/pdf/')) {
        event.respondWith(
            fetch(request)
                .catch(() => {
                    return new Response(
                        JSON.stringify({
                            success: false,
                            error: 'Vous êtes hors ligne'
                        }),
                        {
                            status: 503,
                            headers: { 'Content-Type': 'application/json' }
                        }
                    );
                })
        );
        return;
    }

    // Assets statiques - Network First avec fallback cache
    event.respondWith(
        fetch(request)
            .then((response) => {
                // Mettre en cache si OK
                if (response.ok && request.method === 'GET') {
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME)
                        .then((cache) => cache.put(request, responseClone));
                }
                return response;
            })
            .catch(() => {
                return caches.match(request)
                    .then((cachedResponse) => {
                        if (cachedResponse) {
                            return cachedResponse;
                        }
                        // Fallback pour la page principale
                        if (request.mode === 'navigate') {
                            return caches.match(BASE_PATH + '/app');
                        }
                        return new Response('Ressource non disponible hors ligne', {
                            status: 503
                        });
                    });
            })
    );
});

// Gestion des messages
self.addEventListener('message', (event) => {
    if (event.data === 'skipWaiting') {
        self.skipWaiting();
    }
});
