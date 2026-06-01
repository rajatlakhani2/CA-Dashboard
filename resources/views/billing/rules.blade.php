@extends('layouts.app')

@section('header', 'Billing Automation Rules')

@section('content')
<div class="space-y-6 max-w-5xl mx-auto">
    <div class="flex flex-wrap gap-3 items-center justify-between">
        <p class="text-sm text-gray-600">Set fixed fees per service (or client) for completed unbilled dues.</p>
        <form action="{{ route('billing.apply-rules') }}" method="POST">
            @csrf
            <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-bold">Apply rules to queue</button>
        </form>
    </div>

    <form action="{{ route('billing-rules.store') }}" method="POST" class="bg-white rounded-lg shadow p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
        @csrf
        <div class="md:col-span-2">
            <label class="block text-sm font-medium">Rule name</label>
            <input type="text" name="name" required class="mt-1 w-full rounded-md border-gray-300">
        </div>
        <div>
            <label class="block text-sm font-medium">Service</label>
            <select name="service_id" class="mt-1 w-full rounded-md border-gray-300">
                <option value="">— Any —</option>
                @foreach($services as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium">Client (optional)</label>
            <select name="client_id" class="mt-1 w-full rounded-md border-gray-300">
                <option value="">All clients</option>
                @foreach($clients as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium">Rule type</label>
            <select name="rule_type" class="mt-1 w-full rounded-md border-gray-300">
                <option value="fixed_fee">Fixed fee (₹)</option>
                <option value="use_due_amount">Keep due amount</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium">Fixed amount (₹)</label>
            <input type="number" name="fixed_amount" step="0.01" min="0" class="mt-1 w-full rounded-md border-gray-300">
        </div>
        <div class="md:col-span-2">
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md font-medium">Add rule</button>
        </div>
    </form>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-3 text-left">Name</th>
                <th class="px-4 py-3 text-left">Service</th>
                <th class="px-4 py-3 text-left">Client</th>
                <th class="px-4 py-3 text-left">Type</th>
                <th class="px-4 py-3 text-right">Amount</th>
                <th class="px-4 py-3"></th>
            </tr></thead>
            <tbody>
                @forelse($rules as $rule)
                <tr class="border-t">
                    <td class="px-4 py-3 font-medium">{{ $rule->name }}</td>
                    <td class="px-4 py-3">{{ $rule->service?->name ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $rule->client?->name ?? 'All' }}</td>
                    <td class="px-4 py-3">{{ $rule->rule_type }}</td>
                    <td class="px-4 py-3 text-right">{{ $rule->rule_type === 'fixed_fee' ? '₹'.number_format($rule->fixed_amount, 2) : 'Due amount' }}</td>
                    <td class="px-4 py-3 text-right">
                        <form action="{{ route('billing-rules.destroy', $rule) }}" method="POST" onsubmit="return confirm('Delete rule?')">
                            @csrf @method('DELETE')
                            <button class="text-red-600 text-xs font-bold">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No billing rules yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
