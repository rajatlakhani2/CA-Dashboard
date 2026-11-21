<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        // Light is the default; dark only when chosen with the header switch (stored on this device).
        (function () {
            var KEY = 'cadash.theme';
            function apply(t) {
                document.documentElement.dataset.theme = t;
                var m = document.querySelector('meta[name="theme-color"]');
                if (m) m.setAttribute('content', t === 'dark' ? '#0b1120' : '#0c1f4a');
                document.querySelectorAll('[data-theme-toggle]').forEach(function (b) {
                    var label = t === 'dark' ? 'Switch to light mode' : 'Switch to dark mode';
                    b.setAttribute('aria-label', label); b.setAttribute('title', label); b.setAttribute('aria-pressed', t === 'dark');
                });
            }
            var t = 'light';
            try { if (localStorage.getItem(KEY) === 'dark') t = 'dark'; } catch (e) {}
            apply(t);
            document.addEventListener('DOMContentLoaded', function () { apply(document.documentElement.dataset.theme); });
            window.cdToggleTheme = function () {
                var next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
                try { localStorage.setItem(KEY, next); } catch (e) {}
                apply(next);
            };
        })();
    </script>
    <title>{{ $dashboardBrandName ?? \App\Support\Branding::dashboardName() }}</title>

    @include('partials.workspace-theme')

    <!-- PWA / mobile install hints -->
    <meta name="theme-color" content="#0c1f4a">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ $dashboardBrandName ?? \App\Support\Branding::dashboardName() }}">
    @include('partials.app-shell-head')

    <!-- Scripts & Styles -->
    @include('partials.head-assets')
    <script defer src="{{ asset('vendor/alpine-3.14.3.min.js') }}"></script>
    <style>
        /* Premium Scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        /* Sidebar must not paint over the main panel when scrolling */
        html,
        body {
            overflow-x: hidden;
        }

        #sidebar {
            overflow: hidden;
            contain: layout style;
        }

        #sidebar nav {
            overflow-x: hidden;
        }

        /* Stop active/hover menu items from scaling into the content area */
        #sidebar nav a,
        #sidebar nav button {
            transform: none !important;
        }

        #sidebar {
            background: linear-gradient(175deg, var(--vx-sidebar-navy) 0%, var(--vx-sidebar-mid) 48%, var(--vx-sidebar-deep) 100%) !important;
            border-right: 1px solid rgba(148, 163, 184, 0.12);
            box-shadow: 8px 0 32px rgba(0, 0, 0, 0.22);
        }

        .main-shell {
            position: relative;
            z-index: 40;
            min-width: 0;
            max-width: 100%;
            overflow-x: hidden;
            background-color: var(--premium-bg);
            isolation: isolate;
            box-sizing: border-box;
        }

        #sidebar {
            width: var(--sidebar-width);
            max-width: var(--sidebar-width);
        }

        #sidebar nav a.bg-gradient-to-r,
        #sidebar nav a[class*="from-indigo"] {
            background: var(--vx-nav-active-bg) !important;
            border-left-color: var(--vx-nav-active-border) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.08), 0 6px 20px var(--vx-nav-active-shadow) !important;
            transform: none !important;
        }

        #sidebar nav a:not(.bg-gradient-to-r):not([class*="from-indigo"]):hover {
            background: rgba(255, 255, 255, 0.08) !important;
            color: #fff !important;
        }

        @media (max-width: 1023px) {
            .main-shell {
                padding-bottom: 4.5rem;
            }
            .safe-area-pb {
                padding-bottom: env(safe-area-inset-bottom, 0);
            }
        }

        @media (min-width: 1024px) {
            #sidebar {
                z-index: 20 !important;
            }

            .main-shell {
                position: fixed;
                top: 0;
                right: 0;
                bottom: 0;
                left: var(--sidebar-width);
                width: calc(100vw - var(--sidebar-width)) !important;
                max-width: calc(100vw - var(--sidebar-width)) !important;
                margin-left: 0;
                overflow-y: auto;
                overflow-x: hidden;
                box-shadow: -6px 0 20px -6px rgba(15, 23, 42, 0.12);
            }

            .main-shell > header {
                position: sticky;
                top: 0;
                z-index: 50;
                background-color: #ffffff !important;
                backdrop-filter: none;
            }
        }
    </style>
    @stack('head_styles')
    <script>
        // Check for Font Scale
        const appScale = localStorage.getItem('app_scale');
        if (appScale) {
            document.documentElement.style.fontSize = appScale + '%';
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap">
    <link rel="stylesheet" href="/css/phone.css?v={{ @filemtime(public_path('css/phone.css')) ?: 1 }}">
    <link rel="stylesheet" href="/css/cadash-ui.css?v={{ @filemtime(public_path('css/cadash-ui.css')) ?: 1 }}">
</head>

<body class="h-full theme-{{ auth()->user()?->theme ?? 'modern' }} workspace-{{ $workspaceType ?? \App\Support\WorkspaceProfile::current() }}">
    <div class="min-h-full bg-bg-body text-text-main" x-data>
        <div id="sidebar-overlay" class="fixed inset-0 z-40 bg-slate-900/50 lg:hidden" onclick="closeMobileSidebar()" aria-hidden="true"></div>

        <!-- Sidebar -->
        <div class="fixed inset-y-0 left-0 z-30 bg-sidebar text-white transition-transform duration-300 ease-in-out transform flex flex-col overflow-hidden lg:translate-x-0 lg:z-20" id="sidebar">
            <a href="{{ route('app.home') }}" class="cd-brand" aria-label="{{ $dashboardBrandName ?? \App\Support\Branding::dashboardName() }} home">
                <span class="cd-brand-mark"><img src="/brand/ca-mark.png" alt="" width="40" height="30"></span>
                <span class="cd-brand-text">
                    <span class="cd-brand-name">{{ $dashboardBrandName ?? \App\Support\Branding::dashboardName() }}</span>
                    <span class="cd-brand-tag">{{ \App\Support\Branding::dashboardTagline() }}</span>
                </span>
            </a>

            @php
                $user = auth()->user();
                $canManageFirm = $user?->managesFirmModules();
                $isPartner = $user?->isPartner();
                $isArticle = $user?->isArticle();
                $mod = fn (string $key) => $user?->canAccessModule($key) ?? false;
                $showFinance = \App\Support\ModuleGate::hasFinanceModule($user);
            @endphp
            <nav class="flex-1 overflow-y-auto overflow-x-hidden custom-scrollbar" aria-label="Main menu">
                @include('partials.sidebar-nav')
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-shell flex flex-col min-h-screen min-w-0">
            <!-- Header -->
            <header class="bg-white shadow-sm border-b border-gray-100 sticky top-0 z-50">
                <div class="px-3 sm:px-6 lg:px-8 py-2 md:py-4 flex justify-between items-center gap-2 md:gap-3">
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        <button type="button" onclick="openMobileSidebar()" class="lg:hidden flex-shrink-0 p-2 -ml-1 rounded-lg text-slate-600 hover:bg-slate-100 focus:outline-none" aria-label="Open menu">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                        </button>
                        <h2 class="cd-page-title truncate min-w-0">
                            @yield('header', 'Dashboard')
                        </h2>
                        @stack('header_tools')
                    </div>
                    <div class="flex items-center space-x-1 md:space-x-4">
                        @can('viewAny', App\Models\ClientCredential::class)
                        @include('partials.government-portal-buttons')
                        @endcan
                        @include('partials.demo-tour')
                        <!-- Search Trigger -->
                        <div class="hidden md:flex items-center mr-2" x-data @click="$dispatch('open-search')">
                            <div class="relative cursor-text group" data-tour="quick-search">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400 group-hover:text-indigo-500 transition-colors" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-lg leading-5 bg-white/50 backdrop-blur-sm placeholder-gray-500 focus:outline-none focus:bg-white focus:border-indigo-300 transition duration-150 ease-in-out sm:text-xs shadow-sm hover:shadow group-hover:border-indigo-200 text-gray-500 font-medium">
                                    Quick Search (Ctrl+K)
                                </div>
                            </div>
                        </div>

                        <!-- Phone search -->
                        <button type="button" x-data @click="$dispatch('open-search')" class="md:hidden p-2 text-gray-500 hover:text-indigo-600 focus:outline-none" aria-label="Search">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z" /></svg>
                        </button>

                        <!-- PWA Install Button -->
                        <button id="pwa-install-btn" onclick="installPWA()" class="hidden p-2 text-gray-400 hover:text-indigo-600 focus:outline-none transition-colors" title="Install App">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </button>



                        <!-- Light / dark mode (remembered on this device; light is the default) -->
                        <button type="button" class="cd-theme-toggle" onclick="window.cdToggleTheme()" aria-label="Switch to dark mode" title="Switch to dark mode" data-theme-toggle>
                            <svg class="cd-theme-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
                            <svg class="cd-theme-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
                        </button>

                        <!-- Notifications -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="p-2 text-gray-400 hover:text-indigo-600 focus:outline-none transition-colors relative">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                @php
                                    $groupTotal = collect($notificationGroups ?? [])->sum('count');
                                    $unreadCount = auth()->user()->unreadNotifications->count();
                                @endphp
                                @if($unreadCount > 0 || $groupTotal > 0)
                                <span class="absolute top-1.5 right-1.5 min-w-[1.125rem] h-[1.125rem] px-0.5 rounded-full bg-red-500 ring-2 ring-white text-xs font-bold text-white flex items-center justify-center">{{ max($unreadCount, $groupTotal) > 9 ? '9+' : max($unreadCount, $groupTotal) }}</span>
                                @endif
                            </button>

                            <!-- Dropdown -->
                            <div x-show="open" @click.away="open = false" style="display: none;" class="origin-top-right absolute right-0 mt-2 w-80 sm:w-96 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 z-50 max-h-[min(24rem,70vh)] overflow-hidden flex flex-col">
                                <div class="px-4 py-2 border-b border-gray-100 flex justify-between items-center shrink-0">
                                    <h3 class="text-sm font-semibold text-gray-700">Notifications</h3>
                                    @if($unreadCount > 0)
                                    <a href="{{ route('notifications.read.all') }}" class="text-xs text-indigo-600 hover:text-indigo-800">Mark all read</a>
                                    @endif
                                </div>
                                @if(!empty($notificationGroups))
                                <div class="px-3 py-2 border-b border-gray-50 space-y-1 shrink-0">
                                    @foreach($notificationGroups as $group)
                                    <a href="{{ $group['url'] }}" class="flex items-center justify-between rounded-lg px-2 py-1.5 text-sm hover:bg-indigo-50">
                                        <span class="font-medium text-gray-800">{{ $group['label'] }}</span>
                                        <span class="font-black text-indigo-600">{{ $group['count'] }}</span>
                                    </a>
                                    @endforeach
                                </div>
                                @endif
                                <div class="max-h-48 overflow-y-auto flex-1">
                                    @forelse(auth()->user()->unreadNotifications as $notification)
                                    <a href="{{ route('notifications.read', $notification->id) }}" class="block px-4 py-3 hover:bg-gray-50 transition-colors border-b last:border-0 border-gray-50">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 pt-0.5">
                                                <div class="h-8 w-8 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="ml-3 w-0 flex-1">
                                                <p class="text-sm font-medium text-gray-900">
                                                    {{ $notification->data['title'] ?? 'New Notification' }}
                                                </p>
                                                <p class="mt-1 text-xs text-gray-500">
                                                    {{ $notification->data['message'] ?? 'You have a new update.' }}
                                                </p>
                                                <p class="mt-1 text-xs text-gray-400">
                                                    {{ $notification->created_at->diffForHumans() }}
                                                </p>
                                            </div>
                                        </div>
                                    </a>
                                    @empty
                                    <div class="px-4 py-6 text-center text-sm text-gray-500">
                                        No new notifications.
                                    </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="relative" x-data="{ profileOpen: false }">
                            <button type="button" @click="profileOpen = !profileOpen" class="flex items-center space-x-2 group rounded-lg px-2 py-1 hover:bg-gray-50 focus:outline-none">
                                <span class="hidden md:inline text-sm font-medium text-gray-700 group-hover:text-indigo-600 max-w-[140px] truncate">{{ $user?->name }}</span>
                                <div class="h-8 w-8 rounded-full bg-indigo-600 flex items-center justify-center text-white text-sm font-bold group-hover:ring-2 ring-indigo-400">
                                    {{ strtoupper(substr(preg_replace('/^CA\s+/i', '', trim($user?->name ?? 'U')) ?: 'U', 0, 1)) }}
                                </div>
                            </button>
                            <div x-show="profileOpen" @click.away="profileOpen = false" style="display: none;"
                                class="origin-top-right absolute right-0 mt-2 w-52 rounded-lg shadow-lg py-1 bg-white ring-1 ring-black/5 z-50">
                                <div class="px-4 py-2 border-b border-gray-100">
                                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $user?->name }}</p>
                                    <p class="text-xs text-gray-500 capitalize">{{ $user?->role }}</p>
                                </div>
                                @if($mod('settings'))
                                <a href="{{ route('settings.index') }}" data-tour="nav-settings" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Profile &amp; settings</a>
                                @endif
                                <form method="POST" action="{{ route('logout') }}" class="border-t border-gray-100">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 font-medium">
                                        Sign out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="main-content-pad flex-1 py-8 px-4 sm:px-6 lg:px-8 animate-enter w-full min-w-0 max-w-full box-border">
                @if(session('success'))
                <div class="mb-4 bg-green-50 border-l-4 border-green-500 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700">
                                {{ session('success') }}
                            </p>
                        </div>
                    </div>
                </div>
                @endif

                @if(session('warning'))
                <div class="mb-4 bg-yellow-50 border-l-4 border-yellow-500 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                {{ session('warning') }}
                            </p>
                        </div>
                    </div>
                </div>
                @endif

                @if($errors->any())
                <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">There were errors with your submission</h3>
                            <div class="mt-2 text-sm text-red-700">
                                <ul role="list" class="list-disc pl-5 space-y-1">
                                    @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
    @include('partials.idle-logout')
    @include('partials.card-motion')
    @stack('scripts')
    @yield('scripts')

    <script>
        // PWA Install Logic
        let deferredPrompt;
        const installBtn = document.getElementById('pwa-install-btn');

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            // Show the install button
            if (installBtn) {
                installBtn.classList.remove('hidden');
            }
            console.log('PWA Install Triggered');
        });

        window.addEventListener('appinstalled', () => {
            if (installBtn) {
                installBtn.classList.add('hidden');
            }
            deferredPrompt = null;
            console.log('PWA Installed');
        });

        async function installPWA() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const {
                    outcome
                } = await deferredPrompt.userChoice;
                console.log(`User response to the install prompt: ${outcome}`);
                deferredPrompt = null;
                if (outcome === 'accepted' && installBtn) {
                    installBtn.classList.add('hidden');
                }
            }
        }

        // Zen mode was removed; clear any saved state from older versions.
        try { localStorage.removeItem('zen-mode'); } catch (e) {}
    </script>

    @include('partials.mobile-bottom-nav')
    @include('partials.search-modal')
    @include('partials.dashboard-help-chat')
    @include('partials.demo-tour-play')
    @include('partials.demo-tour-overlay')
    @include('partials.portal-error-modal')

    <script>
        function openMobileSidebar() {
            document.getElementById('sidebar')?.classList.add('mobile-open');
            document.getElementById('sidebar-overlay')?.classList.add('visible');
            document.body.classList.add('overflow-hidden');
        }

        function closeMobileSidebar() {
            document.getElementById('sidebar')?.classList.remove('mobile-open');
            document.getElementById('sidebar-overlay')?.classList.remove('visible');
            document.body.classList.remove('overflow-hidden');
        }

        document.querySelectorAll('#sidebar a').forEach((link) => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 1024) {
                    closeMobileSidebar();
                }
            });
        });
    </script>
</body>

</html>