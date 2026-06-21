@php
    $health = $clientHealth ?? ['score' => 0, 'label' => '—', 'tone' => 'gray'];
    $chips = $complianceChips ?? [];
    $timelinePreview = ($timeline ?? collect())->take(6);
@endphp
<div class="rounded-2xl border border-gray-100 bg-white p-5 sm:p-6 shadow-sm">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="text-xl font-bold text-gray-900 tracking-tight truncate">{{ $client->name }}</h2>
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold
                    {{ $health['tone'] === 'green' ? 'bg-emerald-50 text-emerald-700' : ($health['tone'] === 'amber' ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700') }}">
                    Health {{ $health['score'] }}/100
                </span>
            </div>
            <p class="text-sm text-gray-500 mt-1">{{ $health['label'] }} · {{ $client->client_code }}</p>
        </div>
        @if(isset($totalOutstanding) && auth()->user()?->managesFirmModules())
        <div class="text-right shrink-0">
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Outstanding</p>
            <p class="text-2xl font-extrabold text-gray-900 tabular-nums">₹{{ number_format($totalOutstanding, 0) }}</p>
        </div>
        @endif
    </div>

    @if(count($chips) > 0)
    <div class="flex flex-wrap gap-2 mt-4">
        @foreach($chips as $chip)
        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold border
            {{ $chip['status'] === 'green' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : ($chip['status'] === 'amber' ? 'bg-amber-50 border-amber-200 text-amber-800' : ($chip['status'] === 'red' ? 'bg-rose-50 border-rose-200 text-rose-800' : 'bg-gray-50 border-gray-200 text-gray-600')) }}">
            {{ $chip['label'] }}
            @if($chip['status'] === 'green') ✓
            @elseif($chip['status'] === 'amber') ⚠
            @elseif($chip['status'] === 'red') ✗
            @else —
            @endif
        </span>
        @endforeach
    </div>
    @endif

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-5 pt-5 border-t border-gray-100">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Partner / Manager</p>
            <p class="text-sm font-semibold text-gray-900 mt-0.5 truncate">{{ $client->manager->name ?? 'Unassigned' }}</p>
        </div>
        @if($lastPayment ?? null)
        <div>
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Last payment</p>
            <p class="text-sm font-semibold text-gray-900 mt-0.5">{{ $lastPayment->payment_date->format('d M Y') }}</p>
            <p class="text-xs text-gray-500">₹{{ number_format($lastPayment->amount, 0) }}</p>
        </div>
        @else
        <div>
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Last payment</p>
            <p class="text-sm text-gray-400 mt-0.5">—</p>
        </div>
        @endif
        @if($lastInvoice ?? null)
        <div>
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Last invoice</p>
            <a href="{{ route('invoices.show', $lastInvoice) }}" class="text-sm font-semibold text-indigo-600 hover:underline mt-0.5 block truncate">{{ $lastInvoice->invoice_number }}</a>
        </div>
        @endif
        @if($nextDue ?? null)
        <div>
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Next due</p>
            <p class="text-sm font-semibold text-gray-900 mt-0.5 truncate">{{ $nextDue->clientService->service->name ?? 'Compliance' }}</p>
            <p class="text-xs text-gray-500">{{ $nextDue->due_date->format('d M Y') }}</p>
        </div>
        @endif
    </div>

    @if($timelinePreview->isNotEmpty())
    <div class="mt-5 pt-5 border-t border-gray-100">
        <div class="flex items-center justify-between mb-3">
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Timeline</p>
            <button type="button" @click="tab = 'timeline'" class="text-xs font-semibold text-indigo-600 hover:underline">View all →</button>
        </div>
        <ul class="space-y-2">
            @foreach($timelinePreview as $event)
            <li class="flex items-start gap-3 text-sm">
                <span class="text-[10px] font-mono text-gray-400 shrink-0 w-14">{{ $event['at']->format('M Y') }}</span>
                <span class="text-gray-700 flex-1 min-w-0 truncate">{{ $event['title'] }}</span>
                @if(!empty($event['url']))
                <a href="{{ $event['url'] }}" class="text-xs text-indigo-600 shrink-0">Open</a>
                @endif
            </li>
            @endforeach
        </ul>
    </div>
    @endif
</div>
