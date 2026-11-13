/*
 * Meridyen Roleplay service worker — docs/SRS.md FR-PWA-001..005.
 * - Kimliği doğrulanmış HTML (/panel, /admin), kimlik uçları ve /api ASLA cache'lenmez (network-only).
 * - Gezinmeler: ağ önce; ağ yoksa /offline.html.
 * - /build/* (içerik hash'li) varlıklar: cache önce (çalışma zamanı cache'i).
 * - Mesajlar: SKIP_WAITING (güncelleme onayı), CLEAR_RUNTIME_CACHE (çıkış).
 * - Web Push (Faz 10): yalnızca başlık/özet/URL gösterilir; tıklamada yalnızca aynı origin'e gidilir.
 */
const VERSION = 'mrp-v2';
const PRECACHE = `${VERSION}-precache`;
const RUNTIME = `${VERSION}-runtime`;
const PRECACHE_URLS = ['/offline.html', '/manifest.webmanifest', '/favicon.svg', '/icons/icon-192.png'];
const NEVER_CACHE = /^\/(panel|admin|api|login|logout|register|email|two-factor|user|forgot-password|reset-password|webhooks)(\/|$)/;

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(PRECACHE).then((cache) => cache.addAll(PRECACHE_URLS)));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => !k.startsWith(VERSION)).map((k) => caches.delete(k))))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('message', (event) => {
    if (event.data === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    if (event.data === 'CLEAR_RUNTIME_CACHE') {
        event.waitUntil(caches.delete(RUNTIME));
    }
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    if (request.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    if (NEVER_CACHE.test(url.pathname)) {
        if (request.mode === 'navigate') {
            event.respondWith(fetch(request).catch(() => caches.match('/offline.html')));
        }
        return; // ağ (tarayıcı varsayılanı), cache yok
    }

    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match('/offline.html')));
        return;
    }

    if (url.pathname.startsWith('/build/')) {
        event.respondWith(
            caches.open(RUNTIME).then((cache) => cache.match(request).then((hit) => hit || fetch(request).then((response) => {
                if (response.ok) {
                    cache.put(request, response.clone());
                }
                return response;
            }))),
        );
    }
});

self.addEventListener('push', (event) => {
    let data = {};
    try {
        data = event.data ? event.data.json() : {};
    } catch {
        data = {};
    }
    const title = typeof data.title === 'string' && data.title !== '' ? data.title : 'Meridyen Roleplay';
    event.waitUntil(self.registration.showNotification(title, {
        body: typeof data.body === 'string' ? data.body : '',
        tag: typeof data.tag === 'string' ? data.tag : undefined,
        icon: '/icons/icon-192.png',
        badge: '/icons/icon-192.png',
        data: { url: typeof data.url === 'string' ? data.url : '/panel' },
    }));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    let target = new URL('/panel', self.location.origin);
    try {
        const candidate = new URL(event.notification.data?.url || '/panel', self.location.origin);
        if (candidate.origin === self.location.origin) {
            target = candidate;
        }
    } catch {
        // geçersiz URL: panel ana sayfası
    }
    event.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windows) => {
        const open = windows.find((w) => w.url === target.href);
        return open ? open.focus() : self.clients.openWindow(target.href);
    }));
});