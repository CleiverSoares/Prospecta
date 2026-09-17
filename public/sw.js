const CACHE = 'prospecta-shell-v8';
const SHELL = ['/manifest.webmanifest', '/app/setup', '/app/rota', '/app/checkin'];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE).then((cache) => cache.addAll(SHELL).catch(() => undefined)).then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))),
        ).then(() => self.clients.claim()),
    );
});

self.addEventListener('message', (event) => {
    if (event.data?.type === 'CACHE_ROTA' && Array.isArray(event.data.urls)) {
        event.waitUntil(
            caches.open(CACHE).then((cache) =>
                Promise.all(event.data.urls.map((u) => cache.add(u).catch(() => undefined))),
            ),
        );
    }
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') {
        return;
    }

    const url = new URL(event.request.url);
    if (url.origin !== self.location.origin) {
        return;
    }

    // Offline-first para pacote da rota; network-first para o resto do app
    const offlineFirst = url.pathname.startsWith('/build/')
        || url.pathname === '/manifest.webmanifest'
        || url.pathname.startsWith('/app/');

    if (offlineFirst) {
        event.respondWith(
            caches.match(event.request).then((cached) => {
                const fetched = fetch(event.request)
                    .then((res) => {
                        const clone = res.clone();
                        caches.open(CACHE).then((c) => c.put(event.request, clone)).catch(() => {});
                        return res;
                    })
                    .catch(() => cached || Response.error());

                return cached || fetched;
            }),
        );
        return;
    }

    event.respondWith(
        fetch(event.request).catch(async () => {
            const cached = await caches.match(event.request);
            return cached || Response.error();
        }),
    );
});
