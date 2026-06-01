@extends('layouts.portal')

@section('title', $client->name . ' — Portal')
@section('portal_client', $client->name)

@section('content')
<div class="space-y-8">
    <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <h2 class="text-sm font-bold text-slate-900 mb-3">Outstanding invoices</h2>
        @forelse($invoices as $invoice)
        <div class="border-b border-slate-100 py-3 last:border-0 flex flex-wrap justify-between gap-3">
            <div>
                <p class="font-semibold">{{ $invoice->invoice_number }}</p>
                <p class="text-xs text-slate-500">Due {{ $invoice->due_date?->format('d M Y') }} · @include('partials.status-badge', ['status' => $invoice->status])</p>
                <p class="text-sm font-semibold text-money-negative mt-1">₹ {{ number_format($invoice->balanceDue(), 2) }}</p>
            </div>
            @if($invoice->payment_url)
            <div class="text-center">
                @if($invoice->qr_url)
                <img src="{{ $invoice->qr_url }}" alt="Pay" class="w-28 h-28 mx-auto border rounded-lg">
                @endif
                <p class="text-[10px] text-slate-400 mt-1 max-w-[140px]">Scan to pay via UPI</p>
            </div>
            @endif
        </div>
        @empty
        <p class="text-sm text-slate-500">No outstanding invoices. Thank you!</p>
        @endforelse
    </section>

    <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <h2 class="text-sm font-bold text-slate-900 mb-3">Compliance status</h2>
        <ul class="space-y-2 text-sm">
            @forelse($dues as $due)
            <li class="flex justify-between gap-2">
                <span>{{ $due->clientService?->service?->name ?? 'Compliance' }}</span>
                <span class="text-slate-500">{{ $due->due_date?->format('d M') }} · {{ $due->status }}</span>
            </li>
            @empty
            <li class="text-slate-500">No pending compliance items shown.</li>
            @endforelse
        </ul>
    </section>

    <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <h2 class="text-sm font-bold text-slate-900 mb-3">Upload a document</h2>
        <p class="text-xs text-slate-500 mb-3">Returns, notices, bank statements, etc. Our team will review and update your file.</p>
        <form method="POST" action="{{ route('portal.upload', $token) }}" enctype="multipart/form-data" class="space-y-3">
            @csrf
            <input type="file" name="document" required accept=".pdf,.jpg,.jpeg,.png" class="block w-full text-sm">
            <textarea name="notes" rows="2" class="w-full rounded-md border-slate-300 text-sm" placeholder="Optional note for the firm"></textarea>
            <button type="submit" class="w-full py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700">Upload</button>
        </form>
    </section>
</div>
@endsection
