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
        body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; }
        .bg-bg-card { background-color: #fff; }
        .bg-bg-body { background-color: #f8fafc; }
        .text-text-main { color: #0f172a; }
        .text-text-secondary { color: #64748b; }
        .border-line { border-color: #e2e8f0; }
        .ring-line { --tw-ring-color: #e2e8f0; }
    </style>
@endif
