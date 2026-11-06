/*
 * Service worker for the installable app.
 *
 * Privacy rule: visitors may be blackmail victims on a shared phone, so pages are
 * never stored on the device. Only versioned build files, icons and site images are
 * cached, plus one neutral offline page shown when there is no connection.
 * Bump VERSION on each release so phones fetch a fresh offline page.
 */
const VERSION = '1.16.0';
const CACHE = `ibtizaz-static-${VERSION}`;
const scope = new URL(self.registration.scope);
const OFFLINE_URL = new URL('offline', scope).href;
const PRECACHE = [OFFLINE_URL, 'icon-192.png', 'icon-512.png', 'favicon.svg'].map((path) => new URL(path, scope).href);

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE)
            .then((cache) => cache.addAll(PRECACHE.map((url) => new Request(url, { credentials: 'omit', cache: 'reload' }))))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((key) => key.startsWith('ibtizaz-') && key !== CACHE).map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    );
});

const isStaticAsset = (url) => {
    if (url.origin !== scope.origin || !url.pathname.startsWith(scope.pathname)) return false;
    const path = url.pathname.slice(scope.pathname.length);
    return path.startsWith('build/')
        || path.startsWith('images/')
        || /^(favicon\.(ico|svg)|apple-touch-icon\.png|icon-[\w-]+\.png)$/.test(path);
};

self.addEventListener('fetch', (event) => {
    const { request } = event;
    if (request.method !== 'GET') return;

    // Pages: always from the network. Only when offline, show the neutral offline page.
    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_URL)));
        return;
    }

    const url = new URL(request.url);
    if (!isStaticAsset(url)) return;

    // Build files have hashed names, so a cached copy is always correct.
    event.respondWith(
        caches.open(CACHE).then((cache) => cache.match(request).then((cached) => {
            const network = fetch(request).then((response) => {
                if (response.ok && response.type === 'basic') cache.put(request, response.clone());
                return response;
            });
            if (cached) {
                if (!url.pathname.includes('/build/')) event.waitUntil(network.catch(() => {}));
                return cached;
            }
            return network;
        })),
    );
});
