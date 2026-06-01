import './bootstrap';

// Laravel uses server-rendered pages — unregister legacy Workbox/PWA service workers.
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.getRegistrations().then((registrations) => {
        registrations.forEach((registration) => registration.unregister());
    });

    if ('caches' in window) {
        caches.keys().then((keys) => keys.forEach((key) => caches.delete(key)));
    }
}
