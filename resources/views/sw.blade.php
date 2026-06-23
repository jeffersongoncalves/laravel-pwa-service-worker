// Service worker generated from Blade so the VERSION constant tracks the Vite
// build manifest hash, changing whenever assets are rebuilt. A new VERSION
// forces a new cache name on activate and tears down the old caches.
const VERSION = '{{ $version }}';
const CACHE_PREFIX = '{{ $cachePrefix }}';
const CACHE_NAME = `${CACHE_PREFIX}-v${VERSION}`;
const OFFLINE_URL = '{{ $offlineUrl }}';

// Pre-cache the bare minimum needed to answer a navigation request offline.
const PRECACHE_URLS = @json($precacheUrls);

// Requests that must always hit the network (auth panels, live wires, the SW
// + manifest themselves so updates aren't held back by their own cached copy).
const PASSTHROUGH_PREFIXES = @json($passthroughPrefixes);
const PASSTHROUGH_EXACT = @json($passthroughExact);
const ASSET_PREFIX = '{{ $assetPrefix }}';

self.addEventListener('install', (event) => {
    // skipWaiting lets the newly installed SW take over without forcing the
    // user to close every tab first. Pairs with clients.claim() below.
    self.skipWaiting();

    event.waitUntil(
        // Cache each URL independently: cache.addAll() is atomic, so a single
        // 404/redirect (e.g. an /offline route the app forgot to register)
        // would reject the whole install and leave the SW permanently broken.
        // Per-URL add() with a swallowed rejection installs best-effort instead.
        caches.open(CACHE_NAME).then((cache) =>
            Promise.all(
                PRECACHE_URLS.map((url) => cache.add(url).catch(() => {})),
            ),
        ),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        (async () => {
            const names = await caches.keys();
            await Promise.all(
                names
                    .filter((name) => name.startsWith(`${CACHE_PREFIX}-`) && name !== CACHE_NAME)
                    .map((name) => caches.delete(name)),
            );
            await self.clients.claim();

            // Tell every controlled page which version just activated. The
            // client decides whether to surface an update toast — it can't be
            // decided here because the SW doesn't know if this is the first
            // install (no old version) or a real upgrade.
            const clients = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
            for (const client of clients) {
                client.postMessage({ type: 'pwa-updated', version: VERSION });
            }
        })(),
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;

    // Only meddle with same-origin GETs. Anything else goes straight to the
    // network — caching it would be wrong (POST) or pointless (cross-origin).
    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;

    if (PASSTHROUGH_EXACT.includes(url.pathname)) return;
    if (PASSTHROUGH_PREFIXES.some((prefix) => url.pathname === prefix || url.pathname.startsWith(`${prefix}/`))) return;

    // Hashed build outputs are immutable (content-hashed names), so cache-first
    // is correct — the HTML changes its references on the next release.
    if (ASSET_PREFIX && url.pathname.startsWith(ASSET_PREFIX)) {
        event.respondWith(cacheFirst(request));
        return;
    }

    // HTML navigations: network-first so users see fresh content when online,
    // with cached fallback (and finally OFFLINE_URL) when not.
    if (request.mode === 'navigate' || request.destination === 'document') {
        event.respondWith(networkFirst(request));
        return;
    }

    // Everything else (images, fonts, etc) — stale-while-revalidate so the page
    // renders instantly off cache while a fresh copy is fetched in background.
    event.respondWith(staleWhileRevalidate(request));
});

async function cacheFirst(request) {
    const cache = await caches.open(CACHE_NAME);
    const cached = await cache.match(request);

    if (cached) {
        return cached;
    }

    try {
        const fresh = await fetch(request);

        if (fresh && fresh.ok) {
            cache.put(request, fresh.clone());
        }

        return fresh;
    } catch (error) {
        // Offline with nothing cached. Resolve to a synthetic error Response
        // rather than rejecting — a rejected respondWith() surfaces as
        // "Failed to convert value to 'Response'".
        return Response.error();
    }
}

async function networkFirst(request) {
    const cache = await caches.open(CACHE_NAME);

    try {
        const fresh = await fetch(request);

        if (fresh && fresh.ok) {
            cache.put(request, fresh.clone());
        }

        return fresh;
    } catch (error) {
        const cached = await cache.match(request);

        if (cached) {
            return cached;
        }

        const offline = await cache.match(OFFLINE_URL);

        return (
            offline ||
            new Response('Offline', {
                status: 503,
                statusText: 'Service Unavailable',
                headers: { 'Content-Type': 'text/plain' },
            })
        );
    }
}

async function staleWhileRevalidate(request) {
    const cache = await caches.open(CACHE_NAME);
    const cached = await cache.match(request);

    const fetchPromise = fetch(request)
        .then((response) => {
            if (response && response.ok) {
                cache.put(request, response.clone());
            }
            return response;
        })
        .catch(() => null);

    // Always resolve to a Response: cached hit, then network result, then a
    // synthetic error Response.
    return cached || (await fetchPromise) || Response.error();
}
