const CACHE_NAME = 'marketing-center-mobile-v16';
const STATIC_ASSETS = [
  '/assets/app.css',
  '/assets/brand.css',
  '/assets/app.js',
  '/assets/live-chat-widget.js',
  '/assets/pwa-icon.svg',
  '/manifest.webmanifest'
];

const OFFLINE_HTML = [
  '<!doctype html>',
  '<html lang="ar" dir="rtl">',
  '<meta charset="utf-8">',
  '<meta name="viewport" content="width=device-width,initial-scale=1">',
  '<title>Marketing Center</title>',
  '<body style="font-family:Arial,sans-serif;padding:24px;text-align:right;background:#f6f7f9;color:#172033">',
  '<h1>لا يوجد اتصال</h1>',
  '<p>افتح التطبيق مرة أخرى عند عودة الاتصال.</p>',
  '</body>',
  '</html>'
].join('');

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => cache.addAll(STATIC_ASSETS))
      .then(() => self.skipWaiting())
      .catch(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const request = event.request;

  if (request.method !== 'GET') {
    return;
  }

  const url = new URL(request.url);
  if (url.origin !== self.location.origin) {
    return;
  }

  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request).catch(() => new Response(
        OFFLINE_HTML,
        {headers: {'Content-Type': 'text/html; charset=utf-8'}}
      ))
    );
    return;
  }

  if (STATIC_ASSETS.includes(url.pathname) || url.pathname.startsWith('/assets/')) {
    event.respondWith(
      fetch(request).then((response) => {
        const copy = response.clone();
        caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
        return response;
      }).catch(() => caches.match(request))
    );
  }
});
