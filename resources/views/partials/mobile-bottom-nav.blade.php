@php
    $navUser = auth()->user();
    $navMod = fn (string $key) => $navUser?->canAccessModule($key) ?? false;
    $showMobileNav = $navUser && ! $navUser->isPartner() && ! $navUser->isManager();
@endphp

@if($showMobileNav)
<nav class="mobile-bottom-nav fixed bottom-0 inset-x-0 z-50 bg-white border-t border-slate-200 safe-area-pb lg:hidden" aria-label="Mobile navigation">
    <div class="flex items-stretch justify-around h-14 max-w-lg mx-auto">
        @if($navMod('tasks'))
        <a href="{{ route('tasks.my-day') }}"
            class="flex flex-col items-center justify-center flex-1 min-w-0 px-1 text-[10px] font-semibold {{ request()->routeIs('tasks.my-day') ? 'text-indigo-600' : 'text-slate-500' }}">
            <svg class="h-5 w-5 mb-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
            My Day
        </a>
        <a href="{{ route('tasks.index') }}"
            class="flex flex-col items-center justify-center flex-1 min-w-0 px-1 text-[10px] font-semibold {{ request()->routeIs('tasks.index', 'tasks.edit') ? 'text-indigo-600' : 'text-slate-500' }}">
            <svg class="h-5 w-5 mb-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
            Tasks
        </a>
        <a href="{{ route('time-entries.index') }}"
            class="flex flex-col items-center justify-center flex-1 min-w-0 px-1 text-[10px] font-semibold {{ request()->routeIs('time-entries.*') ? 'text-indigo-600' : 'text-slate-500' }}">
            <svg class="h-5 w-5 mb-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            Time
        </a>
        @endif
        @if($navUser->isArticle())
            @can('create', App\Models\Client::class)
            <a href="{{ route('clients.create') }}"
                class="flex flex-col items-center justify-center flex-1 min-w-0 px-1 text-[10px] font-semibold {{ request()->routeIs('clients.create') ? 'text-indigo-600' : 'text-slate-500' }}">
                <svg class="h-5 w-5 mb-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                New client
            </a>
            @endcan
        @elseif($navMod('clients'))
        <a href="{{ route('clients.index') }}"
            class="flex flex-col items-center justify-center flex-1 min-w-0 px-1 text-[10px] font-semibold {{ request()->routeIs('clients.*') ? 'text-indigo-600' : 'text-slate-500' }}">
            <svg class="h-5 w-5 mb-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            Clients
        </a>
        @endif
        <button type="button"
            @click="$dispatch('keydown.window.prevent.ctrl.k')"
            class="flex flex-col items-center justify-center flex-1 min-w-0 px-1 text-[10px] font-semibold text-slate-500 hover:text-indigo-600">
            <svg class="h-5 w-5 mb-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            Search
        </button>
    </div>
</nav>
@endif
