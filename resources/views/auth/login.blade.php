<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign in — {{ $dashboardBrandName ?? \App\Support\Branding::dashboardName() }}</title>
    @include('partials.app-shell-head')
    @include('partials.public-auth-head')
</head>
<body class="pb">
    <main class="pb-auth">
        <div class="pb-auth__card">
            @include('partials.public-auth-brand', ['heading' => 'Sign in to ' . ($dashboardBrandName ?? \App\Support\Branding::dashboardName()), 'sub' => 'Use your firm’s workspace ID and your account email'])

            @if(session('session_expired') || session('status'))
            <div class="pb-auth__status">{{ session('status') ?: 'Your session expired. The form was refreshed — please sign in again.' }}</div>
            @endif

            @if ($errors->any())
            <div class="pb-auth__errors" role="alert">
                @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
            @endif

            <form action="{{ route('login') }}" method="POST" id="login-form">
                @csrf
                <div class="pb-field">
                    <label for="workspace">Workspace ID</label>
                    <input id="workspace" name="workspace" type="text" required autocomplete="organization" autocapitalize="none"
                        value="{{ old('workspace', $workspace ?? '') }}" placeholder="e.g. rla">
                    <div class="pb-field__hint">Your firm gives you this when your account is created.</div>
                </div>
                <div class="pb-field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" required autocomplete="email" value="{{ old('email') }}">
                </div>
                <div class="pb-field">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password">
                </div>
                <div class="pb-auth__row">
                    <label><input id="remember" name="remember" type="checkbox" value="1"> Remember me</label>
                    <a href="{{ route('password.request', ['workspace' => old('workspace', $workspace ?? '')]) }}">Forgot password?</a>
                </div>
                <button type="submit" class="pb-btn pb-btn--primary pb-btn--lg">Sign in</button>
            </form>

            <p class="pb-auth__foot">New firm? <a href="{{ route('register.organization') }}">Create a workspace</a></p>
            <p class="pb-auth__foot hide-in-native-app" style="margin-top:6px;">On your phone? <a href="{{ route('app.download') }}">Get the mobile app</a></p>

            <nav class="pb-auth__links hide-in-native-app" aria-label="More">
                <a href="{{ route('landing') }}">Home</a>
                <a href="{{ route('about') }}">About</a>
                <a href="{{ route('privacy') }}">Privacy</a>
                <a href="{{ route('terms') }}">Terms</a>
            </nav>
        </div>
    </main>
    <script>
        (function () {
            var openedAt = Date.now();
            var maxIdleMs = {{ (int) config('session.lifetime', 120) * 60 * 1000 }};
            var form = document.getElementById('login-form');
            if (!form) return;
            form.addEventListener('submit', function (e) {
                if (Date.now() - openedAt > maxIdleMs - 60000) {
                    e.preventDefault();
                    window.location.reload();
                }
            });
        })();
    </script>
</body>
</html>
