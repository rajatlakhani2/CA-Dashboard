/*
 * CA Dashboard – phone helpers.
 *
 * Runs on every signed-in page. Inside the Android app (Capacitor) it adds:
 *   - push notifications (registers this phone with the server, opens the tapped notification's page)
 *   - fingerprint / face lock when the app is opened or comes back after a minute in the background
 *   - forgets this phone's push token on sign-out
 * On any phone (app or browser) it adds a "Scan with camera" button to file inputs marked data-camera-scan.
 * In a normal laptop browser it does nothing.
 */
(function () {
    'use strict';

    var cap = window.Capacitor;
    var isApp = !!(cap && typeof cap.isNativePlatform === 'function' && cap.isNativePlatform());
    var csrf = function () {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.content : '';
    };
    var plugin = function (name) {
        if (!isApp) return null;
        if (cap.Plugins && cap.Plugins[name]) return cap.Plugins[name];
        try { return cap.registerPlugin ? cap.registerPlugin(name) : null; } catch (e) { return null; }
    };
    var store = {
        get: function (k) { try { return localStorage.getItem(k); } catch (e) { return null; } },
        set: function (k, v) { try { localStorage.setItem(k, v); } catch (e) { /* ignore */ } },
        sget: function (k) { try { return sessionStorage.getItem(k); } catch (e) { return null; } },
        sset: function (k, v) { try { sessionStorage.setItem(k, v); } catch (e) { /* ignore */ } }
    };
    var signedIn = !!document.querySelector('meta[name="app-user"]');

    if (isApp) {
        document.documentElement.classList.add('is-native-app');
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.native-app-only').forEach(function (el) { el.classList.remove('hidden'); });
            document.querySelectorAll('.hide-in-native-app').forEach(function (el) { el.style.display = 'none'; });
        });
    }

    /* ---------- Push notifications ---------- */
    function setupPush() {
        var push = plugin('PushNotifications');
        var enabled = document.querySelector('meta[name="app-push"]');
        if (!push || !enabled || enabled.content !== '1' || !signedIn) return;

        push.addListener('registration', function (t) {
            if (!t || !t.value) return;
            store.set('push-token', t.value);
            fetch('/app/devices', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
                body: JSON.stringify({ token: t.value, platform: 'android' })
            }).catch(function () { /* retried on next page load */ });
        });
        push.addListener('pushNotificationActionPerformed', function (action) {
            var url = action && action.notification && action.notification.data && action.notification.data.url;
            if (url && url.indexOf(location.origin) === 0) location.href = url;
        });

        // Register at most once per app session; Android asks for permission the first time.
        if (store.sget('push-registered')) return;
        store.sset('push-registered', '1');
        Promise.resolve(push.checkPermissions())
            .then(function (p) { return p && p.receive === 'prompt' ? push.requestPermissions() : p; })
            .then(function (p) { if (p && p.receive === 'granted') return push.register(); })
            .catch(function () { /* permission refused: nothing to do */ });
    }

    function forgetPushOnLogout() {
        document.addEventListener('submit', function (e) {
            var form = e.target;
            var token = store.get('push-token');
            if (!isApp || !token || !form || !/\/logout$/.test(form.getAttribute('action') || '')) return;
            try {
                fetch('/app/devices/forget', {
                    method: 'POST',
                    keepalive: true,
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
                    body: JSON.stringify({ token: token })
                });
            } catch (err) { /* ignore */ }
            store.sset('push-registered', '');
        }, true);
    }

    /* ---------- Fingerprint / face lock ---------- */
    var LOCK_AFTER_MS = 60 * 1000;
    var lockEl = null;

    function lockWanted() {
        return isApp && signedIn && (store.get('app-lock') || 'on') === 'on' && !!plugin('NativeBiometric');
    }

    function showLock() {
        if (lockEl) return;
        lockEl = document.createElement('div');
        lockEl.setAttribute('role', 'dialog');
        lockEl.setAttribute('aria-label', 'App locked');
        lockEl.style.cssText = 'position:fixed;inset:0;z-index:2147483000;background:#1e293b;color:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:16px;padding:24px;font-family:system-ui,sans-serif;text-align:center';
        lockEl.innerHTML =
            '<svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#93c5fd" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 018 0v4"/></svg>' +
            '<div style="font-size:20px;font-weight:600">CA Dashboard is locked</div>' +
            '<div style="font-size:15px;color:#cbd5e1;max-width:280px">Use your fingerprint or face to continue.</div>' +
            '<button type="button" data-unlock style="margin-top:8px;border:0;border-radius:12px;padding:14px 28px;font-size:16px;font-weight:600;background:#3b82f6;color:#fff">Unlock</button>';
        lockEl.querySelector('[data-unlock]').addEventListener('click', unlock);
        (document.body || document.documentElement).appendChild(lockEl);
    }

    function hideLock() {
        if (lockEl) { lockEl.remove(); lockEl = null; }
    }

    function unlock() {
        var bio = plugin('NativeBiometric');
        if (!bio) { hideLock(); return; }
        Promise.resolve(bio.isAvailable({ useFallback: true }))
            .then(function (r) {
                // No fingerprint/face/PIN set up on this phone: don't trap the user.
                if (!r || !r.isAvailable) { hideLock(); store.sset('app-unlocked', '1'); return; }
                return bio.verifyIdentity({
                    reason: 'Unlock CA Dashboard',
                    title: 'Unlock CA Dashboard',
                    subtitle: 'Confirm it is you',
                    useFallback: true
                }).then(function () {
                    store.sset('app-unlocked', '1');
                    hideLock();
                });
            })
            .catch(function () { /* cancelled: stay locked, the Unlock button tries again */ });
    }

    function setupLock() {
        if (!lockWanted()) return;
        if (!store.sget('app-unlocked')) {
            showLock();
            unlock();
        }
        var appPlugin = plugin('App');
        if (!appPlugin) return;
        appPlugin.addListener('appStateChange', function (state) {
            if (!state.isActive) {
                store.set('app-hidden-at', String(Date.now()));
                return;
            }
            var hiddenAt = parseInt(store.get('app-hidden-at') || '0', 10);
            if (hiddenAt && Date.now() - hiddenAt > LOCK_AFTER_MS && lockWanted()) {
                store.sset('app-unlocked', '');
                showLock();
                unlock();
            }
        });
    }

    /* ---------- Scan with camera (any phone) ---------- */
    function setupCameraScan() {
        var touch = isApp || window.matchMedia('(pointer: coarse)').matches;
        if (!touch) return;
        document.querySelectorAll('input[type="file"][data-camera-scan]').forEach(function (input) {
            if (input.dataset.cameraScanReady) return;
            input.dataset.cameraScanReady = '1';
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'mt-2 inline-flex items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700';
            btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/><circle cx="12" cy="13" r="4"/></svg>Scan with camera';
            btn.addEventListener('click', function () {
                var accept = input.getAttribute('accept');
                input.setAttribute('accept', 'image/*');
                input.setAttribute('capture', 'environment');
                input.click();
                setTimeout(function () {
                    input.removeAttribute('capture');
                    if (accept === null) input.removeAttribute('accept'); else input.setAttribute('accept', accept);
                }, 1500);
            });
            // Inputs styled as invisible overlays sit inside a drop zone; put the button after the zone.
            var anchor = input.classList.contains('opacity-0') && input.parentElement ? input.parentElement : input;
            anchor.insertAdjacentElement('afterend', btn);
        });
    }

    function start() {
        setupCameraScan();
        if (!isApp) return;
        setupLock();
        setupPush();
        forgetPushOnLogout();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
