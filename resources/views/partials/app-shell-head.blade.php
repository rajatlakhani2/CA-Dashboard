{{-- Home-screen install (Android and iPhone) and the phone-app helper script. --}}
<link rel="manifest" href="/manifest.webmanifest">
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="48x48" href="/favicon-48x48.png">
<link rel="apple-touch-icon" sizes="180x180" href="/app-icons/icon-180.png">
@auth
<meta name="app-user" content="1">
<meta name="app-push" content="{{ app(\App\Services\PushNotificationService::class)->isConfigured() ? '1' : '0' }}">
@endauth
<script defer src="/js/native-app.js?v={{ @filemtime(public_path('js/native-app.js')) ?: 1 }}"></script>
