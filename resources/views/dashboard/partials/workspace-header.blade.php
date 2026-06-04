@php
    $ws = $workspace ?? [];
    $team = collect($ws['team'] ?? []);
@endphp
<div class="saas-workspace overflow-hidden">
    <div class="saas-workspace__hero">
        <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4 w-full">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="saas-workspace__badge">SaaS Workspace</span>
                    @if(!empty($ws['organization']?->slug))
                    <span class="saas-workspace__badge font-mono normal-case tracking-normal" title="Share this ID for login">ID: {{ $ws['organization']->slug }}</span>
                    @endif
                    <span class="saas-workspace__badge saas-workspace__badge--plan">{{ $ws['plan'] ?? 'Professional' }} plan</span>
                    @if(($ws['seats_remaining'] ?? 0) <= 3)
                    <span class="saas-workspace__badge" style="background:rgba(251,191,36,0.15);border-color:rgba(251,191,36,0.3);color:#fde68a;">Seats almost full</span>
                    @endif
                </div>
                <h1 class="saas-workspace__title truncate">{{ $ws['name'] ?? 'Your firm' }}</h1>
                <p class="saas-workspace__meta">
                    Welcome, <span class="font-semibold text-white">{{ auth()->user()->name }}</span>
                    · <span class="capitalize">{{ $ws['role_label'] ?? 'user' }}</span>
                    · {{ now()->format('l, d M Y') }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3 shrink-0">
                <div class="saas-workspace__stat">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-white/60">Team</p>
                    <p class="text-lg font-bold text-white tabular-nums">{{ $ws['seat_used'] ?? 0 }}<span class="text-white/50 text-sm font-medium">/{{ $ws['seat_limit'] ?? 25 }}</span></p>
                </div>
                @can('viewAny', App\Models\User::class)
                <a href="{{ route('staff.index') }}" class="saas-workspace__btn inline-flex items-center gap-1.5">
                    Manage users
                </a>
                @endcan
            </div>
        </div>
    </div>

    @if($team->isNotEmpty())
    <div class="px-[var(--content-pad)] py-3 border-b border-[var(--premium-border)] bg-[#f6f8fb]">
        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-[var(--premium-muted)] mb-2">Your team</p>
        <div class="saas-team-grid">
            @foreach($team->take(12) as $member)
            <div class="saas-team-card {{ ($member['is_you'] ?? false) ? 'saas-team-card--you' : '' }}">
                <span class="saas-team-card__avatar">{{ $member['initials'] }}</span>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold text-[var(--premium-text)] truncate">{{ $member['name'] }}{{ ($member['is_you'] ?? false) ? ' (you)' : '' }}</p>
                    <p class="text-[10px] text-[var(--premium-muted)]">{{ $member['role'] }} · {{ $member['open_tasks'] }} open</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="px-[var(--content-pad)] py-3 flex flex-wrap items-center justify-between gap-2 text-xs text-[var(--premium-muted)] border-t border-[var(--premium-border)]">
        <p class="max-w-full sm:max-w-2xl line-clamp-2 sm:truncate">{{ $positiveThought ?? 'Ready for a productive day.' }}</p>
        <div class="flex flex-wrap gap-2 lg:hidden shrink-0">
            @can('create', App\Models\Client::class)
            <a href="{{ route('clients.create') }}" class="rounded-lg border border-[var(--premium-border)] px-2.5 py-1 font-semibold text-[var(--premium-navy)] hover:bg-white">+ Client</a>
            @endcan
            <a href="{{ route('tasks.create') }}" class="rounded-lg border border-[var(--premium-border)] px-2.5 py-1 font-semibold text-[var(--premium-navy)] hover:bg-white">+ Task</a>
        </div>
    </div>
</div>
