@extends('layouts.app')

@section('header', 'Collections — Call today')

@section('content')
<div class="space-y-6">
    <p class="text-sm text-slate-600">Prioritized by outstanding balance and days since last contact. Log follow-ups to improve tomorrow's list.</p>

    <div class="flex flex-wrap gap-2">
        <a href="{{ route('collections.index') }}"
            class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ ! $bucket ? 'bg-indigo-600 text-white' : 'bg-white border border-slate-200 text-slate-600' }}">
            All ({{ array_sum($bucketCounts) }})
        </a>
        @foreach(['0-30' => '0–30 days', '31-60' => '31–60', '61-90' => '61–90', '90+' => '90+'] as $key => $label)
        <a href="{{ route('collections.index', ['bucket' => $key]) }}"
            class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ $bucket === $key ? 'bg-indigo-600 text-white' : 'bg-white border border-slate-200 text-slate-600' }}">
            {{ $label }} ({{ $bucketCounts[$key] ?? 0 }})
        </a>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-xl shadow border border-slate-100 overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">Priority</th>
                        <th class="px-4 py-3 text-left">Client</th>
                        <th class="px-4 py-3 text-right">Outstanding</th>
                        <th class="px-4 py-3 text-left">Aging</th>
                        <th class="px-4 py-3 text-left">Last contact</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($callList as $row)
                    <tr class="{{ $highlightClientId === $row->client->id ? 'bg-indigo-50' : '' }}">
                        <td class="px-4 py-3 font-semibold text-slate-700">{{ $row->priority }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('clients.show', $row->client) }}" class="font-medium text-indigo-600 hover:underline">{{ $row->client->name }}</a>
                            @if($row->promise_date)
                            <p class="text-xs text-amber-700">Promise: {{ $row->promise_date->format('d M Y') }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-money-negative">₹ {{ number_format($row->outstanding, 2) }}</td>
                        <td class="px-4 py-3">
                            <span class="text-xs font-medium text-slate-600">{{ $row->aging_bucket }}</span>
                            @if($row->oldest_due)
                            <p class="text-xs text-slate-400">Due {{ $row->oldest_due->format('d M') }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-500">
                            @if($row->last_contact_at)
                            {{ $row->last_contact_at->format('d M Y') }} ({{ $row->days_since_contact }}d ago)
                            @else
                            Never logged
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('collections.index', ['client_id' => $row->client->id, 'bucket' => $bucket]) }}" class="text-xs font-semibold text-indigo-600">Log contact</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-500">No clients with outstanding invoices.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="bg-white rounded-xl shadow border border-slate-100 p-4">
            <h3 class="text-sm font-bold text-slate-900 mb-3">Log follow-up</h3>
            @if($selectedClient)
            <form method="POST" action="{{ route('collections.follow-up', $selectedClient) }}" class="space-y-3">
                @csrf
                @if($callList->isNotEmpty())
                <div>
                    <label class="block text-xs font-medium text-slate-600">Client</label>
                    <select class="mt-1 w-full rounded-md border-slate-300 text-sm"
                        onchange="window.location='{{ route('collections.index') }}?client_id='+this.value+'{{ $bucket ? '&bucket='.$bucket : '' }}'">
                        @foreach($callList as $row)
                        <option value="{{ $row->client->id }}" @selected($selectedClient->id === $row->client->id)>{{ $row->client->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div>
                    <label class="block text-xs font-medium text-slate-600">Channel</label>
                    <select name="channel" class="mt-1 w-full rounded-md border-slate-300 text-sm" required>
                        <option value="phone">Phone</option>
                        <option value="whatsapp">WhatsApp</option>
                        <option value="email">Email</option>
                        <option value="in_person">In person</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600">Promise to pay</label>
                    <input type="date" name="promise_date" class="mt-1 w-full rounded-md border-slate-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600">Next action</label>
                    <input type="text" name="next_action" class="mt-1 w-full rounded-md border-slate-300 text-sm" placeholder="e.g. Call again Friday">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600">Notes</label>
                    <textarea name="notes" rows="2" class="mt-1 w-full rounded-md border-slate-300 text-sm"></textarea>
                </div>
                <button type="submit" class="w-full py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700">Save follow-up</button>
            </form>
            @else
            <p class="text-sm text-slate-500">No outstanding balances to follow up.</p>
            @endif
        </div>
    </div>
</div>
@endsection
