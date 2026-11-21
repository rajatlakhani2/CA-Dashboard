@php
    $brand = \App\Support\Branding::DEFAULT_NAME;
    $onHome = request()->routeIs('landing');
    $sec = fn (string $id) => $onHome ? '#'.$id : route('landing').'#'.$id;
    $ico = fn (string $paths) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$paths.'</svg>';
    // Sections of the front page, used by the floating menu (laptop) and the phone drawer.
    $navLinks = [
        ['id' => 'roles', 'label' => 'Who it’s for', 'icon' => $ico('<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>')],
        ['id' => 'features', 'label' => 'Features & modules', 'icon' => $ico('<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>')],
        ['id' => 'how', 'label' => 'How it works', 'icon' => $ico('<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>')],
        ['id' => 'security', 'label' => 'Security', 'icon' => $ico('<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/>')],
        ['id' => 'mobile', 'label' => 'Mobile app', 'icon' => $ico('<rect x="5" y="2" width="14" height="20" rx="2"/><path d="M12 18h.01"/>')],
        ['id' => 'faq', 'label' => 'Help & FAQs', 'icon' => $ico('<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/>')],
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $brand) — {{ $brand }}</title>
    <meta name="description" content="@yield('description', 'CA Dashboard is practice-management software for chartered accountancy firms: clients, statutory due dates, tasks, billing and the team in one place, on laptop and phone.')">
    <meta name="theme-color" content="#0c1f4a">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/app-icons/icon-180.png">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap">
    <link rel="stylesheet" href="/css/cadash-public.css?v={{ @filemtime(public_path('css/cadash-public.css')) ?: 1 }}">
    <script>document.documentElement.classList.add('pb-js');</script>
    @stack('head')
