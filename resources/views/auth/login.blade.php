<!DOCTYPE html>
<html lang="en" class="h-full">
@php $theme = $themePreset ?? \App\Support\ThemePreset::slatePro(); @endphp

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign in — {{ $dashboardBrandName ?? \App\Support\Branding::dashboardName() }}</title>
    @include('partials.workspace-theme')
    @include('partials.head-assets')
    <style>
        body { font-family: {!! $theme['font_stack'] !!}; }
        .login-shell { min-height: 100vh; display: grid; grid-template-columns: 1fr; }
        @media (min-width: 1024px) { .login-shell { grid-template-columns: 1.05fr 1fr; } }
        .login-brand {
            background: {{ $theme['login_brand_gradient'] }};
            color: #fff;
            padding: 2.5rem 2rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .login-brand .muted { color: {{ $theme['login_brand_muted'] }}; }
        .login-form-wrap {
            background: {{ $theme['bg'] }};
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.25rem;
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border: 1px solid {{ $theme['border'] }};
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(15, 23, 42, 0.08);
            padding: 2rem;
        }
        .login-input {
            display: block;
            width: 100%;
            border-radius: 12px;
            border: 1px solid {{ $theme['border'] }};
            background: {{ $theme['bg'] }};
            padding: 0.65rem 0.85rem;
            font-size: 0.875rem;
            color: {{ $theme['text'] }};
        }
        .login-input:focus {
            outline: none;
            border-color: {{ $theme['accent'] }};
            box-shadow: 0 0 0 3px {{ $theme['login_focus_ring'] }};
            background: #fff;
        }
        .login-btn {
            width: 100%;
            border-radius: 12px;
            background: {{ $theme['login_btn_gradient'] }};
            color: #fff;
            font-weight: 600;
            font-size: 0.875rem;
            padding: 0.7rem 1rem;
            box-shadow: 0 8px 20px {{ $theme['login_btn_shadow'] }};
        }
        .login-btn:hover { filter: brightness(1.05); }
        .login-link { color: {{ $theme['login_link'] }}; }
    </style>
</head>

<body class="h-full">
    <div class="login-shell">
        <div class="login-brand">
            <p class="text-[10px] font-bold uppercase tracking-[0.2em] muted">Secure workspace</p>
            <h1 class="mt-3 text-3xl sm:text-4xl font-bold tracking-tight">{{ $dashboardBrandName ?? \App\Support\Branding::DEFAULT_NAME }}</h1>
            <p class="mt-3 text-sm muted max-w-md leading-relaxed">
                {{ $dashboardBrandTagline ?: 'Each firm has isolated data. Sign in with your workspace ID and email.' }}
            </p>
            <ul class="mt-8 space-y-3 text-sm muted max-w-sm">
                <li class="flex items-center gap-2"><span class="text-emerald-300">✓</span> Role-based module access</li>
                <li class="flex items-center gap-2"><span class="text-emerald-300">✓</span> Clients, compliance & finance in one place</li>
                <li class="flex items-center gap-2"><span class="text-emerald-300">✓</span> Built for CA firms and finance teams</li>
            </ul>
        </div>

        <div class="login-form-wrap">
            <div class="login-card">
                <h2 class="text-xl font-bold text-slate-900">Sign in</h2>
                <p class="mt-1 text-sm text-slate-500">Workspace ID + your account email</p>

                @if(session('session_expired') || session('status'))
                <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                    {{ session('status') ?: 'Your session expired. The form was refreshed — please sign in again.' }}
                </div>
                @endif

                <form class="mt-6 space-y-4" action="{{ route('login') }}" method="POST" id="login-form">
                    @csrf

                    <div>
                        <label for="workspace" class="block text-xs font-semibold text-slate-600 mb-1.5">Workspace ID</label>
                        <input id="workspace" name="workspace" type="text" required autocomplete="organization"
                            value="{{ old('workspace', $workspace ?? '') }}"
                            placeholder="e.g. rla"
                            class="login-input">
                    </div>

                    <div>
                        <label for="email" class="block text-xs font-semibold text-slate-600 mb-1.5">Email</label>
                        <input id="email" name="email" type="email" required autocomplete="email"
                            value="{{ old('email') }}"
                            class="login-input">
                    </div>

                    <div>
                        <label for="password" class="block text-xs font-semibold text-slate-600 mb-1.5">Password</label>
                        <input id="password" name="password" type="password" required autocomplete="current-password"
                            class="login-input">
                    </div>

                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input id="remember" name="remember" type="checkbox" value="1" class="rounded" style="accent-color: {{ $theme['accent'] }}">
                        Remember me
                    </label>

                    @if ($errors->any())
                    <div class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                        <ul class="list-disc pl-4 space-y-0.5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                    @endif

                    <button type="submit" class="login-btn">Sign in</button>
                </form>

                <p class="mt-5 text-center text-xs text-slate-500">
                    New firm? <a href="{{ route('register.organization') }}" class="font-semibold login-link hover:opacity-80">Create a workspace</a>
                </p>
            </div>
        </div>
    </div>
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
