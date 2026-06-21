<!DOCTYPE html>
<html lang="en" class="h-full">
@php $theme = $themePreset ?? \App\Support\ThemePreset::slatePro(); @endphp
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') — {{ \App\Support\Branding::dashboardName() }}</title>
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
            <h1 class="mt-3 text-3xl sm:text-4xl font-bold tracking-tight">{{ \App\Support\Branding::DEFAULT_NAME }}</h1>
            <p class="mt-3 text-sm muted max-w-md leading-relaxed">Account recovery for your firm workspace.</p>
        </div>
        <div class="login-form-wrap">
            <div class="login-card">
                <h2 class="text-xl font-bold text-slate-900">@yield('heading')</h2>
                <p class="mt-1 text-sm text-slate-500">@yield('subheading')</p>
                <div class="mt-6">@yield('content')</div>
            </div>
        </div>
    </div>
</body>
</html>
