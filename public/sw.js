const CACHE_NAME = "futurehope-pwa-v2";
const APP_SHELL = [
  "/",
  "/offline",
  "/android-chrome-192x192.png",
  "/android-chrome-512x512.png",
  "/apple-touch-icon.png",
  "/favicon-32x32.png",
  "/favicon-16x16.png",
];

const TRACKING_PATHS = new Set(["/api/tracking/event", "/api/tracking/pwa-status"]);

self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(APP_SHELL)).then(() => self.skipWaiting())
  );
});

self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key)))
    ).then(() => self.clients.claim())
  );
});

self.addEventListener("message", (event) => {
  if (event.data?.type === "SKIP_WAITING") self.skipWaiting();
});

async function openQueue() {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open("futurehope-pwa", 1);
    request.onupgradeneeded = () => {
      const db = request.result;
      if (!db.objectStoreNames.contains("tracking")) {
        db.createObjectStore("tracking", { keyPath: "id", autoIncrement: true });
      }
    };
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}

async function queueTracking(request) {
  const body = await request.clone().text();
  const headers = {};
  request.headers.forEach((value, key) => { headers[key] = value; });
  const db = await openQueue();
  await new Promise((resolve, reject) => {
    const tx = db.transaction("tracking", "readwrite");
    tx.objectStore("tracking").add({ url: request.url, body, headers });
    tx.oncomplete = resolve;
    tx.onerror = () => reject(tx.error);
  });
  db.close();
}

async function flushTracking() {
  const db = await openQueue();
  const items = await new Promise((resolve, reject) => {
    const tx = db.transaction("tracking", "readonly");
    const req = tx.objectStore("tracking").getAll();
    req.onsuccess = () => resolve(req.result);
    req.onerror = () => reject(req.error);
  });

  for (const item of items) {
    try {
      const response = await fetch(item.url, {
        method: "POST",
        headers: item.headers,
        body: item.body,
        credentials: "include",
      });
      if (!response.ok) continue;
      await new Promise((resolve, reject) => {
        const tx = db.transaction("tracking", "readwrite");
        tx.objectStore("tracking").delete(item.id);
        tx.oncomplete = resolve;
        tx.onerror = () => reject(tx.error);
      });
    } catch {
      break;
    }
  }
  db.close();
}

self.addEventListener("sync", (event) => {
  if (event.tag === "futurehope-tracking-sync") {
    event.waitUntil(flushTracking());
  }
});

self.addEventListener("fetch", (event) => {
  const request = event.request;
  const url = new URL(request.url);
  if (url.origin !== self.location.origin) return;

  if (request.method === "POST" && TRACKING_PATHS.has(url.pathname)) {
    event.respondWith(
      fetch(request.clone()).catch(async () => {
        try {
          await queueTracking(request);
          if ("sync" in self.registration) {
            await self.registration.sync.register("futurehope-tracking-sync");
          }
        } catch {
          return new Response(JSON.stringify({ queued: false }), {
            status: 503,
            headers: { "Content-Type": "application/json" },
          });
        }
        return new Response(JSON.stringify({ queued: true }), {
          status: 202,
          headers: { "Content-Type": "application/json" },
        });
      })
    );
    return;
  }

  if (request.method !== "GET") return;

  if (request.mode === "navigate") {
    event.respondWith(
      fetch(request)
        .then((response) => {
          if (response.ok) {
            const copy = response.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
          }
          return response;
        })
        .catch(() => caches.match(request).then((cached) => cached || caches.match("/offline")))
    );
    return;
  }

  if (url.pathname.startsWith("/_next/static/")) {
    event.respondWith(
      caches.match(request).then((cached) =>
        cached || fetch(request).then((response) => {
          if (response.ok) {
            const copy = response.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
          }
          return response;
        })
      )
    );
  }
});
