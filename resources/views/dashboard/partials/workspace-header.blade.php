@php
    $ws = $workspace ?? [];
    $team = collect($ws['team'] ?? []);
@endphp
<div class="saas-workspace rounded-2xl border border-indigo-100 bg-white shadow-sm overflow-hidden">
    <div class="px-5 py-4 sm:px-6 bg-gradient-to-br from-indigo-700 via-indigo-600 to-indigo-500 text-white">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <span class="inline-flex items-center rounded-md bg-white/15 px-2 py-0.5 text-[10px] font-bold uppercase tracking-widest text-white/90">SaaS Workspace</span>
                    @if(!empty($ws['organization']?->slug))
                    <span class="inline-flex items-center rounded-md bg-white/15 px-2 py-0.5 text-[10px] font-mono text-white/80" title="Share this ID for login">ID: {{ $ws['organization']->slug }}</span>
                    @endif
                    <span class="inline-flex items-center rounded-md bg-emerald-400/30 px-2 py-0.5 text-[10px] font-semibold text-emerald-50">{{ $ws['plan'] ?? 'Professional' }} plan</span>
                    @if(($ws['seats_remaining'] ?? 0) <= 3)
                    <span class="inline-flex items-center rounded-md bg-amber-500/25 px-2 py-0.5 text-[10px] font-semibold text-amber-100">Seats almost full</span>
                    @endif
                </div>
                <h1 class="text-xl sm:text-2xl font-black truncate">{{ $ws['name'] ?? 'Your firm' }}</h1>
                <p class="text-indigo-100 text-sm mt-1">
                    Welcome, <span class="font-semibold text-white">{{ auth()->user()->name }}</span>
                    · <span class="capitalize">{{ $ws['role_label'] ?? 'user' }}</span>
                    · {{ now()->format('l, d M Y') }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3 shrink-0">
                <div class="rounded-xl bg-white/10 px-4 py-2.5 text-center min-w-[5.5rem]">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-indigo-200">Team</p>
                    <p class="text-lg font-black">{{ $ws['seat_used'] ?? 0 }}<span class="text-indigo-300 text-sm font-semibold">/{{ $ws['seat_limit'] ?? 25 }}</span></p>
                </div>
                @can('viewAny', App\Models\User::class)
                <a href="{{ route('staff.index') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-white text-indigo-900 px-4 py-2.5 text-xs font-bold shadow hover:bg-indigo-50 transition">
                    Manage users
                </a>
                @endcan
            </div>
        </div>
    </div>

    @if($team->isNotEmpty())
    <div class="px-5 py-3 sm:px-6 border-b border-gray-100 bg-gray-50/80">
        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">Your team · multi-user workspace</p>
        <div class="flex gap-2 overflow-x-auto pb-1 scrollbar-thin">
            @foreach($team->take(12) as $member)
            <div class="flex shrink-0 items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2 min-w-[9rem] {{ ($member['is_you'] ?? false) ? 'ring-2 ring-indigo-400 border-indigo-200' : '' }}">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-100 text-indigo-800 text-xs font-black">{{ $member['initials'] }}</span>
                <div class="min-w-0">
                    <p class="text-xs font-bold text-gray-900 truncate">{{ $member['name'] }}{{ ($member['is_you'] ?? false) ? ' (you)' : '' }}</p>
                    <p class="text-[10px] text-gray-500">{{ $member['role'] }} · {{ $member['open_tasks'] }} open</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="px-5 py-3 sm:px-6 flex flex-wrap items-center justify-between gap-2 text-xs text-gray-600">
        <p class="italic text-gray-500 max-w-xl truncate">"{{ $positiveThought ?? 'Ready for a productive day.' }}"</p>
        <span class="hidden sm:inline text-[10px] font-mono text-gray-400">Dashboard SaaS v1</span>
        <div class="flex flex-wrap gap-2 lg:hidden">
            @can('create', App\Models\Client::class)
            <a href="{{ route('clients.create') }}" class="rounded-lg border border-gray-200 px-2.5 py-1 font-semibold hover:bg-gray-50">+ Client</a>
            @endcan
            <a href="{{ route('tasks.create') }}" class="rounded-lg border border-gray-200 px-2.5 py-1 font-semibold hover:bg-gray-50">+ Task</a>
        </div>
    </div>
</div>
