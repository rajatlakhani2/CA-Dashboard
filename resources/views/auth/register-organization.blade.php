<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create your firm’s workspace — CA Dashboard</title>
    @include('partials.app-shell-head')
    @include('partials.public-auth-head')
</head>
<body class="pb">
    <main class="pb-auth">
        <div class="pb-auth__card" style="max-width:520px">
            @include('partials.public-auth-brand', ['heading' => 'Create your firm’s workspace', 'sub' => 'Your clients, tasks and invoices stay separate from every other firm.'])
                <form method="POST" action="{{ route('register.organization') }}">
                    @csrf

                    <div class="pb-field">
                        <label>Firm / business name</label>
                        <input name="firm_name" type="text" required value="{{ old('firm_name') }}"
                            placeholder="Sharma & Associates">
                    </div>

                    <div class="pb-field">
                        <label>Workspace ID</label>
                        <p class="pb-field__hint" style="margin:0 0 6px">Lowercase letters, numbers and hyphens. Your team uses it to sign in (e.g. sharma-ca).</p>
                        <input name="workspace" type="text" required value="{{ old('workspace') }}"
                            pattern="[a-z0-9]+(-[a-z0-9]+)*"
                            placeholder="sharma-ca">
                    </div>

                    <hr style="border:0;border-top:1px solid rgba(255,255,255,.14);margin:18px 0">

                    <div class="pb-field">
                        <label>Your name (partner / owner)</label>
                        <input name="admin_name" type="text" required value="{{ old('admin_name') }}">
                    </div>

                    <div class="pb-field">
                        <label>Your email</label>
                        <input name="admin_email" type="email" required value="{{ old('admin_email') }}">
                    </div>

                    <div class="pb-grid pb-grid--2" style="gap:12px">
                        <div class="pb-field">
                            <label>Password</label>
                            <input name="admin_password" type="password" required>
                        </div>
                        <div class="pb-field">
                            <label>Confirm</label>
                            <input name="admin_password_confirmation" type="password" required>
                        </div>
                    </div>

                    @if ($errors->any())
                    <div class="pb-auth__errors" role="alert">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
                    @endif

                    <button type="submit" class="pb-btn pb-btn--primary pb-btn--lg" style="margin-top:6px">
                        Create workspace
                    </button>
                </form>

            <p class="pb-auth__foot">Already have a workspace? <a href="{{ route('login') }}">Sign in</a></p>
            <nav class="pb-auth__links" aria-label="More">
                <a href="{{ route('landing') }}">Home</a>
                <a href="{{ route('privacy') }}">Privacy</a>
                <a href="{{ route('terms') }}">Terms</a>
            </nav>
        </div>
    </main>
</body>
</html>
