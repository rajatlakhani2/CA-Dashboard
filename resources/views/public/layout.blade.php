@php
    $brand = \App\Support\Branding::DEFAULT_NAME;
    $onHome = request()->routeIs('landing');
    $sec = fn (string $id) => $onHome ? '#'.$id : route('landing').'#'.$id;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $brand) — {{ $brand }}</title>
    <meta name="description" content="@yield('description', 'CA Dashboard is the private practice-management workspace for chartered accountancy firms: clients, statutory due dates, tasks, billing and team in one place.')">
    <meta name="theme-color" content="#0c1f4a">
    @hasSection('noindex')<meta name="robots" content="noindex">@endif
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/app-icons/icon-180.png">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap">
    <link rel="stylesheet" href="/css/cadash-public.css?v={{ @filemtime(public_path('css/cadash-public.css')) ?: 1 }}">
    @stack('head')
</head>
<body class="pb">
    <header class="pb-header">
        <div class="pb-wrap pb-header__inner">
            <a href="{{ route('landing') }}" class="pb-brand" aria-label="{{ $brand }} home">
                <span class="pb-brand__mark"><img src="/brand/ca-mark.png" alt="" width="33" height="25"></span>
                <span><span class="pb-brand__name">{{ $brand }}</span><span class="pb-brand__tag">For chartered accountancy firms</span></span>
            </a>
            <nav class="pb-nav" aria-label="Page sections">
                <a href="{{ $sec('features') }}">Features</a>
                <a href="{{ $sec('how') }}">How it works</a>
                <a href="{{ $sec('security') }}">Security</a>
                <a href="{{ $sec('faq') }}">FAQ</a>
                <a href="{{ route('about') }}">About</a>
            </nav>
            <div class="pb-header__actions">
                <a href="{{ route('app.download') }}" class="pb-btn pb-btn--outline">Mobile app</a>
                <a href="{{ route('login') }}" class="pb-btn pb-btn--primary">Sign in</a>
                <details class="pb-menu">
                    <summary aria-label="Open menu">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                    </summary>
                    <div class="pb-menu__panel">
                        <a href="{{ $sec('features') }}">Features</a>
                        <a href="{{ $sec('how') }}">How it works</a>
                        <a href="{{ $sec('security') }}">Security</a>
                        <a href="{{ $sec('faq') }}">FAQ</a>
                        <a href="{{ route('about') }}">About</a>
                        <a href="{{ route('app.download') }}">Mobile app</a>
                    </div>
                </details>
            </div>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <footer class="pb-footer">
        <div class="pb-wrap">
            <div class="pb-footer__grid">
                <div>
                    <a href="{{ route('landing') }}" class="pb-brand">
                        <span class="pb-brand__mark"><img src="/brand/ca-mark.png" alt="" width="33" height="25"></span>
                        <span class="pb-brand__name">{{ $brand }}</span>
                    </a>
                    <p>A private workspace for CA firms and their teams. Access is by invitation from your firm.</p>
                </div>
                <div>
                    <h4>Product</h4>
                    <ul>
                        <li><a href="{{ $sec('features') }}">Features</a></li>
                        <li><a href="{{ $sec('how') }}">How it works</a></li>
                        <li><a href="{{ $sec('security') }}">Security</a></li>
                        <li><a href="{{ route('app.download') }}">Mobile app</a></li>
                    </ul>
                </div>
                <div>
                    <h4>Company</h4>
                    <ul>
                        <li><a href="{{ route('about') }}">About</a></li>
                        <li><a href="{{ route('privacy') }}">Privacy Policy</a></li>
                        <li><a href="{{ route('terms') }}">Terms of Service</a></li>
                        <li><a href="{{ route('account.delete-info') }}">Delete your account</a></li>
                    </ul>
                </div>
                <div>
                    <h4>Support</h4>
                    <ul>
                        <li><a href="{{ $sec('faq') }}">Common questions</a></li>
                        <li><a href="mailto:rajatlakhani2@gmail.com">rajatlakhani2@gmail.com</a></li>
                        <li><a href="mailto:rajpopatpriyank@gmail.com">rajpopatpriyank@gmail.com</a></li>
                    </ul>
                    <p>For access or password help, first ask your firm’s partner or administrator.</p>
                </div>
            </div>
            <div class="pb-footer__legal">
                <span>© {{ date('Y') }} {{ $brand }}. All rights reserved.</span>
                <span>An independent practice-management tool; not affiliated with or endorsed by ICAI.</span>
            </div>
        </div>
    </footer>
</body>
</html>
