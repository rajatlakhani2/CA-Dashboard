/*
 * VouchEx app service worker.
 *
 * Deliberately stores no pages or data. The earlier Workbox setup cached
 * Laravel pages and served stale/broken screens, so this worker:
 *   - deletes every cache left behind by that old setup,
 *   - always fetches pages from the server,
 *   - only shows a small "you're offline" screen when the network is down.
 * Client data never lands on the phone's disk.
 */
const VERSION = 'vouchex-sw-1';

self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.map((key) => caches.delete(key))))
            .then(() => self.clients.claim())
    );
});

const OFFLINE_HTML = `<!doctype html>
<html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Offline — VouchEx</title>
<style>
  body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;
       font-family:system-ui,-apple-system,sans-serif;background:#0b1736;color:#e2e8f0;text-align:center;padding:24px}
  h1{font-size:1.25rem;margin:0 0 .5rem;color:#fff}
  p{margin:0 0 1.25rem;color:#94a3b8;line-height:1.5}
  button{border:0;border-radius:12px;padding:.75rem 1.5rem;font-size:1rem;font-weight:600;
         background:#22c55e;color:#052e16}
</style></head>
<body><div>
  <h1>You're offline</h1>
  <p>VouchEx needs an internet connection.<br>Check your connection and try again.</p>
  <button onclick="location.reload()">Try again</button>
</div></body></html>`;

self.addEventListener('fetch', (event) => {
    const request = event.request;

    // Only full-page loads get the offline screen; everything else
    // (assets, form posts, AJAX) goes straight to the network untouched.
    if (request.mode !== 'navigate' || request.method !== 'GET') {
        return;
    }

    event.respondWith(
        fetch(request).catch(() => new Response(OFFLINE_HTML, {
            status: 503,
            headers: { 'Content-Type': 'text/html; charset=utf-8', 'Cache-Control': 'no-store' },
        }))
    );
});
