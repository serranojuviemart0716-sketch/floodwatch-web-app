// FloodWatch Service Worker for Offline Support

const CACHE_NAME = 'floodwatch-v1';
const urlsToCache = [
    '/',
    '/index.html',
    '/styles.css',
    '/api-client.js',
    '/performance.js',
    'https://cdn.tailwindcss.com',
    'https://cdn.jsdelivr.net/npm/font-awesome@6.6.0/css/all.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js'
];

// Install Service Worker
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Cache opened');
                return cache.addAll(urlsToCache).catch(err => {
                    console.log('Cache addAll error:', err);
                    // Continue even if some URLs fail to cache
                    return Promise.resolve();
                });
            })
    );
});

// Activate Service Worker
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});

// Fetch Event - Network first, fallback to cache
self.addEventListener('fetch', event => {
    // Skip non-GET requests
    if (event.request.method !== 'GET') {
        return;
    }

    // For API calls, use network-first strategy
    if (event.request.url.includes('/api/') || event.request.url.includes('.php')) {
        event.respondWith(
            fetch(event.request)
                .then(response => {
                    // Cache successful responses
                    if (response && response.status === 200) {
                        const responseClone = response.clone();
                        caches.open(CACHE_NAME).then(cache => {
                            cache.put(event.request, responseClone);
                        });
                    }
                    return response;
                })
                .catch(() => {
                    // Return cached response if network fails
                    return caches.match(event.request);
                })
        );
        return;
    }

    // For assets, use cache-first strategy
    event.respondWith(
        caches.match(event.request)
            .then(response => {
                // Return from cache if available
                if (response) {
                    return response;
                }

                return fetch(event.request).then(response => {
                    // Don't cache non-successful responses
                    if (!response || response.status !== 200 || response.type === 'error') {
                        return response;
                    }

                    // Cache the successful response
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME).then(cache => {
                        cache.put(event.request, responseClone);
                    });

                    return response;
                });
            })
            .catch(() => {
                // Return offline page or cached resource
                if (event.request.mode === 'navigate') {
                    return caches.match('/index.html');
                }
                return new Response('Offline - Resource not available', {
                    status: 503,
                    statusText: 'Service Unavailable'
                });
            })
    );
});

// Handle messages from clients
self.addEventListener('message', event => {
    if (event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});
