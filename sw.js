const cacheName = 'attendance-v3'; // Gi-update ngadto sa v3 aron ma-load ang bag-ong loading screen
// Gamay lang sa ang assets para dili mag-error sa install
const assets = [
  './',
  './index.php',
  './manifest.json'
];

// Install service worker
self.addEventListener('install', evt => {
  self.skipWaiting(); // Para mo-activate dayon ang bag-ong version
  evt.waitUntil(
    caches.open(cacheName).then(cache => {
      console.log('Caching essential assets');
      // Gigamit ang map para kung naay usa nga mag-fail, dili ma-block ang tibuok install
      return Promise.all(
        assets.map(url => {
          return cache.add(url).catch(err => console.log('Failed to cache:', url));
        })
      );
    })
  );
});

// Activate event (Pag-clear sa karaan nga cache)
self.addEventListener('activate', evt => {
  evt.waitUntil(
    caches.keys().then(keys => {
      return Promise.all(keys
        .filter(key => key !== cacheName)
        .map(key => caches.delete(key))
      );
    })
  );
});

// Fetch events (Kritikal ni para sa "Install App" sa CP)
self.addEventListener('fetch', evt => {
  evt.respondWith(
    caches.match(evt.request).then(cacheRes => {
      return cacheRes || fetch(evt.request).catch(() => {
        // Pwede nimo butangan og offline page diri puhon
      });
    })
  );
});