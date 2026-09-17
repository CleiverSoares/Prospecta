const CACHE = 'prospecta-shell-v6';
const SHELL = ['/manifest.webmanifest'];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE).then((cache) => cache.addAll(SHELL)).then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))),
        ).then(() => self.clients.claim()),
    );
});

// Network-first: nunca prende HTML/JS velho (causa Alpine "X is not defined").
self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') {
        return;
    }

    const url = new URL(event.request.url);
    if (url.pathname.startsWith('/build/') || url.pathname.startsWith('/app') || url.pathname.startsWith('/admin')) {
        event.respondWith(
            fetch(event.request)
                .then((res) => res)
                .catch(() => caches.match(event.request)),
        );
        return;
    }

    event.respondWith(
        fetch(event.request).catch(() => caches.match(event.request)),
    );
});
