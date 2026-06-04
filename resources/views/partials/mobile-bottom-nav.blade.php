@php
    $navUser = auth()->user();
    $navMod = fn (string $key) => $navUser?->canAccessModule($key) ?? false;
@endphp
@if($navUser)
<nav class="mobile-bottom-nav fixed bottom-0 inset-x-0 z-50 bg-white border-t border-slate-200 safe-area-pb lg:hidden shadow-[0_-4px_20px_rgba(0,0,0,0.06)]" aria-label="Mobile navigation">
    <div class="flex items-stretch justify-around h-14 max-w-lg mx-auto px-1">
        @if($navMod('dashboard'))
        <a href="{{ route('dashboard') }}"
            class="flex flex-col items-center justify-center flex-1 min-w-0 px-1 text-[10px] font-semibold {{ request()->routeIs('dashboard') ? 'text-indigo-600' : 'text-slate-500' }}">
            <svg class="h-5 w-5 mb-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
            Home
        </a>
        @endif
        @if($navMod('clients') && ! $navUser->isArticle())
        <a href="{{ route('clients.index') }}"
            class="flex flex-col items-center justify-center flex-1 min-w-0 px-1 text-[10px] font-semibold {{ request()->routeIs('clients.*') ? 'text-indigo-600' : 'text-slate-500' }}">
            <svg class="h-5 w-5 mb-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            Clients
        </a>
        @endif
        @if($navMod('tasks'))
        <a href="{{ route('tasks.my-day') }}"
            class="flex flex-col items-center justify-center flex-1 min-w-0 px-1 text-[10px] font-semibold {{ request()->routeIs('tasks.*') ? 'text-indigo-600' : 'text-slate-500' }}">
            <svg class="h-5 w-5 mb-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
            Tasks
        </a>
        @endif
        @if($navMod('dashboard'))
        <a href="{{ route('dashboard') }}#schedule"
            class="flex flex-col items-center justify-center flex-1 min-w-0 px-1 text-[10px] font-semibold text-slate-500">
            <svg class="h-5 w-5 mb-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            Calendar
        </a>
        @endif
        <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-mobile-fab'))"
            class="flex flex-col items-center justify-center flex-1 min-w-0 px-1 text-[10px] font-semibold text-indigo-600">
            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600 text-white mb-0.5 shadow-md">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
            </span>
            Add
        </button>
    </div>
</nav>
@include('partials.mobile-fab')
@endif
