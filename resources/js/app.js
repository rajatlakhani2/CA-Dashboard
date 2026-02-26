import './bootstrap';
import { registerSW } from 'virtual:pwa-register';

if ('serviceWorker' in navigator) {
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
