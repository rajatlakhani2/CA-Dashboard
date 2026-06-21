@extends('auth.layout')

@section('title', 'Forgot password')
@section('heading', 'Forgot password')
@section('subheading', 'Enter your workspace ID and email — we will send a reset link.')

@section('content')
@if(session('status'))
<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-900">
    {{ session('status') }}
</div>
@endif

<form class="space-y-4" action="{{ route('password.email') }}" method="POST">
    @csrf

    <div>
        <label for="workspace" class="block text-xs font-semibold text-slate-600 mb-1.5">Workspace ID</label>
        <input id="workspace" name="workspace" type="text" required
            value="{{ old('workspace', $workspace ?? '') }}"
            placeholder="e.g. demodashboard"
            class="login-input">
    </div>

    <div>
        <label for="email" class="block text-xs font-semibold text-slate-600 mb-1.5">Email</label>
        <input id="email" name="email" type="email" required autocomplete="email"
            value="{{ old('email') }}"
            class="login-input">
    </div>

    @if ($errors->any())
    <div class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
        <ul class="list-disc pl-4 space-y-0.5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
    @endif

    <button type="submit" class="login-btn">Email reset link</button>
</form>

<p class="mt-5 text-center text-xs text-slate-500">
  Remembered it? <a href="{{ route('login', ['workspace' => old('workspace', $workspace ?? '')]) }}" class="font-semibold login-link hover:opacity-80">Back to sign in</a>
</p>
@endsection
