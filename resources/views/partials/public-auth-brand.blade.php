<div class="pb-auth__brand">
    <a href="{{ route('landing') }}" class="pb-auth__logo" aria-label="CA Dashboard home"><img src="/brand/ca-india-logo.png" alt="CA India logo" width="72" height="54"></a>
    <div>
        <h1>{{ $heading ?? 'Sign in' }}</h1>
        <p>{{ $sub ?? '' }}</p>
    </div>
</div>
