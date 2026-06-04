@extends('layouts.portal')

@section('title', 'Portal link unavailable')
@section('portal_client', 'Link unavailable')

@section('content')
<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-8 text-center max-w-lg mx-auto">
    <p class="text-xs font-bold uppercase tracking-wider text-amber-700">Access denied</p>
    <h2 class="mt-2 text-xl font-bold text-slate-900">This portal link is invalid or has expired</h2>
    <p class="mt-3 text-sm text-slate-600 leading-relaxed">
        Links are valid for <strong>30 days</strong> from when your CA firm creates them.
        If you saved an old link, or the firm re-issued access, you will need a new URL.
    </p>
    <p class="mt-4 text-sm text-slate-500">
        Please contact your CA firm and ask them to open your client record and tap
        <strong>Portal link</strong> to send you a fresh link.
    </p>
</div>
@endsection
