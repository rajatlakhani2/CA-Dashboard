@extends('layouts.app')

@section('header', 'Partner Dashboard')

@section('content')
<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow p-5 border-l-4 border-indigo-500">
            <p class="text-xs font-bold text-gray-500 uppercase">MTD Invoiced</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">₹ {{ number_format($mtdInvoiced, 0) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow p-5 border-l-4 border-emerald-500">
            <p class="text-xs font-bold text-gray-500 uppercase">MTD Collected</p>
            <p class="text-2xl font-bold text-emerald-700 mt-1">₹ {{ number_format($mtdCollected, 0) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow p-5 border-l-4 border-amber-500">
            <p class="text-xs font-bold text-gray-500 uppercase">Outstanding</p>
            <p class="text-2xl font-bold text-amber-700 mt-1">₹ {{ number_format(max(0, $outstanding), 0) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow p-5 border-l-4 border-red-500">
            <p class="text-xs font-bold text-gray-500 uppercase">Unbilled queue</p>
            <p class="text-2xl font-bold text-red-700 mt-1">{{ $unbilledQueue }}</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <h3 class="font-bold text-gray-900">Firm alerts</h3>
            <form method="POST" action="{{ route('firm-alerts.scan') }}">
                @csrf
                <button type="submit" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">Run scan now</button>
            </form>
        </div>
        <ul class="space-y-3 text-sm max-h-64 overflow-y-auto">
            @forelse($firmAlerts as $alert)
            <li class="flex gap-3 items-start border-b border-gray-100 pb-3 last:border-0">
                <span class="flex-shrink-0 mt-0.5 h-2 w-2 rounded-full {{ $alert->severity === 'critical' ? 'bg-red-500' : ($alert->severity === 'warning' ? 'bg-amber-500' : 'bg-slate-400') }}"></span>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-gray-900">{{ $alert->title }}</p>
                    <p class="text-gray-600 text-xs mt-0.5">{{ $alert->message }}</p>
                    @if($alert->client)
                    <a href="{{ route('clients.show', $alert->client) }}" class="text-xs text-indigo-600 font-medium">View client →</a>
                    @endif
                </div>
                <form method="POST" action="{{ route('firm-alerts.dismiss', $alert) }}" class="flex-shrink-0">
                    @csrf
                    <button type="submit" class="text-xs text-gray-400 hover:text-gray-600">Dismiss</button>
                </form>
            </li>
            @empty
            <li class="text-gray-500">No open alerts. Run a scan or wait for the daily 6:30 AM job.</li>
            @endforelse
        </ul>
    </div>

    @if($atRiskCompliance->isNotEmpty())
    <div class="bg-white rounded-xl shadow p-6 border border-amber-100">
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-bold text-gray-900">At-risk compliance (next 14 days)</h3>
            <a href="{{ route('service-dues.index') }}" class="text-xs font-semibold text-indigo-600">Reminders →</a>
        </div>
        <ul class="space-y-2 text-sm">
            @foreach($atRiskCompliance as $risk)
            <li class="flex justify-between gap-4">
                <span>
                    <a href="{{ route('clients.show', $risk->client) }}" class="font-medium text-indigo-600 hover:underline">{{ $risk->client?->name }}</a>
                    <span class="text-gray-500"> · {{ $risk->service?->name }}</span>
                </span>
                <span class="flex-shrink-0">
                    @include('partials.status-badge', [
                        'status' => ucfirst($risk->level),
                        'type' => $risk->level === 'high' ? 'danger' : 'warning',
                    ])
                    <span class="ml-1 text-xs text-gray-500">{{ $risk->score }}</span>
                </span>
            </li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="font-bold text-gray-900 mb-4">Risk indicators</h3>
            <ul class="space-y-2 text-sm">
                <li class="flex justify-between"><span>Overdue compliance dues</span><strong class="text-red-600">{{ $overdueCompliance }}</strong></li>
                <li class="flex justify-between"><span>Overdue invoices</span><strong class="text-red-600">{{ $overdueInvoices }}</strong></li>
                <li class="flex justify-between"><span>Open tasks</span><strong>{{ $openTasks }}</strong></li>
                <li class="flex justify-between"><span>Overdue tasks</span><strong class="text-amber-600">{{ $overdueTasks }}</strong></li>
            </ul>
            <div class="mt-4 flex gap-2">
                <a href="{{ route('billing.index') }}" class="text-sm text-indigo-600 font-medium">Billing queue →</a>
                <a href="{{ route('reports.financial') }}" class="text-sm text-indigo-600 font-medium">Reports →</a>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="font-bold text-gray-900 mb-4">Staff workload (open tasks)</h3>
            <ul class="space-y-2 text-sm">
                @forelse($staffLoad as $member)
                <li class="flex justify-between">
                    <span>{{ $member->name }} <span class="text-gray-400">({{ $member->role }})</span></span>
                    <strong>{{ $member->open_tasks_count }}</strong>
                </li>
                @empty
                <li class="text-gray-500">No staff with open tasks.</li>
                @endforelse
            </ul>
        </div>
    </div>

    @if($branchRevenue->isNotEmpty())
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="font-bold text-gray-900 mb-4">Branch revenue (MTD)</h3>
        <table class="min-w-full text-sm">
            <thead><tr class="text-left text-gray-500"><th class="pb-2">Branch</th><th class="pb-2 text-right">Amount</th></tr></thead>
            <tbody>
                @foreach($branchRevenue as $row)
                <tr class="border-t">
                    <td class="py-2">{{ $row->branch?->name ?? 'Unassigned' }}</td>
                    <td class="py-2 text-right font-medium">₹ {{ number_format($row->total, 0) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
