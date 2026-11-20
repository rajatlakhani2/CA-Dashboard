{{-- Home-screen install (Android and iPhone) and the phone-app helper script. --}}
<link rel="manifest" href="/manifest.webmanifest">
<link rel="apple-touch-icon" href="/app-icons/icon-180.png">
@auth
<meta name="app-user" content="1">
<meta name="app-push" content="{{ app(\App\Services\PushNotificationService::class)->isConfigured() ? '1' : '0' }}">
@endauth
<script defer src="/js/native-app.js?v={{ @filemtime(public_path('js/native-app.js')) ?: 1 }}"></script>
