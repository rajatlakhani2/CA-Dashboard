@php $pulse = $missionControl['firm_pulse'] ?? []; @endphp
<div class="glass-card p-4 sm:p-5">
    <div class="flex flex-wrap justify-between items-center gap-2 mb-4">
        <div>
            <p class="glass-section-title mb-0">Firm pulse · today</p>
            <p class="text-xs text-gray-500">Live activity summary</p>
        </div>
        <a href="{{ route('activity.index') }}" class="text-xs font-semibold text-indigo-600 hover:underline">Full feed →</a>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
        <div class="rounded-lg bg-indigo-50 px-3 py-2 border border-indigo-100">
            <p class="text-[10px] font-bold text-indigo-600 uppercase">Tasks done</p>
            <p class="text-xl font-black text-indigo-900">+{{ $pulse['tasks_completed'] ?? 0 }}</p>
        </div>
        <div class="rounded-lg bg-blue-50 px-3 py-2 border border-blue-100">
            <p class="text-[10px] font-bold text-blue-600 uppercase">New clients</p>
            <p class="text-xl font-black text-blue-900">+{{ $pulse['clients_added'] ?? 0 }}</p>
        </div>
        <div class="rounded-lg bg-emerald-50 px-3 py-2 border border-emerald-100">
            <p class="text-[10px] font-bold text-emerald-600 uppercase">Collected</p>
            <p class="text-xl font-black text-emerald-900">₹{{ number_format($pulse['collected'] ?? 0, 0) }}</p>
        </div>
        <div class="rounded-lg bg-violet-50 px-3 py-2 border border-violet-100">
            <p class="text-[10px] font-bold text-violet-600 uppercase">Filings done</p>
            <p class="text-xl font-black text-violet-900">+{{ $pulse['filings_completed'] ?? 0 }}</p>
        </div>
    </div>
    @if(!empty($pulse['feed']) && count($pulse['feed']) > 0)
    <ul class="space-y-1.5 max-h-40 overflow-y-auto text-sm">
        @foreach($pulse['feed'] as $item)
        <li class="flex gap-3 text-gray-600">
            <span class="font-mono text-[10px] text-gray-400 shrink-0">{{ $item['time'] }}</span>
            <span class="truncate">{{ $item['text'] }}</span>
        </li>
        @endforeach
    </ul>
    @else
    <p class="text-xs text-gray-500">No activity logged yet today.</p>
    @endif
</div>
