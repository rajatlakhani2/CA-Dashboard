<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $dashboardBrandName ?? \App\Support\Branding::dashboardName() }}</title>

    @include('partials.workspace-theme')

    <!-- PWA / mobile install hints -->
    <meta name="theme-color" content="{{ ($themePreset ?? \App\Support\ThemePreset::forWorkspaceType())['theme_color'] }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ $dashboardBrandName ?? \App\Support\Branding::dashboardName() }}">
    <link rel="apple-touch-icon" href="/favicon.ico">

    <!-- Scripts & Styles -->
    @include('partials.head-assets')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
    <style>
        /* Zen Mode */
        body.zen-mode #sidebar {
            transform: translateX(-100%);
        }

        body.zen-mode .main-shell {
            left: 0 !important;
            margin-left: 0 !important;
            width: 100% !important;
        }

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
        // Check local storage for Zen Mode
        if (localStorage.getItem('zen-mode') === 'true') {
            document.documentElement.classList.add('zen-mode'); // Use documentElement for immediate effect if possible, but body is where class is active
            window.addEventListener('DOMContentLoaded', () => document.body.classList.add('zen-mode'));
        }

        // Check for Font Scale
        const appScale = localStorage.getItem('app_scale');
        if (appScale) {
            document.documentElement.style.fontSize = appScale + '%';
        }
    </script>
</head>

