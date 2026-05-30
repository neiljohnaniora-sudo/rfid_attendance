const cacheName = 'attendance-v4'; // Gi-update ngadto sa v4 aron mo-gana ang Auto-Update
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
  // KINI ANG BAG-O: NETWORK FIRST STRATEGY (Para mo-update automatic)
  evt.respondWith(
    fetch(evt.request).then(fetchRes => {
      return caches.open(cacheName).then(cache => {
        // I-save ang latest nga updates sa cache aron ready inig offline
        cache.put(evt.request.url, fetchRes.clone());
        return fetchRes;
      });
    }).catch(() => {
      // Kung walay internet, anha pa siya mokuha sa karaan nga cache
      return caches.match(evt.request);
    })
  );
});