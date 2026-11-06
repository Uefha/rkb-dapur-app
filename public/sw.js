const CACHE_NAME = 'rkb-dapur-pwa-v6';
const APP_BASE_URL = new URL('./', self.registration.scope);
const OFFLINE_URL = new URL('offline.html', APP_BASE_URL).href;
const APP_ASSETS = ['logo.png', 'pwa-icon-192.png', 'pwa-icon-512.png']
    .map(path => new URL(path, APP_BASE_URL).href);

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll([OFFLINE_URL, ...APP_ASSETS]))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys()
            .then(keys => Promise.all(keys.filter(key => key.startsWith('rkb-dapur-pwa-') && key !== CACHE_NAME).map(key => caches.delete(key))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', event => {
    const request = event.request;
    const url = new URL(request.url);

    if (request.method !== 'GET' || url.origin !== self.location.origin) return;

    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(async () => {
            const offlinePage = await caches.match(OFFLINE_URL);
            return offlinePage || Response.error();
        }));
        return;
    }

    if (APP_ASSETS.includes(url.href)) {
        event.respondWith(fetch(request).catch(() => caches.match(request)));
    }
});
