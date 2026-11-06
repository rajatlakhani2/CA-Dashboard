{{-- Installable app (home-screen "VouchEx" icon). /sw.js stores no pages; see the note at its top. --}}
<link rel="manifest" href="/site.webmanifest">
<link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="VouchEx">
<script>
    if ('serviceWorker' in navigator && window.isSecureContext) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.getRegistrations().then(function (regs) {
                // Remove the old Workbox worker (any worker that isn't /sw.js).
                regs.forEach(function (r) {
                    var w = r.active || r.waiting || r.installing;
                    if (w && !/\/sw\.js$/.test(new URL(w.scriptURL).pathname)) { r.unregister(); }
                });
                navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(function () {});
            });
        });
    }
</script>
@php
    $viteReady = file_exists(public_path('build/manifest.json'));
@endphp
@if ($viteReady)
    @vite(['resources/css/app.css', 'resources/js/app.js'])
@else
    {{-- Fallback when public/build was not uploaded (prevents 500 from @vite on cPanel) --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: {
            primary: { 600: '#4f46e5', 700: '#4338ca' }
        }}}};
    </script>
    <style>
        body { font-family: 'DM Sans', system-ui, sans-serif; background-color: #e8ecf1; }
        .bg-bg-card { background-color: #fff; }
        .bg-bg-body { background-color: #e8ecf1; }
        .text-text-main { color: #0f172a; }
        .text-text-secondary { color: #64748b; }
        .border-line { border-color: #e2e8f0; }
        .ring-line { --tw-ring-color: #e2e8f0; }
    </style>
@endif
