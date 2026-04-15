const CACHE = 'nexapos-v1';

// Static assets to cache on install
const PRECACHE = [
  '/nexapos/public/assets/css/app.css',
  '/nexapos/public/assets/js/app.js',
  '/nexapos/public/assets/icons/icon-192.png',
  '/nexapos/public/assets/icons/icon-512.png',
  '/nexapos/public/manifest.json',
];

// Install: pre-cache static assets
self.addEventListener('install', e => {
  e.waitUntil(
    caches.open(CACHE).then(cache => {
      // Cache what's available, ignore failures (files may not exist)
      return Promise.allSettled(
        PRECACHE.map(url => cache.add(url).catch(() => {}))
      );
    }).then(() => self.skipWaiting())
  );
});

// Activate: clean up old caches
self.addEventListener('activate', e => {
  e.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)))
    ).then(() => self.clients.claim())
  );
});

// Fetch strategy:
// - API calls (routes/api.php): network-only, never cache
// - HTML pages: network-first, fall back to cache
// - Static assets (css/js/img): cache-first, update in background
self.addEventListener('fetch', e => {
  const url = new URL(e.request.url);

  // Skip non-GET and cross-origin
  if (e.request.method !== 'GET') return;
  if (url.origin !== self.location.origin) return;

  // API: always network
  if (url.pathname.includes('api.php') || url.search.includes('action=')) {
    e.respondWith(fetch(e.request));
    return;
  }

  // HTML pages: network-first
  if (e.request.headers.get('accept')?.includes('text/html')) {
    e.respondWith(
      fetch(e.request)
        .then(res => {
          const clone = res.clone();
          caches.open(CACHE).then(c => c.put(e.request, clone));
          return res;
        })
        .catch(() => caches.match(e.request))
    );
    return;
  }

  // Static assets: cache-first
  e.respondWith(
    caches.match(e.request).then(cached => {
      const network = fetch(e.request).then(res => {
        caches.open(CACHE).then(c => c.put(e.request, res.clone()));
        return res;
      });
      return cached || network;
    })
  );
});
