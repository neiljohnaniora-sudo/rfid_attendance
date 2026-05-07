const cacheName = 'attendance-v1';
const assets = [
  '/',
  'index.php',
  'manifest.json'
];

// Install service worker
self.addEventListener('install', evt => {
  evt.waitUntil(
    caches.open(cacheName).then(cache => {
      console.log('Caching assets');
      cache.addAll(assets);
    })
  );
});

// Fetch events
self.addEventListener('fetch', evt => {
  evt.respondWith(
    caches.match(evt.request).then(cacheRes => {
      return cacheRes || fetch(evt.request);
    })
  );
});