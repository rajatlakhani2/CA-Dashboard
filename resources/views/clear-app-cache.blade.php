<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Clear old app cache</title>
</head>
<body style="font-family:system-ui;max-width:28rem;margin:3rem auto;padding:0 1rem;">
    <h1>Clear old cache</h1>
    <p id="status">Working…</p>
    <p><a href="{{ route('login') }}">Go to login</a></p>
    <script>
        (async function () {
            const el = document.getElementById('status');
            const lines = [];
            if ('serviceWorker' in navigator) {
                const regs = await navigator.serviceWorker.getRegistrations();
                for (const r of regs) { await r.unregister(); }
                lines.push('Unregistered ' + regs.length + ' service worker(s).');
            }
            if ('caches' in window) {
                const keys = await caches.keys();
                for (const k of keys) { await caches.delete(k); }
                lines.push('Cleared ' + keys.length + ' cache(s).');
            }
            el.innerHTML = lines.join('<br>') + '<br><br><strong>Close this tab.</strong> Open login in Incognito or a new window.';
        })();
    </script>
</body>
</html>
