@php $health = $clientHealth ?? ['score' => 0, 'label' => '—', 'tone' => 'gray', 'breakdown' => []]; @endphp
<div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Client health score</p>
            <p class="text-3xl font-black
                {{ $health['tone'] === 'green' ? 'text-emerald-600' : ($health['tone'] === 'amber' ? 'text-amber-600' : 'text-rose-600') }}">
                {{ $health['score'] }}<span class="text-lg text-slate-400 font-semibold">/100</span>
            </p>
            <p class="text-xs font-semibold text-slate-600">{{ $health['label'] }}</p>
        </div>
        <ul class="flex flex-wrap gap-2 text-xs">
            @foreach($health['breakdown'] as $row)
            <li class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 border
                {{ $row['status'] === 'green' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : ($row['status'] === 'amber' ? 'bg-amber-50 border-amber-200 text-amber-800' : 'bg-rose-50 border-rose-200 text-rose-800') }}">
                <span>{{ $row['status'] === 'green' ? '🟢' : ($row['status'] === 'amber' ? '🟡' : '🔴') }}</span>
                {{ $row['label'] }}
            </li>
            @endforeach
        </ul>
    </div>
</div>
