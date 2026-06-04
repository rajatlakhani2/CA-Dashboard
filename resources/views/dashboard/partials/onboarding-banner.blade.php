@if(!empty($onboarding['show']))
<div class="onboarding-premium p-4 sm:p-5" x-data="{ open: true }" x-show="open">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="flex-1 min-w-0">
            <p class="text-sm font-bold text-[var(--premium-navy)]">Workspace setup · {{ $onboarding['percent'] }}% complete</p>
            <div class="mt-2 h-2 rounded-full bg-white overflow-hidden max-w-md">
                <div class="h-full bg-indigo-600 rounded-full transition-all" style="width: {{ $onboarding['percent'] }}%"></div>
            </div>
            <ul class="mt-3 flex flex-wrap gap-2">
                @foreach($onboarding['steps'] as $step)
                <li>
                    <a href="{{ $step['url'] }}"
                       class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold
                       {{ $step['done'] ? 'bg-emerald-100 text-emerald-800' : 'bg-white text-indigo-800 border border-indigo-200 hover:bg-indigo-100' }}">
                        @if($step['done'])<span>✓</span>@endif
                        {{ $step['label'] }}
                    </a>
                </li>
                @endforeach
            </ul>
        </div>
        <form method="POST" action="{{ route('onboarding.dismiss') }}" class="shrink-0">
            @csrf
            <button type="submit" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800" @click="open = false">Dismiss</button>
        </form>
    </div>
</div>
@endif
