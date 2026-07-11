const CACHE_NAME = 'anitech-farmer-static-v3';
const CORE_URLS = [
    './manifest.webmanifest',
    './icons/pwa-192.svg',
    './icons/pwa-512.svg',
];

const STATIC_FILE_PATTERN = /\.(?:css|js|mjs|png|jpg|jpeg|gif|svg|webp|ico|woff2?)$/i;
const DYNAMIC_PATH_PREFIXES = [
    '/api/',
    '/farmer/application/track',
    '/farmer/notifications/feed',
];

const shouldSkipCaching = (url) => {
    if (url.search !== '') {
        return true;
    }

    return DYNAMIC_PATH_PREFIXES.some((prefix) => url.pathname.startsWith(prefix));
};

const isStaticAssetRequest = (request, url) => {
    if (request.destination && ['style', 'script', 'image', 'font'].includes(request.destination)) {
        return true;
    }

    return STATIC_FILE_PATTERN.test(url.pathname);
};

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(CORE_URLS)));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))))
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const request = event.request;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin || shouldSkipCaching(url)) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(async () => {
                const fallback = await caches.match('./farmer/track');
                return fallback || Response.error();
            })
        );
        return;
    }

    if (!isStaticAssetRequest(request, url)) {
        return;
    }

    event.respondWith(
        caches.match(request).then((cached) => {
            if (cached) {
                return cached;
            }

            return fetch(request).then((response) => {
                if (!response || response.status !== 200 || response.type !== 'basic') {
                    return response;
                }

                const copy = response.clone();
                caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
                return response;
            });
        })
    );
});
