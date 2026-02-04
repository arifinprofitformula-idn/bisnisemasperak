/* EPI Hub SW – static cache-first, HTML network-first, API network-only */
const CACHE_NAME = 'epi-pwa-v2';
const OFFLINE_URL = '/welcome-epi.html';
const ASSETS = [
  '/bootstrap-5.3.3/css/bootstrap.min.css',
  '/fontawesome/css/all.css',
  '/upload/logoweb.jpg',
  '/manifest.webmanifest',
  OFFLINE_URL
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => cache.addAll(ASSETS))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.map((k) => (k !== CACHE_NAME ? caches.delete(k) : Promise.resolve()))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') {
    return; // jangan ganggu POST login/logout
  }

  const url = new URL(req.url);
  const isSameOrigin = url.origin === self.location.origin;
  const accept = req.headers.get('accept') || '';
  const isHtml = req.mode === 'navigate' || accept.includes('text/html');
  const isApi = isSameOrigin && url.pathname.startsWith('/api/');
  const isStatic = ['style', 'script', 'image', 'font'].includes(req.destination)
    || ASSETS.includes(url.pathname)
    || url.pathname === '/manifest.webmanifest';

  // API: selalu network-only untuk menghindari data sesi salah
  if (isApi) {
    event.respondWith(
      fetch(req).catch(() => new Response(JSON.stringify({ error: 'offline' }), {
        status: 503,
        headers: { 'Content-Type': 'application/json' }
      }))
    );
    return;
  }

  // HTML: network-first, jangan cache response HTML (user-specific)
  if (isHtml) {
    event.respondWith(
      fetch(req).then((res) => res).catch(() => caches.match(OFFLINE_URL))
    );
    return;
  }

  // Static assets: cache-first
  if (isStatic && isSameOrigin) {
    event.respondWith(
      caches.match(req).then((cached) => {
        if (cached) return cached;
        return fetch(req).then((res) => {
          if (res.status === 200) {
            const resClone = res.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(req, resClone)).catch(() => {});
          }
          return res;
        });
      })
    );
    return;
  }

  // Default: network-first tanpa cache
  event.respondWith(
    fetch(req).catch(() => caches.match(req))
  );
});

// Opsional: purge cache saat auth berubah (login/logout) – panggil dari halaman dengan navigator.serviceWorker.controller.postMessage({ type: 'AUTH_CHANGED' })
self.addEventListener('message', (event) => {
  const data = event.data || {};
  // Jangan log payload sensitif
  if (data.type === 'AUTH_CHANGED' || data.type === 'LOGOUT') {
    event.waitUntil((async () => {
      // Purge semua cache PWA untuk mencegah kebocoran sesi
      const names = await caches.keys();
      await Promise.all(names.map((n) => caches.delete(n)));
      // Re-seed cache statis minimal agar offline tetap berfungsi
      const cache = await caches.open(CACHE_NAME);
      await cache.addAll(ASSETS);
    })());
  }
});