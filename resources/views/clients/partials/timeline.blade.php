<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-4 py-3 border-b border-slate-100 bg-slate-50">
        <h3 class="text-sm font-bold text-slate-900">Client timeline</h3>
        <p class="text-xs text-slate-500">Tasks, compliance, invoices, payments, vault access, and follow-ups.</p>
    </div>
    <ul class="divide-y divide-slate-100 max-h-[32rem] overflow-y-auto">
        @forelse($timeline as $event)
        <li class="px-4 py-3 flex gap-3 hover:bg-slate-50">
            <div class="flex-shrink-0 w-16 text-xs text-slate-400 pt-0.5">
                {{ $event['at']?->format('d M Y') }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-slate-900">
                    @if($event['url'])
                    <a href="{{ $event['url'] }}" class="hover:text-indigo-600">{{ $event['title'] }}</a>
                    @else
                    {{ $event['title'] }}
                    @endif
                </p>
                @if($event['detail'])
                <p class="text-xs text-slate-500 mt-0.5">{{ $event['detail'] }}</p>
                @endif
                <span class="inline-block mt-1 text-[10px] uppercase tracking-wide text-slate-400">{{ $event['type'] }}</span>
            </div>
        </li>
        @empty
        <li class="px-4 py-8 text-center text-sm text-slate-500">No timeline events yet.</li>
        @endforelse
    </ul>
</div>
