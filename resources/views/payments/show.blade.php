@extends('layouts.app')

@section('header', 'Payment ' . $payment->receipt_number)

@section('content')
@php $inv = $payment->invoice; $client = $inv?->client; @endphp
<div class="mx-auto max-w-3xl space-y-6">
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('payments.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-slate-600 hover:text-slate-900">← All payments</a>
        <div class="ml-auto flex flex-wrap gap-2">
            <a href="{{ route('payments.receipt', $payment) }}" class="inline-flex min-h-10 items-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700">Download receipt (PDF)</a>
            @if($inv)
            <a href="{{ route('invoices.show', $inv) }}" class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:border-slate-400">Open invoice</a>
            @endif
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6">
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Amount received</p>
        <p class="mt-1 text-3xl font-bold text-slate-900">₹{{ number_format((float) $payment->amount, 2) }}</p>
        <dl class="mt-6 grid grid-cols-1 gap-x-8 gap-y-4 sm:grid-cols-2 text-sm">
            <div><dt class="text-slate-500">Receipt number</dt><dd class="font-semibold text-slate-900">{{ $payment->receipt_number }}</dd></div>
            <div><dt class="text-slate-500">Date</dt><dd class="font-semibold text-slate-900">{{ optional($payment->payment_date)->format('d M Y') }}</dd></div>
            <div><dt class="text-slate-500">Client</dt><dd class="font-semibold text-slate-900">
                @if($client)<a href="{{ route('clients.show', $client) }}" class="text-blue-700 hover:underline">{{ $client->name }}</a>@else — @endif
            </dd></div>
            <div><dt class="text-slate-500">Invoice</dt><dd class="font-semibold text-slate-900">{{ $inv?->invoice_number ?? '—' }}</dd></div>
            <div><dt class="text-slate-500">Mode</dt><dd class="font-semibold text-slate-900">{{ \Illuminate\Support\Str::headline((string) $payment->payment_mode) ?: '—' }}</dd></div>
            <div><dt class="text-slate-500">Reference</dt><dd class="font-semibold text-slate-900">{{ $payment->reference_number ?: '—' }}</dd></div>
            <div><dt class="text-slate-500">Received by</dt><dd class="font-semibold text-slate-900">{{ $payment->receiver?->name ?? '—' }}</dd></div>
            @if($payment->notes)
            <div class="sm:col-span-2"><dt class="text-slate-500">Notes</dt><dd class="text-slate-800 whitespace-pre-line">{{ $payment->notes }}</dd></div>
            @endif
        </dl>
    </div>

    @if($client)
    <p class="text-sm text-slate-600">See every invoice and payment for this client on the <a href="{{ route('ledger.show', $client) }}" class="font-semibold text-blue-700 hover:underline">client ledger</a>.</p>
    @endif
</div>
@endsection
