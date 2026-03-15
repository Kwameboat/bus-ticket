/**
 * GhanaBus Connect - Service Worker
 * Workbox-style manual implementation for cPanel compatibility
 */

const CACHE_VERSION = 'v1.0.0';
const STATIC_CACHE  = `gbc-static-${CACHE_VERSION}`;
const PAGES_CACHE   = `gbc-pages-${CACHE_VERSION}`;
const ALL_CACHES    = [STATIC_CACHE, PAGES_CACHE];

// Assets to precache on install
const PRECACHE_ASSETS = [
    '/offline',
    '/css/app.css',
    '/js/app.js',
    '/manifest.json',
    '/pwa-icons/icon-192.png',
    '/pwa-icons/icon-512.png',
];

// Routes that must NEVER be cached (payment, booking mutations)
const NEVER_CACHE_PATTERNS = [
    /\/api\//, /\/webhooks\//, /\/checkout/, /\/payment/,
    /\/bookings\/[^/]+\/confirm/, /\/seats\/lock/,
];

// Routes that should always use network (fresh data required)
const NETWORK_ONLY_PATTERNS = [
    /\/search/, /\/trips\/[^/]+\/seats/, /\/bookings$/,
];

// ── Install ──────────────────────────────────────────────────────────────────
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then(cache => cache.addAll(PRECACHE_ASSETS))
            .then(() => self.skipWaiting())
    );
});

// ── Activate ─────────────────────────────────────────────────────────────────
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(
                keys.filter(key => !ALL_CACHES.includes(key))
                    .map(key => caches.delete(key))
            )
        ).then(() => self.clients.claim())
    );
});

// ── Fetch ─────────────────────────────────────────────────────────────────────
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);

    // Only handle same-origin GET requests
    if (request.method !== 'GET' || url.origin !== self.location.origin) return;

    // Never cache: payment/booking mutations
    if (NEVER_CACHE_PATTERNS.some(p => p.test(url.pathname))) {
        event.respondWith(fetch(request));
        return;
    }

    // Network-only: dynamic pages needing fresh data
    if (NETWORK_ONLY_PATTERNS.some(p => p.test(url.pathname))) {
        event.respondWith(
            fetch(request).catch(() => caches.match('/offline'))
        );
        return;
    }

    // Static assets: Cache First
    if (/\.(css|js|png|jpg|jpeg|webp|svg|woff2|ico)$/.test(url.pathname)) {
        event.respondWith(
            caches.match(request).then(cached => {
                return cached || fetch(request).then(response => {
                    const clone = response.clone();
                    caches.open(STATIC_CACHE).then(c => c.put(request, clone));
                    return response;
                });
            })
        );
        return;
    }

    // HTML pages: Network First with cache fallback
    event.respondWith(
        fetch(request)
            .then(response => {
                const clone = response.clone();
                caches.open(PAGES_CACHE).then(c => c.put(request, clone));
                return response;
            })
            .catch(() => caches.match(request)
                .then(cached => cached || caches.match('/offline'))
            )
    );
});

// ── Background Sync placeholder ───────────────────────────────────────────────
self.addEventListener('message', event => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});
