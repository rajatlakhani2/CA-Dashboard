import './bootstrap';
import { registerSW } from 'virtual:pwa-register';

if ('serviceWorker' in navigator) {
    const isLocalHost = ['localhost', '127.0.0.1'].includes(window.location.hostname);

    if (isLocalHost) {
        navigator.serviceWorker.getRegistrations().then((registrations) => {
            registrations.forEach((registration) => registration.unregister());
        });
    } else {
        registerSW({
            immediate: true,
            onNeedRefresh() {
                // Logic for refresh prompt can go here
            },
            onOfflineReady() {
                // Logic for offline ready message can go here
            },
        });
    }
}
