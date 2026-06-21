@extends('auth.layout')

@section('title', 'Reset password')
@section('heading', 'Choose a new password')
@section('subheading')
Workspace: {{ old('workspace', $workspace ?? '—') }}
@endsection

@section('content')
<form class="space-y-4" action="{{ route('password.update') }}" method="POST">
    @csrf

    <input type="hidden" name="token" value="{{ $token }}">
    <input type="hidden" name="workspace" value="{{ old('workspace', $workspace) }}">

    <div>
        <label for="email" class="block text-xs font-semibold text-slate-600 mb-1.5">Email</label>
        <input id="email" name="email" type="email" required readonly
            value="{{ old('email', $email ?? '') }}"
            class="login-input bg-slate-50">
    </div>

    <div>
        <label for="password" class="block text-xs font-semibold text-slate-600 mb-1.5">New password</label>
        <input id="password" name="password" type="password" required autocomplete="new-password"
            class="login-input">
    </div>

    <div>
        <label for="password_confirmation" class="block text-xs font-semibold text-slate-600 mb-1.5">Confirm password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
            class="login-input">
    </div>

    @if ($errors->any())
    <div class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
        <ul class="list-disc pl-4 space-y-0.5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
    @endif

    <button type="submit" class="login-btn">Update password</button>
</form>

<p class="mt-5 text-center text-xs text-slate-500">
  <a href="{{ route('login', ['workspace' => old('workspace', $workspace ?? '')]) }}" class="font-semibold login-link hover:opacity-80">Back to sign in</a>
</p>
@endsection
