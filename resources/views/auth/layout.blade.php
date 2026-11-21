<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') — {{ \App\Support\Branding::dashboardName() }}</title>
    @include('partials.head-assets')
    @include('partials.app-shell-head')
    @include('partials.public-auth-head')
</head>
<body class="pb">
    <main class="pb-auth">
        <div class="pb-auth__card pb-auth__card--legacy">
            @include('partials.public-auth-brand', ['heading' => trim($__env->yieldContent('heading')), 'sub' => trim($__env->yieldContent('subheading'))])
            @yield('content')
            <nav class="pb-auth__links" aria-label="More">
                <a href="{{ route('login') }}">Back to sign in</a>
                <a href="{{ route('privacy') }}">Privacy</a>
                <a href="{{ route('terms') }}">Terms</a>
            </nav>
        </div>
    </main>
</body>
</html>