<body class="h-full theme-{{ auth()->user()?->theme ?? 'modern' }} workspace-{{ $workspaceType ?? \App\Support\WorkspaceProfile::current() }}">
    <div class="min-h-full bg-bg-body text-text-main" x-data>
        <div id="sidebar-overlay" class="fixed inset-0 z-40 bg-slate-900/50 lg:hidden" onclick="closeMobileSidebar()" aria-hidden="true"></div>

        <!-- Sidebar -->
        <div class="fixed inset-y-0 left-0 z-30 bg-sidebar text-white transition-transform duration-300 ease-in-out transform flex flex-col overflow-hidden lg:translate-x-0 lg:z-20" id="sidebar">
            <div class="flex-shrink-0 flex items-center justify-center h-16 bg-white/10 shadow-md">
                <h1 class="text-lg font-bold tracking-wide text-center px-2 leading-tight">{{ $dashboardBrandName ?? \App\Support\Branding::dashboardName() }}</h1>
            </div>

            <nav class="flex-1 mt-5 px-4 space-y-6 overflow-y-auto overflow-x-hidden custom-scrollbar pb-10">
                @php
                    $user = auth()->user();
                    $canManageFirm = $user?->managesFirmModules();
                    $isPartner = $user?->isPartner();
                    $isArticle = $user?->isArticle();
                    $mod = fn (string $key) => $user?->canAccessModule($key) ?? false;
                    $showFinance = \App\Support\ModuleGate::hasFinanceModule($user);
                @endphp

                @if($isArticle)
                <!-- Article / Clerk Custom Menu -->
                <div class="space-y-1">
                    <p class="px-4 text-[10px] font-extrabold text-indigo-300/40 uppercase tracking-widest mb-2 select-none">
                        My Work
                    </p>
                    @if($mod('tasks'))
                    <a href="{{ route('tasks.my-day') }}" class="group flex items-center py-3 text-sm font-bold rounded-xl transition-all duration-200 {{ request()->routeIs('tasks.my-day') ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/30 border-l-4 border-indigo-400 pl-3 pr-4 scale-[1.01]' : 'text-slate-400 hover:bg-white/5 hover:text-white hover:translate-x-1 pl-4 pr-4' }}">
                        <svg class="mr-3 flex-shrink-0 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                        My Day
                    </a>
                    <a href="{{ route('tasks.index') }}" class="group flex items-center py-3 text-sm font-bold rounded-xl transition-all duration-200 {{ request()->routeIs('tasks.index') ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/30 border-l-4 border-indigo-400 pl-3 pr-4 scale-[1.01]' : 'text-slate-400 hover:bg-white/5 hover:text-white hover:translate-x-1 pl-4 pr-4' }}">
                        <svg class="mr-3 flex-shrink-0 h-5 w-5 {{ request()->routeIs('tasks.index') ? 'text-white' : 'text-slate-500 group-hover:text-white' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        My Tasks
                    </a>
                    @endif
                    @can('create', App\Models\Client::class)
                    <a href="{{ route('clients.create') }}" class="group flex items-center py-3 text-sm font-bold rounded-xl transition-all duration-200 {{ request()->routeIs('clients.create') ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/30 border-l-4 border-indigo-400 pl-3 pr-4 scale-[1.01]' : 'text-slate-400 hover:bg-white/5 hover:text-white hover:translate-x-1 pl-4 pr-4' }}">
                        <svg class="mr-3 flex-shrink-0 h-5 w-5 {{ request()->routeIs('clients.create') ? 'text-white' : 'text-slate-500 group-hover:text-white' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Submit Client
                    </a>
                    @endcan
                </div>
                @else
                
                @if($mod('dashboard') || $isPartner)
                <!-- Command Centre -->
                <div class="space-y-1">
                    <p class="px-4 text-[10px] font-extrabold text-indigo-300/40 uppercase tracking-widest mb-2 select-none">Command Centre</p>
                    @if($mod('tasks'))
                    <a href="{{ route('tasks.my-day') }}" class="group flex items-center py-3 text-sm font-bold rounded-xl transition-all duration-200 {{ request()->routeIs('tasks.my-day') ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/30 border-l-4 border-indigo-400 pl-3 pr-4 scale-[1.01]' : 'text-slate-400 hover:bg-white/5 hover:text-white hover:translate-x-1 pl-4 pr-4' }}">
                        <svg class="mr-3 flex-shrink-0 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                        My Day
                    </a>
                    @endif
                    @if($mod('dashboard'))
                    <a href="{{ route('dashboard') }}" data-tour="nav-dashboard" class="group flex items-center py-3 text-sm font-bold rounded-xl transition-all duration-200 {{ request()->routeIs('dashboard') ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/30 border-l-4 border-indigo-400 pl-3 pr-4 scale-[1.01]' : 'text-slate-400 hover:bg-white/5 hover:text-white hover:translate-x-1 pl-4 pr-4' }}">
                        <svg class="mr-3 flex-shrink-0 h-5 w-5 {{ request()->routeIs('dashboard') ? 'text-white' : 'text-slate-500 group-hover:text-white' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Command Centre
                    </a>
                    @endif
                </div>
                @endif

                @if($mod('clients') || $mod('credentials') || $mod('smart_documents'))
                <!-- Clients -->
                <div class="pt-4 border-t border-white/5 space-y-1">
                    <p class="px-4 text-[10px] font-extrabold text-indigo-300/40 uppercase tracking-widest mb-2 select-none">
                        Clients
                    </p>
                    @if($mod('clients'))
                    <a href="{{ route('clients.index') }}" data-tour="nav-clients" class="group flex items-center py-3 text-sm font-bold rounded-xl transition-all duration-200 {{ request()->routeIs('clients.*') ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/30 border-l-4 border-indigo-400 pl-3 pr-4 scale-[1.01]' : 'text-slate-400 hover:bg-white/5 hover:text-white hover:translate-x-1 pl-4 pr-4' }}">
                        <svg class="mr-3 flex-shrink-0 h-5 w-5 {{ request()->routeIs('clients.*') ? 'text-white' : 'text-slate-500 group-hover:text-white' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        Clients
                    </a>
                    @endif
                    @if($mod('credentials'))
                    <a href="{{ route('credentials.index') }}" class="group flex items-center py-3 text-sm font-bold rounded-xl transition-all duration-200 {{ request()->routeIs('credentials.*') ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/30 border-l-4 border-indigo-400 pl-3 pr-4 scale-[1.01]' : 'text-slate-400 hover:bg-white/5 hover:text-white hover:translate-x-1 pl-4 pr-4' }}">
                        <svg class="mr-3 flex-shrink-0 h-5 w-5 {{ request()->routeIs('credentials.*') ? 'text-white' : 'text-slate-500 group-hover:text-white' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        Passwords
                    </a>
                    @endif
                    @if($canManageFirm)
                    <a href="{{ route('document-ingestions.index') }}" class="group flex items-center py-3 text-sm font-bold rounded-xl transition-all duration-200 {{ request()->routeIs('document-ingestions.*') ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/30 border-l-4 border-indigo-400 pl-3 pr-4 scale-[1.01]' : 'text-slate-400 hover:bg-white/5 hover:text-white hover:translate-x-1 pl-4 pr-4' }}">
                        <svg class="mr-3 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        Doc review
                    </a>
                    @endif
                    @if($mod('smart_documents'))
                    <a href="{{ route('smart-documents.index') }}" class="group flex items-center py-3 text-sm font-bold rounded-xl transition-all duration-200 {{ request()->routeIs('smart-documents.*') ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/30 border-l-4 border-indigo-400 pl-3 pr-4 scale-[1.01]' : 'text-slate-400 hover:bg-white/5 hover:text-white hover:translate-x-1 pl-4 pr-4' }}">
                        <svg class="mr-3 flex-shrink-0 h-5 w-5 {{ request()->routeIs('smart-documents.*') ? 'text-white' : 'text-slate-500 group-hover:text-white' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                        Smart Archive
                    </a>
                    @endif
                </div>
                @endif

                @if($mod('tasks') || $mod('staff'))
                <!-- 3. WORK MANAGEMENT -->
                <div class="pt-4 border-t border-white/5 space-y-1">
                    <p class="px-4 text-[10px] font-extrabold text-indigo-300/40 uppercase tracking-widest mb-2 select-none">
                        Work
                    </p>
                    @if($mod('tasks'))
                    <a href="{{ route('tasks.my-day') }}" class="group flex items-center py-3 text-sm font-bold rounded-xl transition-all duration-200 {{ request()->routeIs('tasks.my-day') ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/30 border-l-4 border-indigo-400 pl-3 pr-4 scale-[1.01]' : 'text-slate-400 hover:bg-white/5 hover:text-white hover:translate-x-1 pl-4 pr-4' }}">
                        <svg class="mr-3 flex-shrink-0 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                        My Day
                    </a>
                    <a href="{{ route('tasks.index') }}" data-tour="nav-tasks" class="group flex items-center py-3 text-sm font-bold rounded-xl transition-all duration-200 {{ request()->routeIs('tasks.index') || request()->routeIs('tasks.create') || request()->routeIs('tasks.edit') ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/30 border-l-4 border-indigo-400 pl-3 pr-4 scale-[1.01]' : 'text-slate-400 hover:bg-white/5 hover:text-white hover:translate-x-1 pl-4 pr-4' }}">
                        <svg class="mr-3 flex-shrink-0 h-5 w-5 {{ request()->routeIs('tasks.*') ? 'text-white' : 'text-slate-500 group-hover:text-white' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        Tasks
                    </a>
                    @endif
                    @if($mod('staff'))
                    <a href="{{ route('staff.index') }}" class="group flex items-center py-3 text-sm font-bold rounded-xl transition-all duration-200 {{ request()->routeIs('staff.*') ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/30 border-l-4 border-indigo-400 pl-3 pr-4 scale-[1.01]' : 'text-slate-400 hover:bg-white/5 hover:text-white hover:translate-x-1 pl-4 pr-4' }}">
                        <svg class="mr-3 flex-shrink-0 h-5 w-5 {{ request()->routeIs('staff.*') ? 'text-white' : 'text-slate-500 group-hover:text-white' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Staff Directory
                    </a>
                    @endif
                    @if($canManageFirm && $mod('tasks'))
                    <a href="{{ route('workload.index') }}" data-tour="nav-workload" class="group flex items-center py-3 text-sm font-bold rounded-xl transition-all duration-200 {{ request()->routeIs('workload.*') ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/30 border-l-4 border-indigo-400 pl-3 pr-4 scale-[1.01]' : 'text-slate-400 hover:bg-white/5 hover:text-white hover:translate-x-1 pl-4 pr-4' }}">
                        <svg class="mr-3 flex-shrink-0 h-5 w-5 {{ request()->routeIs('workload.*') ? 'text-white' : 'text-slate-500 group-hover:text-white' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 0v10" />
                        </svg>
                        Workload Planner
                    </a>
                    @endif
                    @if($mod('tasks'))
                    <a href="{{ route('time-entries.index') }}" class="group flex items-center py-3 text-sm font-bold rounded-xl transition-all duration-200 {{ request()->routeIs('time-entries.*') ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/30 border-l-4 border-indigo-400 pl-3 pr-4 scale-[1.01]' : 'text-slate-400 hover:bg-white/5 hover:text-white hover:translate-x-1 pl-4 pr-4' }}">
                        <svg class="mr-3 flex-shrink-0 h-5 w-5 {{ request()->routeIs('time-entries.*') ? 'text-white' : 'text-slate-500 group-hover:text-white' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Time Tracking
                    </a>
                    @endif
                    @if($mod('staff') && $canManageFirm)
                    <a href="{{ route('leaves.index') }}" class="group flex items-center py-3 text-sm font-bold rounded-xl transition-all duration-200 {{ request()->routeIs('leaves.*') ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/30 border-l-4 border-indigo-400 pl-3 pr-4 scale-[1.01]' : 'text-slate-400 hover:bg-white/5 hover:text-white hover:translate-x-1 pl-4 pr-4' }}">
                        <svg class="mr-3 flex-shrink-0 h-5 w-5 {{ request()->routeIs('leaves.*') ? 'text-white' : 'text-slate-500 group-hover:text-white' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Leaves
                    </a>
                    @endif
                </div>
                @endif

                @if($mod('service_dues') || $mod('personal_renewals') || $mod('dsc'))
                <!-- 4. COMPLIANCE -->
                <div class="pt-4 border-t border-white/5 space-y-1">
                    <p class="px-4 text-[10px] font-extrabold text-indigo-300/40 uppercase tracking-widest mb-2 select-none">
                        Compliance
                    </p>
                    @if($mod('service_dues'))
                    <a href="{{ route('service-dues.index') }}" data-tour="nav-reminders" class="group flex items-center py-3 text-sm font-bold rounded-xl transition-all duration-200 {{ request()->routeIs('service-dues.*') ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/30 border-l-4 border-indigo-400 pl-3 pr-4 scale-[1.01]' : 'text-slate-400 hover:bg-white/5 hover:text-white hover:translate-x-1 pl-4 pr-4' }}">
                        <svg class="mr-3 flex-shrink-0 h-5 w-5 {{ request()->routeIs('service-dues.*') ? 'text-white' : 'text-slate-500 group-hover:text-white' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Reminders
                    </a>
                    @endif
                    @if($mod('personal_renewals'))
                    <a href="{{ route('personal-renewals.index') }}" data-tour="nav-personal-renewals" class="group flex items-center py-3 text-sm font-bold rounded-xl transition-all duration-200 {{ request()->routeIs('personal-renewals.*') ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/30 border-l-4 border-indigo-400 pl-3 pr-4 scale-[1.01]' : 'text-slate-400 hover:bg-white/5 hover:text-white hover:translate-x-1 pl-4 pr-4' }}">
                        <svg class="mr-3 flex-shrink-0 h-5 w-5 {{ request()->routeIs('personal-renewals.*') ? 'text-white' : 'text-slate-500 group-hover:text-white' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Personal Renewals
                    </a>
                    @endif
                    @if($mod('dsc'))
                    <a href="{{ route('dscs.index') }}" class="group flex items-center py-3 text-sm font-bold rounded-xl transition-all duration-200 {{ request()->routeIs('dscs.*') ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/30 border-l-4 border-indigo-400 pl-3 pr-4 scale-[1.01]' : 'text-slate-400 hover:bg-white/5 hover:text-white hover:translate-x-1 pl-4 pr-4' }}">
                        <svg class="mr-3 flex-shrink-0 h-5 w-5 {{ request()->routeIs('dscs.*') ? 'text-white' : 'text-slate-500 group-hover:text-white' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                        </svg>
                        DSC Tracker
                    </a>
                    @endif
                </div>
                @endif

                <!-- 5. FINANCE & BILLING -->
                @if($showFinance)
                <div class="pt-4 border-t border-white/5 space-y-1">
                    <p class="px-4 text-[10px] font-extrabold text-indigo-300/40 uppercase tracking-widest mb-2 select-none">
                        Billing
                    </p>
                    @if($mod('billing'))
                    <a href="{{ route('billing.index') }}" class="group flex items-center py-3 text-sm font-bold rounded-xl transition-all duration-200 {{ request()->routeIs('billing.*') ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/30 border-l-4 border-indigo-400 pl-3 pr-4 scale-[1.01]' : 'text-slate-400 hover:bg-white/5 hover:text-white hover:translate-x-1 pl-4 pr-4' }}">
                        <svg class="mr-3 flex-shrink-0 h-5 w-5 {{ request()->routeIs('billing.*') ? 'text-white' : 'text-slate-500 group-hover:text-white' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        Billing Queue
                    </a>
                    @endif
                    @if($mod('invoices') || auth()->user()?->canViewPortfolioInvoices())
                    <a href="{{ route('invoices.index') }}" data-tour="nav-invoices" class="group flex items-center py-3 text-sm font-bold rounded-xl transition-all duration-200 {{ request()->routeIs('invoices.*') ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/30 border-l-4 border-indigo-400 pl-3 pr-4 scale-[1.01]' : 'text-slate-400 hover:bg-white/5 hover:text-white hover:translate-x-1 pl-4 pr-4' }}">
                        <svg class="mr-3 flex-shrink-0 h-5 w-5 {{ request()->routeIs('invoices.*') ? 'text-white' : 'text-slate-500 group-hover:text-white' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        {{ auth()->user()?->isAssociate() ? 'My Client Invoices' : 'Invoices' }}
                    </a>
                    @endif
                    @if($mod('payments') && $canManageFirm)
                    <a href="{{ route('collections.index') }}" class="group flex items-center py-3 text-sm font-bold rounded-xl transition-all duration-200 {{ request()->routeIs('collections.*') ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/30 border-l-4 border-indigo-400 pl-3 pr-4 scale-[1.01]' : 'text-slate-400 hover:bg-white/5 hover:text-white hover:translate-x-1 pl-4 pr-4' }}">
                        <svg class="mr-3 flex-shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                        Collections
                    </a>
                    @endif
                    @if($mod('payments'))
                    <a href="{{ route('payments.index') }}" class="group flex items-center py-3 text-sm font-bold rounded-xl transition-all duration-200 {{ request()->routeIs('payments.*') ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/30 border-l-4 border-indigo-400 pl-3 pr-4 scale-[1.01]' : 'text-slate-400 hover:bg-white/5 hover:text-white hover:translate-x-1 pl-4 pr-4' }}">
                        <svg class="mr-3 flex-shrink-0 h-5 w-5 {{ request()->routeIs('payments.*') ? 'text-white' : 'text-slate-500 group-hover:text-white' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        Payments
                    </a>
                    @endif
                    @if($mod('expenses'))
                    <a href="{{ route('expenses.index') }}" class="group flex items-center py-3 text-sm font-bold rounded-xl transition-all duration-200 {{ request()->routeIs('expenses.*') ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/30 border-l-4 border-indigo-400 pl-3 pr-4 scale-[1.01]' : 'text-slate-400 hover:bg-white/5 hover:text-white hover:translate-x-1 pl-4 pr-4' }}">
                        <svg class="mr-3 flex-shrink-0 h-5 w-5 {{ request()->routeIs('expenses.*') ? 'text-white' : 'text-slate-500 group-hover:text-white' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        Expenses
                    </a>
                    @endif
                    @if($mod('subscriptions'))
                    <a href="{{ route('subscriptions.index') }}" class="group flex items-center py-3 text-sm font-bold rounded-xl transition-all duration-200 {{ request()->routeIs('subscriptions.*') ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/30 border-l-4 border-indigo-400 pl-3 pr-4 scale-[1.01]' : 'text-slate-400 hover:bg-white/5 hover:text-white hover:translate-x-1 pl-4 pr-4' }}">
                        <svg class="mr-3 flex-shrink-0 h-5 w-5 {{ request()->routeIs('subscriptions.*') ? 'text-white' : 'text-slate-500 group-hover:text-white' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Subscriptions
                    </a>
                    @endif
                </div>
                @endif

                <!-- 6. REPORTS & 360° -->
                @if($mod('reports') || $mod('compliance'))
                <div class="pt-4 border-t border-white/5" x-data="{ open: {{ request()->routeIs('reports.*') || request()->routeIs('compliance.index') ? 'true' : 'false' }} }">
                    <p class="px-4 text-[10px] font-extrabold text-indigo-300/40 uppercase tracking-widest mb-2 select-none">
                        Insights
                    </p>
                    <button @click="open = !open" class="group w-full flex items-center justify-between px-4 py-3 text-sm font-bold rounded-xl transition-all duration-200 {{ request()->routeIs('reports.*') || request()->routeIs('compliance.index') ? 'text-white bg-white/5' : 'text-slate-400 hover:bg-white/5 hover:text-white hover:translate-x-1' }} focus:outline-none">
                        <div class="flex items-center">
                            <svg class="mr-3 flex-shrink-0 h-5 w-5 {{ request()->routeIs('reports.*') || request()->routeIs('compliance.index') ? 'text-indigo-400' : 'text-slate-500 group-hover:text-white' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Reports & 360°
                        </div>
                        <svg class="w-4 h-4 transition-transform transform duration-200" :class="{ 'rotate-180 text-white': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <!-- Submenu -->
                    <div x-show="open" 
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="space-y-1 pl-12 pr-2 mt-1" 
                         style="display: none;">
                        <a href="{{ route('compliance.index') }}" class="group flex items-center px-2 py-2 text-xs font-semibold rounded-md transition-all duration-150 {{ request()->routeIs('compliance.index') ? 'text-white font-bold bg-indigo-500/20' : 'text-slate-400 hover:text-white hover:pl-3' }}">
                            Compliance 360°
                        </a>
                        <a href="{{ route('reports.index') }}?tab=service" class="group flex items-center px-2 py-2 text-xs font-semibold rounded-md transition-all duration-150 {{ request()->routeIs('reports.index') && request()->get('tab') == 'service' ? 'text-white font-bold bg-indigo-500/20' : 'text-slate-400 hover:text-white hover:pl-3' }}">
                            Service Report
                        </a>
                        <a href="{{ route('reports.financial') }}?tab=income" class="group flex items-center px-2 py-2 text-xs font-semibold rounded-md transition-all duration-150 {{ request()->routeIs('reports.financial') || (request()->routeIs('reports.*') && request()->get('tab') == 'income') ? 'text-white font-bold bg-indigo-500/20' : 'text-slate-400 hover:text-white hover:pl-3' }}">
                            Income Wise
                        </a>
                        <a href="{{ route('reports.compliance') }}?tab=due_date" class="group flex items-center px-2 py-2 text-xs font-semibold rounded-md transition-all duration-150 {{ request()->routeIs('reports.compliance') || (request()->routeIs('reports.*') && request()->get('tab') == 'due_date') ? 'text-white font-bold bg-indigo-500/20' : 'text-slate-400 hover:text-white hover:pl-3' }}">
                            Due Date Report
                        </a>
                        @if($canManageFirm)
                        <a href="{{ route('reports.staff-productivity') }}" class="group flex items-center px-2 py-2 text-xs font-semibold rounded-md transition-all duration-150 {{ request()->routeIs('reports.staff-productivity') ? 'text-white font-bold bg-indigo-500/20' : 'text-slate-400 hover:text-white hover:pl-3' }}">
                            Staff Productivity
                        </a>
                        <a href="{{ route('reports.client-profitability') }}" class="group flex items-center px-2 py-2 text-xs font-semibold rounded-md transition-all duration-150 {{ request()->routeIs('reports.client-profitability') ? 'text-white font-bold bg-indigo-500/20' : 'text-slate-400 hover:text-white hover:pl-3' }}">
                            Client Profitability
                        </a>
                        @endif
                    </div>
                </div>
                @endif

                <!-- 7. ADMINISTRATION / OPERATIONS -->
                @if($mod('activity') || $canManageFirm || $mod('system'))
                <div class="pt-4 border-t border-white/5 space-y-1">
                    <p class="px-4 text-[10px] font-extrabold text-indigo-300/40 uppercase tracking-widest mb-2 select-none">
                        Administration
                    </p>
                    @if($mod('activity'))
                    <a href="{{ route('activity.index') }}" class="group flex items-center py-3 text-sm font-bold rounded-xl transition-all duration-200 {{ request()->routeIs('activity.*') ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/30 border-l-4 border-indigo-400 pl-3 pr-4 scale-[1.01]' : 'text-slate-400 hover:bg-white/5 hover:text-white hover:translate-x-1 pl-4 pr-4' }}">
                        <svg class="mr-3 flex-shrink-0 h-5 w-5 {{ request()->routeIs('activity.*') ? 'text-white' : 'text-slate-500 group-hover:text-white' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        The Pulse
                    </a>
                    @endif
                    @if($canManageFirm)
                    <a href="{{ route('recycle-bin.index') }}" class="group flex items-center py-3 text-sm font-bold rounded-xl transition-all duration-200 {{ request()->routeIs('recycle-bin.*') ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/30 border-l-4 border-indigo-400 pl-3 pr-4 scale-[1.01]' : 'text-slate-400 hover:bg-white/5 hover:text-white hover:translate-x-1 pl-4 pr-4' }}">
                        <svg class="mr-3 flex-shrink-0 h-5 w-5 {{ request()->routeIs('recycle-bin.*') ? 'text-white' : 'text-slate-500 group-hover:text-white' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Recycle Bin
                    </a>
                    @endif
                    @if($mod('service_dues') && $canManageFirm)
                    <a href="{{ route('services.index') }}" class="group flex items-center py-3 text-sm font-bold rounded-xl transition-all duration-200 {{ request()->routeIs('services.*') ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/30 border-l-4 border-indigo-400 pl-3 pr-4 scale-[1.01]' : 'text-slate-400 hover:bg-white/5 hover:text-white hover:translate-x-1 pl-4 pr-4' }}">
                        <svg class="mr-3 flex-shrink-0 h-5 w-5 {{ request()->routeIs('services.*') ? 'text-white' : 'text-slate-500 group-hover:text-white' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Service Master
                    </a>
                    @endif
                    @if($mod('system') && $isPartner)
                    <a href="{{ route('system.index') }}" class="group flex items-center py-3 text-sm font-bold rounded-xl transition-all duration-200 {{ request()->routeIs('system.*') ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-600/30 border-l-4 border-indigo-400 pl-3 pr-4 scale-[1.01]' : 'text-slate-400 hover:bg-white/5 hover:text-white hover:translate-x-1 pl-4 pr-4' }}">
                        <svg class="mr-3 flex-shrink-0 h-5 w-5 {{ request()->routeIs('system.*') ? 'text-white' : 'text-slate-500 group-hover:text-white' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V9a2 2 0 012-2h2a2 2 0 012 2v10" />
                        </svg>
                        System Health
                    </a>
                    @endif
                </div>
                @endif
                @endif
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-shell flex flex-col min-h-screen min-w-0">
            <!-- Header -->
            <header class="bg-white shadow-sm border-b border-gray-100 sticky top-0 z-50">
                <div class="px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center gap-3">
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        <button type="button" onclick="openMobileSidebar()" class="lg:hidden flex-shrink-0 p-2 -ml-1 rounded-lg text-slate-600 hover:bg-slate-100 focus:outline-none" aria-label="Open menu">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                        </button>
                        <h2 class="text-xl sm:text-2xl font-bold text-gray-800 bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 to-indigo-400 truncate min-w-0">
                            @yield('header', 'Dashboard')
                        </h2>
                    </div>
                    <div class="flex items-center space-x-4">
                        @include('partials.demo-tour')
                        <!-- Search Trigger -->
                        <div class="hidden md:flex items-center mr-2" x-data @click="$dispatch('keydown.window.prevent.ctrl.k')">
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

                        <!-- Zen Mode Toggle -->
                        <button onclick="toggleZenMode()" class="p-2 text-gray-400 hover:text-indigo-600 focus:outline-none transition-colors" title="Toggle Zen Mode">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                            </svg>
                        </button>

                        <!-- PWA Install Button -->
                        <button id="pwa-install-btn" onclick="installPWA()" class="hidden p-2 text-gray-400 hover:text-indigo-600 focus:outline-none transition-colors" title="Install App">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
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
                                <span class="absolute top-1.5 right-1.5 min-w-[1.125rem] h-[1.125rem] px-0.5 rounded-full bg-red-500 ring-2 ring-white text-[9px] font-bold text-white flex items-center justify-center">{{ max($unreadCount, $groupTotal) > 9 ? '9+' : max($unreadCount, $groupTotal) }}</span>
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
                                                <p class="mt-1 text-[10px] text-gray-400">
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
                                <span class="text-sm font-medium text-gray-700 group-hover:text-indigo-600 max-w-[140px] truncate">{{ $user?->name }}</span>
                                <div class="h-8 w-8 rounded-full bg-indigo-600 flex items-center justify-center text-white text-sm font-bold group-hover:ring-2 ring-indigo-400">
                                    {{ strtoupper(substr($user?->name ?? 'U', 0, 1)) }}
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

        function toggleZenMode() {
            document.body.classList.toggle('zen-mode');
            localStorage.setItem('zen-mode', document.body.classList.contains('zen-mode'));
        }
    </script>

    @include('partials.mobile-bottom-nav')
    @include('partials.search-modal')
    @include('partials.dashboard-help-chat')
    @include('partials.demo-tour-play')
    @include('partials.demo-tour-overlay')

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