</head>
<body class="pb">
    <header class="pb-header" id="pb-header">
        <div class="pb-wrap pb-header__inner">
            <a href="{{ route('landing') }}" class="pb-brand" aria-label="{{ $brand }} home">
                <span class="pb-brand__mark"><img src="/brand/ca-mark.png" alt="" width="33" height="25"></span>
                <span><span class="pb-brand__name">{{ $brand }}</span><span class="pb-brand__tag">Practice management for CA firms</span></span>
            </a>
            <div class="pb-header__actions">
                <a href="{{ route('login') }}" class="pb-btn pb-btn--outline pb-hide-sm">Sign in</a>
                <a href="{{ route('register.organization') }}" class="pb-btn pb-btn--primary pb-hide-sm">Create workspace</a>
                <a href="{{ route('login') }}" class="pb-btn pb-btn--primary pb-show-sm">Sign in</a>
                <button type="button" class="pb-menu-btn" id="pb-menu-btn" aria-label="Open menu" aria-expanded="false" aria-controls="pb-drawer">
                    <svg class="pb-menu-btn__open" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                    <svg class="pb-menu-btn__close" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
    </header>

    {{-- Phones and tablets: slide-in drawer with the page sections --}}
    <div class="pb-drawer-backdrop" id="pb-drawer-backdrop" hidden></div>
    <nav class="pb-drawer" id="pb-drawer" aria-label="Page sections" hidden>
        @foreach($navLinks as $l)
        <a href="{{ $sec($l['id']) }}" data-pb-close><span class="pb-drawer__icon">{!! $l['icon'] !!}</span>{{ $l['label'] }}</a>
        @endforeach
        <a href="{{ route('about') }}" data-pb-close><span class="pb-drawer__icon">{!! $ico('<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>') !!}</span>About</a>
        <div class="pb-drawer__actions">
            <a href="{{ route('login') }}" class="pb-btn pb-btn--primary pb-btn--lg">Sign in</a>
            <a href="{{ route('register.organization') }}" class="pb-btn pb-btn--outline pb-btn--lg">Create your firm’s workspace</a>
        </div>
    </nav>

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
                    <p>Practice-management software for chartered accountancy firms of every size, from sole practitioners to multi-branch firms.</p>
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
                        <li><a href="{{ $sec('faq') }}">Help &amp; FAQs</a></li>
                        <li><a href="mailto:rajatlakhani2@gmail.com">rajatlakhani2@gmail.com</a></li>
                        <li><a href="mailto:rajpopatpriyank@gmail.com">rajpopatpriyank@gmail.com</a></li>
                    </ul>
                </div>
            </div>
            <div class="pb-footer__legal">
                <span>© {{ date('Y') }} {{ $brand }}. All rights reserved.</span>
                <span>An independent practice-management tool; not affiliated with or endorsed by ICAI.</span>
            </div>
        </div>
    </footer>

    @if($onHome)
    {{-- Laptop: floating section menu on the right (same as VouchEx). Hover or click the logo. --}}
    <nav class="pb-quicknav" id="pb-quicknav" aria-label="Page sections">
        <button type="button" class="pb-quicknav__trigger" id="pb-quicknav-trigger" aria-expanded="false" aria-controls="pb-quicknav-list">
            <span class="pb-quicknav__text pb-quicknav__text--trigger">Explore CA Dashboard
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
            </span>
            <span class="pb-quicknav__logo"><img src="/brand/ca-mark.png" alt="" draggable="false"></span>
        </button>
        <ul class="pb-quicknav__list" id="pb-quicknav-list">
            @foreach($navLinks as $i => $l)
            <li style="--i: {{ $i }}">
                <a href="#{{ $l['id'] }}" class="pb-quicknav__item">
                    <span class="pb-quicknav__text">{{ $l['label'] }}</span>
                    <span class="pb-quicknav__icon">{!! $l['icon'] !!}</span>
                </a>
            </li>
            @endforeach
        </ul>
    </nav>

    {{-- Bottom left: step down through the sections --}}
    <button type="button" class="pb-scroll-hint" id="pb-scroll-hint" aria-label="Scroll down to the next section">
        <span class="pb-scroll-hint__icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m7 6 5 5 5-5"/><path d="m7 13 5 5 5-5"/></svg></span>
        <span class="pb-scroll-hint__text" id="pb-scroll-hint-text">Scroll to explore</span>
    </button>
    @endif

    <button type="button" class="pb-scroll-top" id="pb-scroll-top" aria-label="Back to top" hidden>
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 7-7 7 7"/><path d="M12 19V5"/></svg>
    </button>

    <script>
    (function () {
        var header = document.getElementById('pb-header');
        var top = document.getElementById('pb-scroll-top');
        var hint = document.getElementById('pb-scroll-hint');
        var hintText = document.getElementById('pb-scroll-hint-text');
        var stops = @json(array_column($navLinks, 'id'));

        function onScroll() {
            var y = window.scrollY;
            header.classList.toggle('is-solid', y > 8);
            top.hidden = y < 400;
            if (hint) {
                var nearBottom = y + window.innerHeight >= document.documentElement.scrollHeight - 320;
                hint.hidden = nearBottom;
                hintText.textContent = y < 120 ? 'Scroll to explore' : 'Next section';
            }
        }
        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll);
        onScroll();
        top.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });
        if (hint) hint.addEventListener('click', function () {
            var next = stops.map(function (id) { return document.getElementById(id); })
                .find(function (el) { return el && el.getBoundingClientRect().top > 110; });
            if (next) next.scrollIntoView({ behavior: 'smooth', block: 'start' });
            else window.scrollBy({ top: window.innerHeight * 0.8, behavior: 'smooth' });
        });

        // Floating menu: hover opens it on laptops; click/tap toggles; Esc or outside click closes.
        var qn = document.getElementById('pb-quicknav');
        if (qn) {
            var trig = document.getElementById('pb-quicknav-trigger');
            var setOpen = function (on) { qn.classList.toggle('is-open', on); trig.setAttribute('aria-expanded', on ? 'true' : 'false'); };
            trig.addEventListener('click', function () { setOpen(!qn.classList.contains('is-open')); });
            qn.addEventListener('mouseleave', function () { setOpen(false); });
            document.addEventListener('click', function (e) { if (!qn.contains(e.target)) setOpen(false); });
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setOpen(false); });
            qn.querySelectorAll('a').forEach(function (a) { a.addEventListener('click', function () { setOpen(false); }); });
        }

        // Phone drawer
        var btn = document.getElementById('pb-menu-btn'), drawer = document.getElementById('pb-drawer'), backdrop = document.getElementById('pb-drawer-backdrop');
        function drawerOpen(on) {
            drawer.hidden = !on; backdrop.hidden = !on;
            btn.classList.toggle('is-open', on);
            btn.setAttribute('aria-expanded', on ? 'true' : 'false');
            btn.setAttribute('aria-label', on ? 'Close menu' : 'Open menu');
            document.body.style.overflow = on ? 'hidden' : '';
        }
        btn.addEventListener('click', function () { drawerOpen(drawer.hidden); });
        backdrop.addEventListener('click', function () { drawerOpen(false); });
        drawer.querySelectorAll('[data-pb-close]').forEach(function (a) { a.addEventListener('click', function () { drawerOpen(false); }); });

        // Cards fade up as they scroll into view (skipped when the device asks for reduced motion).
        var cards = document.querySelectorAll('[data-reveal]');
        if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            cards.forEach(function (c) { c.classList.add('is-in'); });
        } else {
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); } });
            }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
            cards.forEach(function (c) { io.observe(c); });
        }
    })();
    </script>
</body>
</html>
