{{--
    "Install VouchEx app" banner for phones.
    Android/Chrome: uses the browser's install prompt.
    iPhone/iPad (no prompt API): shows the Share → Add to Home Screen steps.
    Hidden when already running as the installed app, or for 30 days after "×".
--}}
<style>body.install-banner-open .demo-tour-fab{display:none!important}</style>
<div id="install-app-banner" hidden
     style="position:fixed;left:12px;right:12px;bottom:calc(68px + env(safe-area-inset-bottom));z-index:60;max-width:30rem;margin:0 auto;"
     role="dialog" aria-labelledby="install-app-title">
    <div style="display:flex;align-items:center;gap:12px;background:#0b1736;color:#e2e8f0;border:1px solid #1e3a8a;border-radius:16px;padding:12px 12px 12px 14px;box-shadow:0 12px 32px rgba(2,6,23,.35);">
        <img src="/icons/vouchex-192.png" alt="" width="40" height="40" style="border-radius:10px;flex:none;">
        <div style="flex:1;min-width:0;">
            <div id="install-app-title" style="font-weight:700;color:#fff;font-size:15px;">Install VouchEx app</div>
            <div id="install-app-text" style="font-size:12.5px;color:#94a3b8;line-height:1.35;">Add to your phone home screen for quick access</div>
        </div>
        <button type="button" id="install-app-go"
                style="flex:none;border:0;border-radius:999px;background:#22c55e;color:#052e16;font-weight:700;font-size:14px;padding:8px 16px;">Install</button>
        <button type="button" id="install-app-close" aria-label="Dismiss"
                style="flex:none;border:0;background:transparent;color:#94a3b8;font-size:22px;line-height:1;padding:4px 6px;">&times;</button>
    </div>
</div>
<script>
(function () {
    var KEY = 'vouchex-install-dismissed-at';
    var banner = document.getElementById('install-app-banner');
    var headerBtn = document.getElementById('pwa-install-btn');
    var goBtn = document.getElementById('install-app-go');
    var text = document.getElementById('install-app-text');
    var deferred = null;

    var standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    var isPhone = window.matchMedia('(max-width: 1023px)').matches;
    var isIos = /iphone|ipad|ipod/i.test(navigator.userAgent) ||
        (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

    function recentlyDismissed() {
        try {
            var at = parseInt(localStorage.getItem(KEY) || '0', 10);
            return at && (Date.now() - at) < 30 * 24 * 3600 * 1000;
        } catch (e) { return false; }
    }
    function show() {
        if (standalone || !isPhone || recentlyDismissed()) return;
        banner.hidden = false;
        document.body.classList.add('install-banner-open');
    }
    function hide() {
        banner.hidden = true;
        document.body.classList.remove('install-banner-open');
    }

    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        deferred = e;
        if (headerBtn) headerBtn.classList.remove('hidden');
        show();
    });

    window.installPWA = function () {
        if (deferred) {
            deferred.prompt();
            deferred.userChoice.then(function (choice) {
                deferred = null;
                if (choice.outcome === 'accepted') {
                    hide();
                    if (headerBtn) headerBtn.classList.add('hidden');
                }
            });
        } else if (isIos) {
            text.innerHTML = 'Tap <strong style="color:#fff">Share</strong> <span aria-hidden="true">⬆︎</span> below, then <strong style="color:#fff">Add to Home Screen</strong>.';
            goBtn.hidden = true;
        }
    };

    window.addEventListener('appinstalled', function () {
        hide();
        if (headerBtn) headerBtn.classList.add('hidden');
    });

    goBtn.addEventListener('click', window.installPWA);
    document.getElementById('install-app-close').addEventListener('click', function () {
        try { localStorage.setItem(KEY, String(Date.now())); } catch (e) {}
        hide();
    });

    // iOS Safari never fires beforeinstallprompt, so offer the manual steps.
    if (isIos && !standalone) {
        goBtn.textContent = 'How?';
        show();
    }
})();
</script>
